<?php

namespace Tests\Feature\Booking;

use App\Domain\Booking\Services\BookingService;
use App\Models\BookableUnit;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private BookableUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesPermissionSeeder::class]);

        $this->branch = Branch::factory()->create();

        $product = Product::factory()->create([
            'branch_id' => $this->branch->id,
            'base_price' => 500000,
        ]);

        $this->unit = BookableUnit::factory()->room()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
            'base_price' => 500000,
        ]);
    }

    private function owner(): User
    {
        $user = User::factory()->create();
        $user->assignRole('owner');

        return $user;
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'bookable_unit_id' => $this->unit->id,
            'guest_name' => 'Tamu Uji',
            'guest_phone' => '081200011122',
            'guests_count' => 2,
            'date_start' => '2026-09-10',
            'date_end' => '2026-09-12',
            'amount_total' => 1000000,
        ], $overrides);
    }

    public function test_create_booking_blocks_dates(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)
            ->post(route('bookings.store'), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('bookings', [
            'guest_name' => 'Tamu Uji',
            'status' => 'confirmed',
        ]);

        // Overlap penuh ditolak
        $this->actingAs($owner)
            ->post(route('bookings.store'), $this->payload(['guest_name' => 'Tamu Kedua']))
            ->assertSessionHasErrors('date_start');
    }

    public function test_checkout_date_is_exclusive(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner)->post(route('bookings.store'), $this->payload())->assertRedirect();

        // Check-in tepat di tanggal check-out tamu sebelumnya = BOLEH.
        $this->actingAs($owner)
            ->post(route('bookings.store'), $this->payload([
                'guest_name' => 'Tamu Berikutnya',
                'date_start' => '2026-09-12',
                'date_end' => '2026-09-14',
            ]))
            ->assertRedirect();

        $this->assertEquals(2, Booking::count());
    }

    public function test_cancel_frees_dates(): void
    {
        $owner = $this->owner();
        $booking = app(BookingService::class)
            ->create($owner, $this->payload());

        $this->actingAs($owner)->post(route('bookings.cancel', $booking))->assertRedirect();
        $this->assertEquals('cancelled', $booking->fresh()->status);

        // Tanggal bebas lagi → bisa dibooking orang lain.
        $this->actingAs($owner)
            ->post(route('bookings.store'), $this->payload(['guest_name' => 'Pengganti']))
            ->assertRedirect();

        $this->assertEquals(2, Booking::count());
    }

    public function test_guests_over_capacity_rejected(): void
    {
        $this->actingAs($this->owner())
            ->post(route('bookings.store'), $this->payload(['guests_count' => 10]))
            ->assertSessionHasErrors('guests_count');
    }

    public function test_payment_creates_transaction_with_backdate_and_split(): void
    {
        $owner = $this->owner();

        $booking = app(BookingService::class)
            ->create($owner, $this->payload()); // 2 malam x 500k

        $this->actingAs($owner)
            ->post(route('bookings.payments.store', $booking), [
                'method' => 'transfer',
                'amount' => 300000,
            ])
            ->assertRedirect();

        $booking->refresh();
        $txn = $booking->transaction;

        $this->assertNotNull($txn);
        $this->assertEquals('partial', $txn->status);
        $this->assertEquals('2026-09-10 12:00', $txn->transaction_date->format('Y-m-d H:i'));

        // Pelunasan lewat endpoint yang sama (split payment).
        $this->actingAs($owner)
            ->post(route('bookings.payments.store', $booking), [
                'method' => 'cash',
                'amount' => 810000, // 1.000.000 + PPN 11% - 300.000
            ])
            ->assertRedirect();

        $this->assertEquals('paid', $booking->transaction->fresh()->status);
        $this->assertEquals(2, $booking->transaction->payments()->count());
    }

    public function test_overpay_rejected(): void
    {
        $owner = $this->owner();
        $booking = app(BookingService::class)
            ->create($owner, $this->payload());

        $this->actingAs($owner)
            ->post(route('bookings.payments.store', $booking), [
                'method' => 'cash',
                'amount' => 99999999,
            ])
            ->assertSessionHasErrors('amount');

        // Transaksi terlanjur dibuat (menunggu pembayaran) tapi TIDAK ada
        // payment masuk dan status tetap bukan paid.
        $booking->refresh();
        $this->assertNotNull($booking->transaction_id);
        $this->assertEquals(0, $booking->transaction->payments()->count());
        $this->assertNotEquals('paid', $booking->transaction->status);
    }

    public function test_checkin_checkout_flow(): void
    {
        $owner = $this->owner();
        $booking = app(BookingService::class)
            ->create($owner, $this->payload());

        // Check-out sebelum check-in ditolak (403 oleh policy).
        $this->actingAs($owner)
            ->post(route('bookings.check-out', $booking))
            ->assertForbidden();

        $this->assertEquals('confirmed', $booking->fresh()->status);

        $this->actingAs($owner)->post(route('bookings.check-in', $booking))->assertRedirect();
        $this->assertEquals('checked_in', $booking->fresh()->status);

        $this->actingAs($owner)->post(route('bookings.check-out', $booking))->assertRedirect();
        $this->assertEquals('checked_out', $booking->fresh()->status);
    }

    public function test_kasir_can_create_but_guide_cannot_view(): void
    {
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');

        $this->actingAs($kasir)
            ->post(route('bookings.store'), $this->payload())
            ->assertRedirect();

        $guide = User::factory()->create();
        $guide->assignRole('dive-guide');

        $this->actingAs($guide)->get(route('bookings.index'))->assertForbidden();
    }

    public function test_admin_cabang_sees_only_own_branch_bookings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin-cabang');
        $admin->branches()->attach($this->branch);

        app(BookingService::class)
            ->create($this->owner(), $this->payload(['guest_name' => 'Milik Sendiri']));

        $otherBranch = Branch::factory()->create();
        $otherUnitProduct = Product::factory()->create(['branch_id' => $otherBranch->id]);
        $otherUnit = BookableUnit::factory()->room()->create([
            'branch_id' => $otherBranch->id,
            'product_id' => $otherUnitProduct->id,
        ]);
        app(BookingService::class)
            ->create($this->owner(), $this->payload([
                'bookable_unit_id' => $otherUnit->id,
                'guest_name' => 'Cabang Sebelah',
            ]));

        $this->actingAs($admin)
            ->get(route('bookings.index'))
            ->assertOk()
            ->assertSee('Milik Sendiri')
            ->assertDontSee('Cabang Sebelah');
    }
}
