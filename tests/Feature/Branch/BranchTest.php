<?php

namespace Tests\Feature\Branch;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesPermissionSeeder::class);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        $this->staff = User::factory()->create();
        $this->staff->assignRole('dive-guide');
    }

    public function test_owner_can_view_branch_index(): void
    {
        Branch::factory()->count(3)->create();

        $this->actingAs($this->owner)
            ->get(route('branches.index'))
            ->assertOk()
            ->assertSee(Branch::first()->name);
    }

    public function test_user_without_permission_cannot_view_branches(): void
    {
        $this->actingAs($this->staff)
            ->get(route('branches.index'))
            ->assertForbidden();
    }

    public function test_owner_can_create_branch_with_users(): void
    {
        $payload = Branch::factory()->make()->only([
            'name', 'brand', 'domain', 'address', 'phone',
        ]);
        $payload['is_active'] = true;
        $payload['users'] = [$this->owner->id, $this->staff->id];

        $response = $this->actingAs($this->owner)
            ->post(route('branches.store'), $payload)
            ->assertRedirect();

        $branch = Branch::where('name', $payload['name'])->firstOrFail();

        $response->assertRedirect(route('branches.edit', $branch));
        $this->assertCount(2, $branch->users);
        $this->assertTrue((bool) $branch->is_active);
    }

    public function test_create_validates_required_fields(): void
    {
        $this->actingAs($this->owner)
            ->post(route('branches.store'), [])
            ->assertSessionHasErrors(['name', 'brand']);
    }

    public function test_update_branch_is_audited(): void
    {
        $branch = Branch::factory()->create(['name' => 'Lama']);

        $this->actingAs($this->owner)
            ->put(route('branches.update', $branch), [
                'name' => 'Baru',
                'brand' => 'scubago',
                'is_active' => false,
            ])
            ->assertRedirect();

        $branch->refresh();

        $this->assertSame('Baru', $branch->name);
        $this->assertFalse($branch->is_active);

        $audit = AuditLog::query()
            ->where('action', 'branch.updated')
            ->where('model_id', $branch->id)
            ->firstOrFail();

        $this->assertSame('Lama', $audit->before['name']);
        $this->assertSame('Baru', $audit->after['name']);
    }

    public function test_delete_branch_requires_permission_and_detaches_users(): void
    {
        $admin = User::factory()->create();
        $role = Role::findByName('admin-cabang');
        $role->givePermissionTo('branches.delete');

        $admin->assignRole($role);

        $branch = Branch::factory()->create();
        $branch->users()->attach([$admin->id]);

        $this->actingAs($this->staff)
            ->delete(route('branches.destroy', $branch))
            ->assertForbidden();

        $this->actingAs($admin)
            ->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseMissing('branches', ['id' => $branch->id]);
        $this->assertDatabaseCount('audit_logs', 1);
    }
}
