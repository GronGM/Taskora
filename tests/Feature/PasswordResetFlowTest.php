<?php

namespace Tests\Feature;

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_contains_forgot_password_link(): void
    {
        $loginSource = file_get_contents(resource_path('js/Pages/Auth/Login.jsx'));

        $this->assertStringContainsString('Забыли пароль?', $loginSource);
        $this->assertStringContainsString('href="/forgot-password"', $loginSource);
    }

    public function test_guest_can_view_forgot_password_page(): void
    {
        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/ForgotPassword'));
    }

    public function test_authenticated_user_is_redirected_from_forgot_password_page(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($user)
            ->get('/forgot-password')
            ->assertRedirect('/customer/dashboard');
    }

    public function test_forgot_password_returns_neutral_success_for_existing_email(): void
    {
        User::factory()->create(['email' => 'customer@taskora.local']);

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'customer@taskora.local'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', ForgotPasswordController::STATUS_MESSAGE);
    }

    public function test_forgot_password_returns_neutral_success_for_missing_email(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'missing@taskora.local'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHas('status', ForgotPasswordController::STATUS_MESSAGE);

        $this->assertDatabaseMissing('password_reset_tokens', [
            'email' => 'missing@taskora.local',
        ]);
    }

    public function test_forgot_password_validates_email_with_russian_messages(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => ''])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors([
                'email' => 'Введите почту.',
            ]);

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'not-an-email'])
            ->assertRedirect('/forgot-password')
            ->assertSessionHasErrors([
                'email' => 'Введите корректную почту.',
            ]);
    }

    public function test_forgot_password_validation_does_not_show_translation_keys(): void
    {
        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => ''])
            ->assertRedirect('/forgot-password');

        $sessionErrors = session('errors');
        $errors = is_object($sessionErrors) && method_exists($sessionErrors, 'getBag')
            ? $sessionErrors->getBag('default')->all()
            : \Illuminate\Support\Arr::flatten((array) $sessionErrors);

        $this->assertNotEmpty($errors);
        $this->assertStringNotContainsString('validation.', implode(' ', $errors));
    }

    public function test_forgot_password_rate_limit_blocks_frequent_requests(): void
    {
        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.77'])
                ->from('/forgot-password')
                ->post('/forgot-password', ['email' => 'limited@taskora.local'])
                ->assertRedirect('/forgot-password');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.77'])
            ->post('/forgot-password', ['email' => 'limited@taskora.local'])
            ->assertStatus(429);
    }

    public function test_reset_password_rate_limit_blocks_frequent_requests(): void
    {
        for ($attempt = 1; $attempt <= 10; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.88'])
                ->from('/reset-password/token?email=limited@taskora.local')
                ->post('/reset-password', [
                    'token' => 'token',
                    'email' => 'limited@taskora.local',
                    'password' => 'new-password-123',
                    'password_confirmation' => 'new-password-123',
                ])
                ->assertRedirect('/reset-password/token?email=limited@taskora.local');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.10.10.88'])
            ->post('/reset-password', [
                'token' => 'token',
                'email' => 'limited@taskora.local',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertStatus(429);
    }

    public function test_forgot_password_creates_reset_token_for_existing_user(): void
    {
        User::factory()->create(['email' => 'token-user@taskora.local']);

        $this->from('/forgot-password')
            ->post('/forgot-password', ['email' => 'token-user@taskora.local'])
            ->assertRedirect('/forgot-password');

        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'token-user@taskora.local',
        ]);
    }

    public function test_reset_password_page_opens_form(): void
    {
        $this->get('/reset-password/plain-token?email=customer@taskora.local')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/ResetPassword')
                ->where('email', 'customer@taskora.local')
                ->missing('token')
            );
    }

    public function test_reset_password_form_uses_password_visibility_toggles(): void
    {
        $resetSource = file_get_contents(resource_path('js/Pages/Auth/ResetPassword.jsx'));

        $this->assertSame(2, substr_count($resetSource, '<PasswordInput'));
        $this->assertStringContainsString('label="Новый пароль"', $resetSource);
        $this->assertStringContainsString('label="Подтверждение пароля"', $resetSource);
    }

    public function test_reset_password_changes_password_and_allows_login_only_with_new_password(): void
    {
        $user = User::factory()->create([
            'email' => 'reset-user@taskora.local',
            'password' => Hash::make('old-password-123'),
            'role' => User::ROLE_CUSTOMER,
        ]);
        $token = Password::createToken($user);

        $this->from('/reset-password/'.$token.'?email=reset-user@taskora.local')
            ->post('/reset-password', [
                'token' => $token,
                'email' => 'reset-user@taskora.local',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect('/login')
            ->assertSessionHas('success', 'Пароль успешно изменен. Теперь можно войти.');

        $this->assertTrue(Hash::check('new-password-123', $user->refresh()->password));

        $this->from('/login')
            ->post('/login', [
                'email' => 'reset-user@taskora.local',
                'password' => 'old-password-123',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('auth');

        $this->post('/login', [
            'email' => 'reset-user@taskora.local',
            'password' => 'new-password-123',
        ])->assertRedirect('/customer/dashboard');

        $this->assertAuthenticated();
    }

    public function test_invalid_reset_token_shows_russian_error(): void
    {
        User::factory()->create(['email' => 'invalid-token@taskora.local']);

        $this->from('/reset-password/bad-token?email=invalid-token@taskora.local')
            ->post('/reset-password', [
                'token' => 'bad-token',
                'email' => 'invalid-token@taskora.local',
                'password' => 'new-password-123',
                'password_confirmation' => 'new-password-123',
            ])
            ->assertRedirect('/reset-password/bad-token?email=invalid-token@taskora.local')
            ->assertSessionHasErrors([
                'email' => 'Ссылка для сброса пароля недействительна или устарела.',
            ]);
    }

    public function test_password_confirmation_mismatch_shows_russian_error(): void
    {
        $this->from('/reset-password/token?email=customer@taskora.local')
            ->post('/reset-password', [
                'token' => 'token',
                'email' => 'customer@taskora.local',
                'password' => 'new-password-123',
                'password_confirmation' => 'different-password',
            ])
            ->assertRedirect('/reset-password/token?email=customer@taskora.local')
            ->assertSessionHasErrors([
                'password' => 'Пароли не совпадают.',
            ]);
    }

    public function test_short_password_shows_russian_error(): void
    {
        $this->from('/reset-password/token?email=customer@taskora.local')
            ->post('/reset-password', [
                'token' => 'token',
                'email' => 'customer@taskora.local',
                'password' => 'short',
                'password_confirmation' => 'short',
            ])
            ->assertRedirect('/reset-password/token?email=customer@taskora.local')
            ->assertSessionHasErrors([
                'password' => 'Пароль должен быть не короче 8 символов.',
            ]);
    }

    public function test_reset_password_validation_does_not_show_translation_keys(): void
    {
        $this->from('/reset-password/token')
            ->post('/reset-password', [])
            ->assertRedirect('/reset-password/token');

        $sessionErrors = session('errors');
        $errors = is_object($sessionErrors) && method_exists($sessionErrors, 'getBag')
            ? $sessionErrors->getBag('default')->all()
            : \Illuminate\Support\Arr::flatten((array) $sessionErrors);

        $this->assertNotEmpty($errors);
        $this->assertStringNotContainsString('validation.', implode(' ', $errors));
    }

    public function test_mail_log_notice_is_visible_in_local_and_staging(): void
    {
        config([
            'app.env' => 'local',
            'mail.default' => 'log',
        ]);

        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('mailLogNotice', true));
    }

    public function test_mail_log_notice_is_hidden_in_production(): void
    {
        config([
            'app.env' => 'production',
            'mail.default' => 'log',
        ]);

        $this->get('/forgot-password')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('mailLogNotice', false));
    }

    public function test_log_mailer_uses_dedicated_debug_mail_log_channel(): void
    {
        $mailConfig = require config_path('mail.php');
        $loggingConfig = require config_path('logging.php');

        $this->assertSame('mail', $mailConfig['mailers']['log']['channel']);
        $this->assertSame('single', $loggingConfig['channels']['mail']['driver']);
        $this->assertSame(storage_path('logs/mail.log'), $loggingConfig['channels']['mail']['path']);
        $this->assertSame('debug', $loggingConfig['channels']['mail']['level']);
    }

    public function test_reset_token_is_not_rendered_on_login_or_forgot_password_pages(): void
    {
        $token = 'secret-reset-token-that-must-not-appear';

        $this->get('/login')->assertDontSee($token, false);
        $this->get('/forgot-password')->assertDontSee($token, false);
    }
}
