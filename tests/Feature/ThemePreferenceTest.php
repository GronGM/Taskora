<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_base_layout_contains_pre_mount_theme_script(): void
    {
        $source = file_get_contents(resource_path('views/app.blade.php'));

        $this->assertStringContainsString('taskora_theme', $source);
        $this->assertStringNotContainsString('matchMedia', $source);
        $this->assertStringContainsString("let preference = 'light';", $source);
        $this->assertStringContainsString("['light', 'dark'].includes(stored)", $source);
        $this->assertStringContainsString("root.classList.toggle('dark'", $source);
        $this->assertStringContainsString('root.dataset.themePreference', $source);
        $this->assertStringContainsString('root.dataset.theme', $source);
        $this->assertStringContainsString('<meta name="color-scheme" content="light dark">', $source);
    }

    public function test_public_and_dashboard_layouts_include_theme_toggle(): void
    {
        $publicLayout = file_get_contents(resource_path('js/Layouts/PublicLayout.jsx'));
        $dashboardLayout = file_get_contents(resource_path('js/Layouts/DashboardLayout.jsx'));

        $this->assertStringContainsString("import ThemeToggle from '../Components/Theme/ThemeToggle';", $publicLayout);
        $this->assertStringContainsString('<ThemeToggle', $publicLayout);
        $this->assertStringContainsString("import ThemeToggle from '../Components/Theme/ThemeToggle';", $dashboardLayout);
        $this->assertStringContainsString('<ThemeToggle', $dashboardLayout);
    }

    public function test_public_layout_uses_single_theme_toggle_and_compact_mobile_menu(): void
    {
        $publicLayout = file_get_contents(resource_path('js/Layouts/PublicLayout.jsx'));

        $this->assertSame(1, substr_count($publicLayout, '<ThemeToggle'));
        $this->assertStringContainsString('public-mobile-menu', $publicLayout);
        $this->assertStringContainsString('data-testid="public-mobile-menu"', $publicLayout);
        $this->assertStringContainsString('aria-expanded={isMobileMenuOpen}', $publicLayout);
        $this->assertStringContainsString('Меню', $publicLayout);
        $this->assertStringContainsString('hidden items-center gap-1 lg:flex', $publicLayout);
        $this->assertStringContainsString('lg:hidden', $publicLayout);
        $this->assertStringNotContainsString('hidden items-center gap-1 md:flex', $publicLayout);
        $this->assertStringNotContainsString('md:hidden', $publicLayout);
        $this->assertStringNotContainsString('overflow-x-auto', $publicLayout);
    }

    public function test_theme_toggle_contains_accessible_russian_options(): void
    {
        $toggleSource = file_get_contents(resource_path('js/Components/Theme/ThemeToggle.jsx'));
        $providerSource = file_get_contents(resource_path('js/Components/Theme/ThemeProvider.jsx'));

        $this->assertStringContainsString('aria-label', $toggleSource);
        $this->assertStringContainsString('data-testid="theme-toggle"', $toggleSource);
        $this->assertStringContainsString('data-testid="theme-toggle-select"', $toggleSource);
        $this->assertStringContainsString('Тема оформления', $toggleSource);
        $this->assertStringContainsString('Светлая', $providerSource);
        $this->assertStringContainsString('Темная', $providerSource);
        $this->assertStringNotContainsString('Как в системе', $providerSource);
        $this->assertStringNotContainsString('system:', $providerSource);
        $this->assertStringNotContainsString('matchMedia', $providerSource);
        $this->assertStringContainsString('resolvedTheme', $providerSource);
        $this->assertStringContainsString('setTheme', $providerSource);
    }

    public function test_react_app_wraps_pages_in_theme_provider(): void
    {
        $source = file_get_contents(resource_path('js/app.js'));

        $this->assertStringContainsString("import { ThemeProvider } from './Components/Theme/ThemeProvider';", $source);
        $this->assertStringContainsString('createElement(ThemeProvider', $source);
    }

    public function test_tailwind_dark_mode_is_class_based(): void
    {
        $source = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('@custom-variant dark', $source);
        $this->assertStringContainsString('html.dark', $source);
        $this->assertStringContainsString('.dark input', $source);
        $this->assertStringContainsString('.dark .bg-white', $source);
    }

    public function test_login_and_beta_access_pages_render_with_theme_toggle(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/Login'));

        config([
            'beta.enabled' => true,
            'beta.password' => 'test12345',
            'beta.cookie_name' => 'taskora_beta_access',
        ]);

        $this->get('/beta-access')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/BetaAccess'));

        $betaAccessSource = file_get_contents(resource_path('js/Pages/Auth/BetaAccess.jsx'));

        $this->assertStringContainsString("import ThemeToggle from '../../Components/Theme/ThemeToggle';", $betaAccessSource);
        $this->assertStringContainsString('<ThemeToggle', $betaAccessSource);
    }

    public function test_admin_dashboard_renders_without_theme_backend_props(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Dashboards/Admin'));
    }
}
