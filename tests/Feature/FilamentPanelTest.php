<?php

namespace Tests\Feature;

use App\Filament\Admin\Pages\AppVersions;
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
            ->assertSee('App Versions')
            ->assertSee('Release history');
    }

    public function test_release_history_filters_by_platform_and_update_status(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        Filament::setCurrentPanel(Filament::getPanel('admin'));

        Livewire::test(AppVersions::class)
            ->set('platformFilter', 'windows')
            ->set('statusFilter', 'required')
            ->assertSee('Showing 1 release records')
            ->assertSee('Below minimum');
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
