<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_pos_page_renders(): void
    {
        $this->seed(['RolesPermissionSeeder']);
        Branch::factory()->create();
        $owner = User::factory()->create();
        $owner->assignRole('owner');

        $r = $this->actingAs($owner)->get(route('transactions.create'));
        if ($e = $r->exception ?? null) {
            dump(class_basename($e), $e->getMessage(), $e->getFile() . ':' . $e->getLine());
        }
        $r->assertOk();
    }
}
