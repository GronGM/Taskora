<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthLoginUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_displays_login_form(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Login'));
    }

    public function test_invalid_password_returns_russian_auth_error(): void
    {
        $this->seed();

        $this->from('/login')
            ->post('/login', [
                'email' => 'customer@taskora.local',
                'password' => 'wrong-password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'auth' => 'Неверная почта или пароль.',
            ]);

        $this->assertGuest();
    }

    public function test_invalid_password_error_is_available_in_inertia_errors(): void
    {
        $this->seed();

        $this->followingRedirects()
            ->from('/login')
            ->post('/login', [
                'email' => 'customer@taskora.local',
                'password' => 'wrong-password',
            ])
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/Login')
                ->where('errors.auth', 'Неверная почта или пароль.')
            );
    }

    public function test_login_validation_messages_are_russian(): void
    {
        $this->from('/login')
            ->post('/login', [
                'email' => '',
                'password' => '',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'Введите почту.',
                'password' => 'Введите пароль.',
            ]);

        $this->from('/login')
            ->post('/login', [
                'email' => 'not-an-email',
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'email' => 'Введите корректную почту.',
            ]);
    }

    public function test_login_page_uses_password_visibility_toggle(): void
    {
        $loginSource = file_get_contents(resource_path('js/Pages/Auth/Login.jsx'));
        $passwordInputSource = file_get_contents(resource_path('js/Components/Auth/PasswordInput.jsx'));

        $this->assertStringContainsString('<PasswordInput', $loginSource);
        $this->assertStringContainsString('autoComplete="current-password"', $loginSource);
        $this->assertStringContainsString('Показать пароль', $passwordInputSource);
        $this->assertStringContainsString('Скрыть пароль', $passwordInputSource);
        $this->assertStringContainsString('type="button"', $passwordInputSource);
        $this->assertStringContainsString('aria-label', $passwordInputSource);
        $this->assertStringContainsString("type={visible ? 'text' : 'password'}", $passwordInputSource);
    }

    public function test_registration_page_uses_password_visibility_toggles(): void
    {
        $registerSource = file_get_contents(resource_path('js/Pages/Auth/Register.jsx'));

        $this->assertSame(2, substr_count($registerSource, '<PasswordInput'));
        $this->assertStringContainsString('id="password"', $registerSource);
        $this->assertStringContainsString('id="password_confirmation"', $registerSource);
        $this->assertStringContainsString('autoComplete="new-password"', $registerSource);
    }

    public function test_seeded_customer_login_still_works(): void
    {
        $this->seed();

        $this->post('/login', [
            'email' => 'customer@taskora.local',
            'password' => 'password',
        ])
            ->assertRedirect('/customer/dashboard');

        $this->assertAuthenticated();
    }
}
