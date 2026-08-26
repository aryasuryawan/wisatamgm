<?php

namespace Tests\Feature\Schedule;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private Branch $branch;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(['RolesPermissionSeeder']);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        $this->branch = Branch::factory()->create();
        $this->product = Product::factory()->create(['name' => 'Fun Dive Tulamben']);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'date_start' => now()->addDays(3)->format('Y-m-d\TH:i'),
            'date_end' => now()->addDays(3)->addHours(6)->format('Y-m-d\TH:i'),
            'capacity' => 8,
            'notes' => null,
        ], $overrides);
    }

    public function test_owner_can_view_schedules(): void
    {
        Schedule::factory()->count(3)->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
        ]);

        $this->actingAs($this->owner)
            ->get(route('schedules.index'))
            ->assertOk();
    }

    public function test_create_schedule(): void
    {
        $this->actingAs($this->owner)
            ->post(route('schedules.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseCount('schedules', 1);
        $this->assertDatabaseHas('schedules', [
            'product_id' => $this->product->id,
            'status' => 'draft',
        ]);
    }

    public function test_update_schedule(): void
    {
        $schedule = Schedule::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'capacity' => 8,
        ]);

        $this->actingAs($this->owner)
            ->put(route('schedules.update', $schedule), $this->validPayload(['capacity' => 12]))
            ->assertRedirect();

        $this->assertEquals(12, $schedule->fresh()->capacity);
    }

    public function test_delete_draft_schedule(): void
    {
        $schedule = Schedule::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
        ]);

        $this->actingAs($this->owner)
            ->delete(route('schedules.destroy', $schedule))
            ->assertRedirect();

        $this->assertDatabaseMissing('schedules', ['id' => $schedule->id]);
    }

    public function test_cannot_delete_confirmed_schedule(): void
    {
        $schedule = Schedule::factory()->confirmed()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
        ]);

        $this->actingAs($this->owner)
            ->delete(route('schedules.destroy', $schedule))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('schedules', ['id' => $schedule->id]);
    }

    public function test_validation_requires_fields(): void
    {
        $this->actingAs($this->owner)
            ->post(route('schedules.store'), [])
            ->assertSessionHasErrors(['branch_id', 'product_id', 'date_start', 'capacity']);
    }

    public function test_capacity_must_be_positive(): void
    {
        $this->actingAs($this->owner)
            ->post(route('schedules.store'), $this->validPayload(['capacity' => 0]))
            ->assertSessionHasErrors(['capacity']);
    }

    public function test_add_participant(): void
    {
        $schedule = Schedule::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'capacity' => 4,
        ]);
        $customer = Customer::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('schedules.participants.store', $schedule), ['customer_id' => $customer->id])
            ->assertRedirect();

        $this->assertDatabaseHas('schedule_participants', [
            'schedule_id' => $schedule->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_duplicate_participant_blocked(): void
    {
        $schedule = Schedule::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
        ]);
        $customer = Customer::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('schedules.participants.store', $schedule), ['customer_id' => $customer->id])
            ->assertRedirect();

        $this->actingAs($this->owner)
            ->post(route('schedules.participants.store', $schedule), ['customer_id' => $customer->id])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_participant_blocked_when_full(): void
    {
        $schedule = Schedule::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
            'capacity' => 1,
        ]);
        $schedule->participants()->create(['customer_id' => Customer::factory()->create()->id]);

        $newCustomer = Customer::factory()->create();

        $this->actingAs($this->owner)
            ->post(route('schedules.participants.store', $schedule), ['customer_id' => $newCustomer->id])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('schedule_participants', ['customer_id' => $newCustomer->id]);
    }

    public function test_remove_participant(): void
    {
        $schedule = Schedule::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
        ]);
        $participant = $schedule->participants()->create(['customer_id' => Customer::factory()->create()->id]);

        $this->actingAs($this->owner)
            ->delete(route('schedules.participants.destroy', [$schedule, $participant]))
            ->assertRedirect();

        $this->assertDatabaseMissing('schedule_participants', ['id' => $participant->id]);
    }

    public function test_status_transition_valid(): void
    {
        $schedule = Schedule::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
        ]);

        $this->actingAs($this->owner)
            ->patch(route('schedules.status', $schedule), ['status' => 'confirmed'])
            ->assertRedirect();

        $this->assertEquals('confirmed', $schedule->fresh()->status);
    }

    public function test_status_transition_invalid(): void
    {
        $schedule = Schedule::factory()->completed()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
        ]);

        $this->actingAs($this->owner)
            ->patch(route('schedules.status', $schedule), ['status' => 'draft'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertEquals('completed', $schedule->fresh()->status);
    }

    public function test_add_and_remove_staff(): void
    {
        $schedule = Schedule::factory()->create([
            'branch_id' => $this->branch->id,
            'product_id' => $this->product->id,
        ]);
        $guide = User::factory()->create()->assignRole('dive-guide');

        $this->actingAs($this->owner)
            ->post(route('schedules.staff.store', $schedule), [
                'user_id' => $guide->id,
                'role_in_trip' => 'guide',
            ])
            ->assertRedirect();

        $staffId = $schedule->staff()->firstOrFail()->id;

        $this->assertDatabaseHas('schedule_staff', [
            'schedule_id' => $schedule->id,
            'user_id' => $guide->id,
            'role_in_trip' => 'guide',
        ]);

        $this->actingAs($this->owner)
            ->delete(route('schedules.staff.destroy', [$schedule, $staffId]))
            ->assertRedirect();

        $this->assertDatabaseMissing('schedule_staff', ['id' => $staffId]);
    }

    public function test_admin_cabang_sees_only_own_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        Schedule::factory()->create(['branch_id' => $this->branch->id, 'product_id' => $this->product->id]);
        Schedule::factory()->create(['branch_id' => $otherBranch->id, 'product_id' => $this->product->id]);

        $admin = User::factory()->create();
        $admin->assignRole('admin-cabang');
        $admin->branches()->attach($this->branch->id);

        $response = $this->actingAs($admin)->get(route('schedules.index'));
        $response->assertOk();

        $visible = $response->viewData('schedules');
        $this->assertEquals(1, $visible->count());
        $this->assertEquals($this->branch->id, $visible->first()->branch_id);
    }

    public function test_dive_guide_sees_only_assigned_schedules(): void
    {
        $mine = Schedule::factory()->create(['branch_id' => $this->branch->id, 'product_id' => $this->product->id]);
        $other = Schedule::factory()->create(['branch_id' => $this->branch->id, 'product_id' => $this->product->id]);
        $guide = User::factory()->create()->assignRole('dive-guide');
        $mine->staff()->create(['user_id' => $guide->id, 'role_in_trip' => 'guide']);

        $response = $this->actingAs($guide)->get(route('schedules.index'));
        $response->assertOk();

        $ids = $response->viewData('schedules')->pluck('id')->all();
        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($other->id, $ids);
    }

    public function test_create_page_only_offers_trip_products(): void
    {
        $wisata = \App\Models\ProductCategory::factory()->create(['type_slug' => 'wisata']);
        $makanan = \App\Models\ProductCategory::factory()->create(['type_slug' => 'makanan']);
        $jasa = \App\Models\ProductCategory::factory()->create(['type_slug' => 'jasa']);

        $trip = Product::factory()->create(['name' => 'Fun Dive Trip', 'category_id' => $wisata->id]);
        $course = Product::factory()->create(['name' => 'Open Water Class', 'category_id' => $jasa->id]);
        $snack = Product::factory()->create(['name' => 'Snack Box', 'category_id' => $makanan->id]);

        $this->actingAs($this->owner)
            ->get(route('schedules.create'))
            ->assertOk()
            ->assertSee('Fun Dive Trip')
            ->assertSee('Open Water Class')
            ->assertDontSee('Snack Box');

        $this->assertTrue($trip->exists && $course->exists && $snack->exists);
    }

    public function test_user_without_permission_cannot_view(): void
    {
        $finance = User::factory()->create()->assignRole('finance');

        $this->actingAs($finance)
            ->get(route('schedules.index'))
            ->assertForbidden();
    }
}
