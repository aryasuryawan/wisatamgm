<?php

namespace Tests\Feature\Customer;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionSeeder::class);

        $this->branch = Branch::factory()->create();
        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin-cabang');
        $this->admin->branches()->attach($this->branch->id);
    }

    public function test_owner_can_view_customers(): void
    {
        $owner = User::factory()->create();
        $owner->assignRole('owner');
        Customer::factory()->count(3)->create(['branch_id' => $this->branch->id]);

        $this->actingAs($owner)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee(Customer::first()->name);
    }

    public function test_admin_can_view_customers(): void
    {
        Customer::factory()->count(2)->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->admin)
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee(Customer::first()->name);
    }

    public function test_search_by_name(): void
    {
        Customer::factory()->create(['name' => 'Budi Darmawan', 'branch_id' => $this->branch->id]);
        Customer::factory()->create(['name' => 'Siti Rahayu', 'branch_id' => $this->branch->id]);

        $this->actingAs($this->admin)
            ->get(route('customers.index', ['q' => 'Budi']))
            ->assertSee('Budi Darmawan')
            ->assertDontSee('Siti Rahayu');
    }

    public function test_filter_by_nationality(): void
    {
        Customer::factory()->indonesian()->create(['name' => 'Budi', 'branch_id' => $this->branch->id]);
        Customer::factory()->international()->create(['name' => 'John', 'branch_id' => $this->branch->id]);

        $this->actingAs($this->admin)
            ->get(route('customers.index', ['nationality' => 'indonesia']))
            ->assertSee('Budi')
            ->assertDontSee('John');

        $this->actingAs($this->admin)
            ->get(route('customers.index', ['nationality' => 'international']))
            ->assertSee('John')
            ->assertDontSee('Budi');
    }

    public function test_create_customer(): void
    {
        $this->actingAs($this->admin)
            ->post(route('customers.store'), [
                'name' => 'Joko Widodo',
                'source' => 'organic',
                'nationality_type' => 'indonesia',
                'phone' => '08123456789',
                'branch_id' => $this->branch->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'name' => 'Joko Widodo',
            'nationality_type' => 'indonesia',
        ]);
    }

    public function test_create_international_customer(): void
    {
        $this->actingAs($this->admin)
            ->post(route('customers.store'), [
                'name' => 'John Smith',
                'source' => 'walk_in',
                'nationality_type' => 'international',
                'phone' => '+1234567890',
                'branch_id' => $this->branch->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'name' => 'John Smith',
            'nationality_type' => 'international',
        ]);
    }

    public function test_create_customer_with_preferences(): void
    {
        $this->actingAs($this->admin)
            ->post(route('customers.store'), [
                'name' => 'Customer withPrefs',
                'source' => 'organic',
                'nationality_type' => 'indonesia',
                'branch_id' => $this->branch->id,
                'preferences' => [
                    'allergies' => 'Shellfish',
                    'equipment_size' => 'L',
                    'experience_level' => 'advanced',
                ],
            ])
            ->assertRedirect();

        $customer = Customer::where('name', 'Customer withPrefs')->first();
        $this->assertEquals('Shellfish', $customer->getPreference('allergies'));
        $this->assertEquals('L', $customer->getPreference('equipment_size'));
        $this->assertEquals('advanced', $customer->getPreference('experience_level'));
    }

    public function test_update_customer(): void
    {
        $customer = Customer::factory()->create(['name' => 'Old Name', 'branch_id' => $this->branch->id]);

        $this->actingAs($this->admin)
            ->put(route('customers.update', $customer), [
                'name' => 'New Name',
                'source' => 'organic',
                'nationality_type' => 'indonesia',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('customers', ['id' => $customer->id, 'name' => 'New Name']);
    }

    public function test_delete_customer(): void
    {
        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);

        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $this->actingAs($owner)
            ->delete(route('customers.destroy', $customer))
            ->assertRedirect();

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_view_customer_profile(): void
    {
        $customer = Customer::factory()->create(['branch_id' => $this->branch->id]);

        $this->actingAs($this->admin)
            ->get(route('customers.show', $customer))
            ->assertOk()
            ->assertSee($customer->name);
    }

    public function test_validation_requires_name(): void
    {
        $this->actingAs($this->admin)
            ->post(route('customers.store'), [
                'name' => '',
                'source' => 'organic',
                'nationality_type' => 'indonesia',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_validation_requires_source(): void
    {
        $this->actingAs($this->admin)
            ->post(route('customers.store'), [
                'name' => 'Test',
                'source' => '',
                'nationality_type' => 'indonesia',
            ])
            ->assertSessionHasErrors('source');
    }

    public function test_validation_requires_nationality(): void
    {
        $this->actingAs($this->admin)
            ->post(route('customers.store'), [
                'name' => 'Test',
                'source' => 'organic',
                'nationality_type' => '',
            ])
            ->assertSessionHasErrors('nationality_type');
    }

    public function test_user_without_permission_cannot_view(): void
    {
        // Create a user with no customer permissions
        $finance = User::factory()->create()->assignRole('finance');

        $this->actingAs($finance)
            ->get(route('customers.index'))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_redirected_to_login(): void
    {
        $this->get(route('customers.index'))
            ->assertRedirect(route('login'));
    }
}
