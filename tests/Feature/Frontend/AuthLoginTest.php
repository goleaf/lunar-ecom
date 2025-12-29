<?php

namespace Tests\Feature\Frontend;

use App\Livewire\Frontend\Pages\Auth\Login as LoginPage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_route_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Login');
    }

    public function test_login_livewire_authenticates_and_redirects(): void
    {
        $user = User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);

        Livewire::test(LoginPage::class)
            ->set('email', 'user@example.com')
            ->set('password', 'password')
            ->set('remember', true)
            ->call('login')
            ->assertRedirect(route('frontend.homepage'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_livewire_rejects_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => Hash::make('password'),
        ]);

        Livewire::test(LoginPage::class)
            ->set('email', 'user@example.com')
            ->set('password', 'wrong-password')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }
}

