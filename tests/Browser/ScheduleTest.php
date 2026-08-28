<?php

namespace Tests\Browser;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Schedule;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ScheduleTest extends DuskTestCase
{
    use DatabaseMigrations;

    private User $admin;
    private Branch $branch;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionSeeder::class);

        $this->branch = Branch::factory()->create(['name' => 'Tulamben Main']);
        $tripCategory = \App\Models\ProductCategory::factory()->create(['type_slug' => 'wisata', 'name' => 'Wisata']);
        $this->product = Product::factory()->create([
            'name' => 'Fun Dive USAT Liberty',
            'branch_id' => $this->branch->id,
            'category_id' => $tripCategory->id,
        ]);

        $this->admin = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);
        $this->admin->assignRole('owner');
    }

    public function test_can_view_schedule_list(): void
    {
        Schedule::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
        ]);

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('schedules.index'))
                ->assertVisible('@schedules-table')
                ->assertSee('Fun Dive USAT Liberty');
        });
    }

    public function test_full_flow_create_participant_status(): void
    {
        $customer = Customer::factory()->create(['name' => 'Budi Diver', 'branch_id' => $this->branch->id]);

        $dateStart = now()->addDays(2)->format('Y-m-d') . 'T08:00';
        $dateEnd = now()->addDays(2)->format('Y-m-d') . 'T14:00';

        $this->browse(function (Browser $browser) use ($dateStart, $dateEnd) {
            $browser->loginAs($this->admin)
                ->visit(route('schedules.create'))
                ->select('product_id', $this->product->id)
                ->select('branch_id', $this->branch->id)
                ->script("document.querySelector('[name=date_start]').value = '{$dateStart}'; document.querySelector('[name=date_start]').dispatchEvent(new Event('change')); document.querySelector('[name=date_start]').dispatchEvent(new Event('input'));");
            $browser->script("document.querySelector('[name=date_end]').value = '{$dateEnd}'; document.querySelector('[name=date_end]').dispatchEvent(new Event('change')); document.querySelector('[name=date_end]').dispatchEvent(new Event('input'));");

            $browser->clear('@input-capacity')
                ->type('@input-capacity', '6')
                ->press('@save-schedule')
                ->pause(1500)
                ->storeSource('schedule-after-save')
                ->waitForText('Fun Dive USAT Liberty', 10);
        });

        $schedule = Schedule::orderByDesc('id')->first();
        $this->assertNotNull($schedule, 'Schedule should be created');
        $this->assertEquals('draft', $schedule->status);

        $showUrl = route('schedules.show', $schedule);

        $this->browse(function (Browser $browser) use ($showUrl, $customer) {
            // Daftarkan peserta.
            $browser->loginAs($this->admin)
                ->visit($showUrl)
                ->select('customer_id', $customer->id)
                ->press('@add-participant-button')
                ->waitForText('Budi Diver', 10)
                ->assertVisible('@participant-form');

            // Ubah status draft -> confirmed — note: translation now "Terbooking" due to booking override (ui.status_confirmed)
            $browser->press('@status-action-confirmed')
                ->pause(1500)
                ->waitFor('@schedule-status', 10)
                ->pause(300);
            $browser->assertSee('Terbooking');

            // Ubah status confirmed -> ongoing.
            $browser->press('@status-action-ongoing')
                ->pause(1200)
                ->waitFor('@schedule-status', 10)
                ->pause(300);
            $browser->assertSee('Berjalan');
        });

        $this->assertDatabaseHas('schedule_participants', [
            'schedule_id' => $schedule->id,
            'customer_id' => $customer->id,
        ]);
        $this->assertEquals('ongoing', $schedule->fresh()->status);
    }

    public function test_validation_blocks_empty_form(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->admin)
                ->visit(route('schedules.create'))
                ->press('@save-schedule')
                ->pause(500)
                ->assertPathIs('/schedules/create');
        });

        $this->assertDatabaseCount('schedules', 0);
    }
}
