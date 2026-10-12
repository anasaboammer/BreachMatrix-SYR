<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\EncryptionApps;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Pages\Auth\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FilamentPanelTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_admin_login_page(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_guest_is_redirected_to_login_from_the_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_user_can_log_in_and_open_the_admin_dashboard(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'password')
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticatedAs($user);
        $this->get('/admin')
            ->assertOk()
            ->assertSee('Encryption Apps')
            ->assertSee('No app profiles yet')
            ->assertDontSee('Release history');
    }

    public function test_new_app_form_displays_identity_encryption_and_platform_choices(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EncryptionApps::class)
            ->mountAction('newApp')
            ->assertSee('App name')
            ->assertSee('App icon')
            ->assertSee('AES-256-GCM')
            ->assertSee('RSA-3072')
            ->assertSee('Windows')
            ->assertSee('Linux')
            ->assertSee('Android')
            ->assertSee('does not build an app');
    }

    public function test_submitting_a_profile_only_displays_a_preview_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(EncryptionApps::class)
            ->callAction('newApp', [
                'app_name' => 'Personal vault',
                'icon' => 'shield',
                'algorithm' => 'hybrid',
                'platform' => 'linux',
            ])
            ->assertSee('Personal vault')
            ->assertSee('AES + RSA · Linux · Preview only, not saved or built');
    }

    public function test_invalid_credentials_do_not_authenticate_the_user(): void
    {
        $user = User::factory()->create();
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(Login::class)
            ->set('data.email', $user->email)
            ->set('data.password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }
}
