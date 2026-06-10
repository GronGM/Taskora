<?php

namespace Tests\Feature;

use App\Models\BetaFeedback;
use App\Models\User;
use App\Support\BetaAccess;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BetaTestingToolingTest extends TestCase
{
    use RefreshDatabase;

    public function test_beta_testing_page_is_available_in_local(): void
    {
        $this->enableLocalBetaTooling();

        $response = $this->get(route('beta-testing'))->assertOk();

        $this->assertSame('BetaTesting/Index', $response->inertiaPage()['component']);
    }

    public function test_beta_testing_page_is_available_when_beta_access_is_enabled(): void
    {
        $this->enableBetaAccess();
        config(['app.env' => 'production']);

        $response = $this->withSession([BetaAccess::SESSION_KEY => true])
            ->get(route('beta-testing'))
            ->assertOk();

        $this->assertSame('BetaTesting/Index', $response->inertiaPage()['component']);
    }

    public function test_beta_testing_page_is_inaccessible_in_production_without_beta(): void
    {
        $this->disableBetaAccess();
        config(['app.env' => 'production']);

        $this->get(route('beta-testing'))->assertNotFound();
    }

    public function test_beta_testing_page_shows_test_accounts(): void
    {
        $this->enableLocalBetaTooling();

        $response = $this->get(route('beta-testing'))->assertOk();

        $emails = collect($response->inertiaProps('accounts'))->pluck('email');

        $this->assertTrue($emails->contains('customer@taskora.local'));
        $this->assertTrue($emails->contains('performer@taskora.local'));
        $this->assertTrue($emails->contains('moderator@taskora.local'));
        $this->assertTrue($emails->contains('admin@taskora.local'));
    }

    public function test_guest_after_beta_access_can_open_feedback_form(): void
    {
        $this->enableBetaAccess();
        config(['app.env' => 'production']);

        $response = $this->withSession([BetaAccess::SESSION_KEY => true])
            ->get(route('beta-feedback.create'))
            ->assertOk();

        $this->assertSame('BetaFeedback/Create', $response->inertiaPage()['component']);
    }

    public function test_authenticated_user_can_submit_beta_feedback(): void
    {
        $this->enableLocalBetaTooling();
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($user)
            ->post(route('beta-feedback.store'), $this->validFeedbackPayload([
                'role' => User::ROLE_CUSTOMER,
                'title' => 'Клиентская ошибка в каталоге',
            ]))
            ->assertRedirect(route('beta-feedback.create'));

        $this->assertDatabaseHas('beta_feedback', [
            'user_id' => $user->id,
            'role' => User::ROLE_CUSTOMER,
            'title' => 'Клиентская ошибка в каталоге',
            'status' => BetaFeedback::STATUS_OPEN,
        ]);
    }

    public function test_guest_can_submit_beta_feedback(): void
    {
        $this->enableLocalBetaTooling();

        $this->post(route('beta-feedback.store'), $this->validFeedbackPayload([
            'role' => 'guest',
            'title' => 'Гостевое обращение',
        ]))
            ->assertRedirect(route('beta-feedback.create'));

        $this->assertDatabaseHas('beta_feedback', [
            'user_id' => null,
            'role' => 'guest',
            'title' => 'Гостевое обращение',
        ]);
    }

    public function test_feedback_title_is_required(): void
    {
        $this->enableLocalBetaTooling();

        $this->from(route('beta-feedback.create'))
            ->post(route('beta-feedback.store'), $this->validFeedbackPayload(['title' => '']))
            ->assertRedirect(route('beta-feedback.create'))
            ->assertSessionHasErrors('title');
    }

    public function test_feedback_description_is_required(): void
    {
        $this->enableLocalBetaTooling();

        $this->from(route('beta-feedback.create'))
            ->post(route('beta-feedback.store'), $this->validFeedbackPayload(['description' => '']))
            ->assertRedirect(route('beta-feedback.create'))
            ->assertSessionHasErrors('description');
    }

    public function test_feedback_type_is_validated(): void
    {
        $this->enableLocalBetaTooling();

        $this->from(route('beta-feedback.create'))
            ->post(route('beta-feedback.store'), $this->validFeedbackPayload(['type' => 'payment_gateway']))
            ->assertRedirect(route('beta-feedback.create'))
            ->assertSessionHasErrors('type');
    }

    public function test_feedback_severity_is_validated(): void
    {
        $this->enableLocalBetaTooling();

        $this->from(route('beta-feedback.create'))
            ->post(route('beta-feedback.store'), $this->validFeedbackPayload(['severity' => 'blocker']))
            ->assertRedirect(route('beta-feedback.create'))
            ->assertSessionHasErrors('severity');
    }

    public function test_admin_can_see_beta_feedback_queue(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        BetaFeedback::factory()->create(['title' => 'Админ видит обращение']);

        $response = $this->actingAs($admin)
            ->get(route('admin.beta-feedback.index'))
            ->assertOk();

        $this->assertSame('Admin/BetaFeedback/Index', $response->inertiaPage()['component']);
        $this->assertTrue(collect($response->inertiaProps('feedback'))->pluck('title')->contains('Админ видит обращение'));
    }

    public function test_non_admin_roles_cannot_see_beta_feedback_queue(): void
    {
        foreach ([User::ROLE_CUSTOMER, User::ROLE_PERFORMER, User::ROLE_MODERATOR] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))
                ->get(route('admin.beta-feedback.index'))
                ->assertForbidden();
        }
    }

    public function test_admin_can_change_beta_feedback_status(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $feedback = BetaFeedback::factory()->create(['status' => BetaFeedback::STATUS_OPEN]);

        $this->actingAs($admin)
            ->post(route('admin.beta-feedback.status', $feedback), [
                'status' => BetaFeedback::STATUS_RESOLVED,
            ])
            ->assertRedirect(route('admin.beta-feedback.show', $feedback));

        $this->assertDatabaseHas('beta_feedback', [
            'id' => $feedback->id,
            'status' => BetaFeedback::STATUS_RESOLVED,
        ]);
    }

    public function test_test_mode_banner_contains_feedback_link(): void
    {
        $source = file_get_contents(resource_path('js/Components/TestModeBanner.jsx'));

        $this->assertStringContainsString('/beta-feedback/create', $source);
    }

    public function test_beta_testing_checklist_document_exists(): void
    {
        $this->assertFileExists(base_path('docs/beta-testing-checklist.md'));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validFeedbackPayload(array $overrides = []): array
    {
        return [
            'role' => 'guest',
            'page_url' => '/catalog',
            'scenario' => 'Проверка каталога',
            'type' => BetaFeedback::TYPE_BUG,
            'severity' => BetaFeedback::SEVERITY_MEDIUM,
            'title' => 'Кнопка не отвечает',
            'description' => 'Нажал кнопку в тестовом сценарии, но ожидаемое действие не произошло.',
            'browser' => 'Feature test browser',
            'screen_size' => '1280x720',
            ...$overrides,
        ];
    }

    private function enableLocalBetaTooling(): void
    {
        config([
            'app.env' => 'local',
            'beta.enabled' => false,
            'beta.password' => null,
            'beta.cookie_name' => 'taskora_beta_access',
        ]);
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
}
