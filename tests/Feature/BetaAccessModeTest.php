<?php

namespace Tests\Feature;

use App\Support\BetaAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetaAccessModeTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_works_normally_when_beta_access_is_disabled(): void
    {
        $this->disableBetaAccess();

        $response = $this->get('/');

        $response->assertOk();
        $this->assertSame('Home', $response->inertiaPage()['component']);
    }

    public function test_beta_access_redirects_public_pages_to_password_gate(): void
    {
        $this->enableBetaAccess();

        $this->get('/catalog')
            ->assertRedirect(route('beta-access.show'));
    }

    public function test_beta_password_page_uses_public_russian_copy_without_secret(): void
    {
        $this->enableBetaAccess('test12345');

        $this->get('/beta-access')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Auth/BetaAccess'))
            ->assertDontSee('test12345', false);

        $pageSource = file_get_contents(resource_path('js/Pages/Auth/BetaAccess.jsx'));

        $this->assertStringContainsString('Закрытое тестирование Таскоры', $pageSource);
        $this->assertStringContainsString('Сайт доступен только участникам тестирования', $pageSource);
        $this->assertStringContainsString('Войти', $pageSource);
    }

    public function test_wrong_beta_password_keeps_access_closed(): void
    {
        $this->enableBetaAccess();

        $this->from('/beta-access')
            ->post(route('beta-access.store'), ['password' => 'wrong-password'])
            ->assertRedirect('/beta-access')
            ->assertSessionHasErrors('password');

        $this->get('/')->assertRedirect(route('beta-access.show'));
    }

    public function test_correct_beta_password_grants_session_access_and_redirects_to_intended_page(): void
    {
        $this->enableBetaAccess('test12345');

        $this->get('/tasks')->assertRedirect(route('beta-access.show'));

        $this->post(route('beta-access.store'), ['password' => 'test12345'])
            ->assertRedirect(url('/tasks'));

        $this->assertTrue(session(BetaAccess::SESSION_KEY));
        $this->get('/tasks')->assertOk();
    }

    public function test_beta_cookie_grants_access_after_password_login(): void
    {
        $this->enableBetaAccess('test12345');

        $response = $this->post(route('beta-access.store'), ['password' => 'test12345']);
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($cookie) => $cookie->getName() === BetaAccess::cookieName());

        $this->assertNotNull($cookie);
        $this->withSession([])
            ->withCookie($cookie->getName(), $cookie->getValue())
            ->get('/catalog')
            ->assertOk();
    }

    public function test_assets_and_favicon_are_not_redirected_by_beta_gate(): void
    {
        $this->enableBetaAccess();

        $this->get('/build/assets/missing.css')->assertNotFound();
        $this->get('/favicon.ico')->assertNotFound();
    }

    public function test_app_layout_links_favicon_assets(): void
    {
        $this->disableBetaAccess();

        $this->assertFileExists(public_path('favicon.ico'));
        $this->assertGreaterThan(0, filesize(public_path('favicon.ico')));
        $this->assertFileExists(public_path('favicon-32x32.png'));
        $this->assertGreaterThan(0, filesize(public_path('favicon-32x32.png')));
        $this->assertFileExists(public_path('favicon-192x192.png'));
        $this->assertGreaterThan(0, filesize(public_path('favicon-192x192.png')));
        $this->assertFileExists(public_path('apple-touch-icon.png'));
        $this->assertGreaterThan(0, filesize(public_path('apple-touch-icon.png')));
        $this->assertContains($this->pngColorType(public_path('favicon-32x32.png')), [4, 6]);
        $this->assertContains($this->pngColorType(public_path('favicon-192x192.png')), [4, 6]);
        $this->assertSame([16, 32, 48], $this->icoSizes(public_path('favicon.ico')));

        $this->get('/login')
            ->assertOk()
            ->assertSee('favicon.ico?v=2', false)
            ->assertSee('favicon-32x32.png?v=2', false)
            ->assertSee('favicon-192x192.png?v=2', false)
            ->assertSee('apple-touch-icon.png?v=2', false)
            ->assertSee('rel="apple-touch-icon"', false);
    }

    public function test_laravel_404_pages_are_not_replaced_by_beta_gate(): void
    {
        $this->enableBetaAccess();

        $this->get('/missing-beta-page')->assertNotFound();
    }

    public function test_test_mode_banner_is_shared_for_local_staging_and_beta_modes(): void
    {
        $this->disableBetaAccess();
        config(['app.env' => 'local']);

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('testMode.enabled', true)
                ->where('testMode.message', BetaAccess::BANNER_TEXT)
            );

        config(['app.env' => 'staging']);

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('testMode.enabled', true));

        config(['app.env' => 'production']);
        $this->enableBetaAccess();

        $this->withSession([BetaAccess::SESSION_KEY => true])
            ->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('testMode.enabled', true));
    }

    public function test_test_mode_banner_is_hidden_in_production_without_beta(): void
    {
        $this->disableBetaAccess();
        config(['app.env' => 'production']);

        $this->get('/login')
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('testMode.enabled', false));
    }

    public function test_noindex_meta_is_added_outside_production_and_for_beta(): void
    {
        $this->disableBetaAccess();
        config(['app.env' => 'staging']);

        $this->get('/login')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);

        config(['app.env' => 'production']);
        $this->enableBetaAccess();

        $this->withSession([BetaAccess::SESSION_KEY => true])
            ->get('/login')
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex,nofollow">', false);
    }

    public function test_noindex_meta_is_not_added_for_public_production_without_beta(): void
    {
        $this->disableBetaAccess();
        config(['app.env' => 'production']);

        $this->get('/login')
            ->assertOk()
            ->assertDontSee('noindex,nofollow', false);
    }

    public function test_robots_txt_blocks_test_modes_and_allows_public_production(): void
    {
        $this->disableBetaAccess();
        config(['app.env' => 'local']);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee("User-agent: *\nDisallow: /", false);

        config(['app.env' => 'production']);

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee("User-agent: *\nAllow: /", false);

        $this->enableBetaAccess();

        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee("User-agent: *\nDisallow: /", false);
    }

    public function test_testing_access_documentation_exists(): void
    {
        $this->assertFileExists(base_path('docs/testing-access.md'));
    }

    private function enableBetaAccess(string $password = 'test12345'): void
    {
        config([
            'app.debug' => false,
            'beta.enabled' => true,
            'beta.password' => $password,
            'beta.cookie_name' => 'taskora_beta_access',
        ]);
    }

    private function disableBetaAccess(): void
    {
        config([
            'beta.enabled' => false,
            'beta.password' => null,
            'beta.cookie_name' => 'taskora_beta_access',
        ]);
    }

    private function pngColorType(string $path): int
    {
        $bytes = file_get_contents($path, false, null, 0, 26);

        $this->assertIsString($bytes);
        $this->assertStringStartsWith("\x89PNG\r\n\x1A\n", $bytes);

        return ord($bytes[25]);
    }

    /**
     * @return array<int, int>
     */
    private function icoSizes(string $path): array
    {
        $bytes = file_get_contents($path);

        $this->assertIsString($bytes);
        $this->assertGreaterThanOrEqual(6, strlen($bytes));

        $header = unpack('vreserved/vtype/vcount', substr($bytes, 0, 6));

        $this->assertSame(0, $header['reserved']);
        $this->assertSame(1, $header['type']);

        $sizes = [];

        for ($index = 0; $index < $header['count']; $index++) {
            $offset = 6 + ($index * 16);
            $this->assertGreaterThanOrEqual($offset + 16, strlen($bytes));

            $width = ord($bytes[$offset]);
            $sizes[] = $width === 0 ? 256 : $width;
        }

        sort($sizes);

        return $sizes;
    }
}
