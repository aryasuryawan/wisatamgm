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
        $this->product = Product::factory()->create([
            'name' => 'Fun Dive USAT Liberty',
            'branch_id' => $this->branch->id,
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
                ->script("document.querySelector('[name=date_start]').value = '{$dateStart}';");
            $browser->script("document.querySelector('[name=date_end]').value = '{$dateEnd}';");

            $browser->type('capacity', '6')
                ->press('@save-schedule')
                ->waitUntil("window.location.pathname.startsWith('/schedules/') && !window.location.pathname.endsWith('/create')");
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
                ->waitForText('Budi Diver')
                ->assertVisible('@participant-form');

            // Ubah status draft -> confirmed.
            $browser->press('@status-action-confirmed')
                ->waitForText('Terkonfirmasi');

            // Ubah status confirmed -> ongoing.
            $browser->press('@status-action-ongoing')
                ->waitForText('Berjalan');
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
