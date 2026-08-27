<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Database\Seeders\RolesPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolesPermissionSeeder::class]);
        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');
    }

    public function test_settings_index_requires_permission(): void
    {
        $this->actingAs($this->owner)
            ->get(route('settings.index'))
            ->assertOk();
    }

    public function test_settings_index_shows_business_tab(): void
    {
        Setting::set('business_name', 'Tulamben Scuba', 'business');

        $this->actingAs($this->owner)
            ->get(route('settings.index', ['tab' => 'business']))
            ->assertOk()
            ->assertSee('Tulamben Scuba');
    }

    public function test_settings_update_business(): void
    {
        $this->actingAs($this->owner)
            ->put(route('settings.update', ['tab' => 'business']), [
                'business_name' => 'SIP Garden Resort',
                'business_phone' => '+628123456789',
                'business_email' => 'info@sipgarden.com',
            ])
            ->assertRedirect();

        $this->assertEquals('SIP Garden Resort', Setting::get('business_name'));
        $this->assertEquals('+628123456789', Setting::get('business_phone'));
    }

    public function test_settings_update_notifications(): void
    {
        $this->actingAs($this->owner)
            ->put(route('settings.update', ['tab' => 'notifications']), [
                'wa_invoice_paid' => 'Terima kasih :name!',
                'wa_schedule_reminder' => 'Halo :name, pengingat :label',
                'email_invoice_subject' => 'Invoice #:no',
                'email_invoice_body' => 'Halo :name',
            ])
            ->assertRedirect();

        $this->assertEquals('Terima kasih :name!', Setting::get('wa_invoice_paid'));
    }

    public function test_settings_update_templates(): void
    {
        $this->actingAs($this->owner)
            ->put(route('settings.update', ['tab' => 'templates']), [
                'pdf_paper_size' => 'a4',
                'pdf_show_tax' => '0',
                'pdf_show_logo' => '1',
            ])
            ->assertRedirect();

        $this->assertEquals('a4', Setting::get('pdf_paper_size'));
        $this->assertEquals('0', Setting::get('pdf_show_tax'));
    }
}
