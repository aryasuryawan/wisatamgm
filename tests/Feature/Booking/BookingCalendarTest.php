<?php

namespace Tests\Feature\Booking;

use App\Models\BookableUnit;
use App\Models\Booking;
use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCalendarTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private User $owner;

    private BookableUnit $unit;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesPermissionSeeder::class]);

        $this->branch = Branch::factory()->create();
        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        $product = Product::factory()->create(['branch_id' => $this->branch->id]);
        $this->unit = BookableUnit::factory()->room()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_calendar_shows_month_and_booked_cells(): void
    {
        Booking::create([
            'branch_id' => $this->branch->id,
            'bookable_unit_id' => $this->unit->id,
            'user_id' => $this->owner->id,
            'guest_name' => 'Tamu Kalender',
            'guests_count' => 1,
            'date_start' => now()->startOfMonth()->addDays(4),
            'date_end' => now()->startOfMonth()->addDays(6),
            'amount_total' => 900000,
            'status' => 'confirmed',
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('bookings.calendar'))
            ->assertOk();

        $this->assertStringContainsString('booking-calendar-table', (string) $response->getContent());
        $this->assertStringContainsString('Tamu Kalender', (string) $response->getContent());
    }

    public function test_calendar_navigation_months(): void
    {
        $this->actingAs($this->owner)
            ->get(route('bookings.calendar', ['month' => '2026-12']))
            ->assertOk()
            ->assertSee('Desember 2026');
    }

    public function test_cancelled_bookings_do_not_block_cells(): void
    {
        Booking::create([
            'branch_id' => $this->branch->id,
            'bookable_unit_id' => $this->unit->id,
            'user_id' => $this->owner->id,
            'guest_name' => 'Dibatalkan',
            'guests_count' => 1,
            'date_start' => now()->startOfMonth()->addDays(2),
            'date_end' => now()->startOfMonth()->addDays(4),
            'amount_total' => 500000,
            'status' => 'cancelled',
        ]);

        $response = $this->actingAs($this->owner)->get(route('bookings.calendar'))->assertOk();

        // Nama tamu batal tidak tampil di sel.
        $this->assertStringNotContainsString('Dibatalkan', (string) $response->getContent());
    }
}
