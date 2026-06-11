<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminMailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_mail_settings_page(): void
    {
        config([
            'app.env' => 'staging',
            'mail.default' => 'log',
            'mail.mailers.smtp.host' => 'smtp.example.test',
            'mail.mailers.smtp.port' => 2525,
            'mail.mailers.smtp.username' => 'smtp-user',
            'mail.mailers.smtp.password' => 'super-secret-mail-password',
            'mail.from.address' => 'noreply@example.test',
            'mail.from.name' => 'Taskora Mail',
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->get(route('admin.mail-settings.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Admin/MailSettings/Index')
                ->where('settings.app_env', 'staging')
                ->where('settings.mail_mailer', 'log')
                ->where('settings.mail_host', 'Задан')
                ->where('settings.mail_port', '2525')
                ->where('settings.mail_username', 'Задан')
                ->where('settings.mail_password', 'Задан')
                ->where('settings.mail_from_address', 'noreply@example.test')
                ->where('settings.mail_from_name', 'Taskora Mail')
            );

        $source = file_get_contents(resource_path('js/Pages/Admin/MailSettings/Index.jsx'));

        $this->assertStringContainsString('Настройки почты', $source);
        $this->assertStringContainsString('На staging реальная почта может быть отключена', $response->inertiaProps('warning'));
        $this->assertStringContainsString('Секреты не отображаются', $response->inertiaProps('hint'));
    }

    public function test_non_admin_users_cannot_see_mail_settings(): void
    {
        foreach ([User::ROLE_MODERATOR, User::ROLE_CUSTOMER, User::ROLE_PERFORMER] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.mail-settings.index'))
                ->assertForbidden();
        }
    }

    public function test_guest_is_redirected_from_mail_settings(): void
    {
        $this->get(route('admin.mail-settings.index'))
            ->assertRedirect('/login');
    }

    public function test_mail_settings_does_not_expose_mail_password_value(): void
    {
        config([
            'mail.mailers.smtp.password' => 'super-secret-mail-password',
            'mail.mailers.smtp.username' => 'smtp-user',
            'mail.mailers.smtp.host' => 'smtp.example.test',
        ]);

        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)
            ->get(route('admin.mail-settings.index'))
            ->assertOk();

        $this->assertSame('Задан', $response->inertiaProps('settings.mail_password'));
        $this->assertStringNotContainsString('super-secret-mail-password', $response->getContent());
        $this->assertStringNotContainsString('smtp-user', $response->getContent());
        $this->assertStringNotContainsString('smtp.example.test', $response->getContent());
    }

    public function test_admin_dashboard_links_to_mail_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboards/Admin'));

        $source = file_get_contents(resource_path('js/Pages/Dashboards/Admin.jsx'));

        $this->assertStringContainsString('/admin/mail-settings', $source);
        $this->assertStringContainsString('Настройки почты', $source);
    }
}
