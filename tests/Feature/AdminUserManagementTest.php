<?php

namespace Tests\Feature;

use App\Models\BetaFeedback;
use App\Models\Order;
use App\Models\PerformerProfile;
use App\Models\Service;
use App\Models\Task;
use App\Models\User;
use App\Models\UserAdminEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_users_admin_page(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER, ['email' => 'visible@taskora.local']);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin/users')
            ->assertOk();

        $this->assertSame('Admin/Users/Index', $response->inertiaPage()['component']);
        $this->assertTrue(collect($response->inertiaProps('users.data'))->pluck('email')->contains($target->email));
    }

    public function test_moderator_cannot_see_users_admin_page(): void
    {
        $this->actingAs($this->user(User::ROLE_MODERATOR))
            ->get('/admin/users')
            ->assertForbidden();
    }

    #[DataProvider('nonAdminRoles')]
    public function test_customer_and_performer_cannot_see_users_admin_page(string $role): void
    {
        $this->actingAs($this->user($role))
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_guest_cannot_see_users_admin_page(): void
    {
        $this->get('/admin/users')->assertRedirect('/login');
    }

    public function test_admin_can_search_user_by_email(): void
    {
        $matched = $this->user(User::ROLE_CUSTOMER, ['email' => 'search-match@taskora.local']);
        $this->user(User::ROLE_CUSTOMER, ['email' => 'other-user@taskora.local']);

        $emails = collect($this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin/users?q=search-match')
            ->assertOk()
            ->inertiaProps('users.data'))
            ->pluck('email');

        $this->assertTrue($emails->contains($matched->email));
        $this->assertFalse($emails->contains('other-user@taskora.local'));
    }

    public function test_admin_can_filter_users_by_role(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER, ['email' => 'performer-filter@taskora.local']);
        $this->user(User::ROLE_CUSTOMER, ['email' => 'customer-filter@taskora.local']);

        $roles = collect($this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin/users?role=performer')
            ->assertOk()
            ->inertiaProps('users.data'))
            ->pluck('role');

        $this->assertTrue($roles->contains($performer->role));
        $this->assertFalse($roles->contains(User::ROLE_CUSTOMER));
    }

    public function test_admin_can_filter_users_by_status(): void
    {
        $blocked = $this->user(User::ROLE_CUSTOMER, [
            'email' => 'blocked-filter@taskora.local',
            'status' => User::STATUS_BLOCKED,
        ]);
        $this->user(User::ROLE_CUSTOMER, ['email' => 'active-filter@taskora.local']);

        $emails = collect($this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin/users?status=blocked')
            ->assertOk()
            ->inertiaProps('users.data'))
            ->pluck('email');

        $this->assertTrue($emails->contains($blocked->email));
        $this->assertFalse($emails->contains('active-filter@taskora.local'));
    }

    public function test_admin_can_filter_users_with_performer_profile(): void
    {
        $performer = $this->user(User::ROLE_PERFORMER, ['email' => 'profile-filter@taskora.local']);
        PerformerProfile::factory()->for($performer)->create();
        $this->user(User::ROLE_PERFORMER, ['email' => 'without-profile@taskora.local']);

        $emails = collect($this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin/users?has_performer_profile=yes')
            ->assertOk()
            ->inertiaProps('users.data'))
            ->pluck('email');

        $this->assertTrue($emails->contains($performer->email));
        $this->assertFalse($emails->contains('without-profile@taskora.local'));
    }

    public function test_admin_can_filter_users_with_orders(): void
    {
        $customer = $this->user(User::ROLE_CUSTOMER, ['email' => 'orders-filter@taskora.local']);
        Order::factory()->create(['customer_id' => $customer->id]);
        $this->user(User::ROLE_CUSTOMER, ['email' => 'without-orders@taskora.local']);

        $emails = collect($this->actingAs($this->user(User::ROLE_ADMIN))
            ->get('/admin/users?has_orders=yes')
            ->assertOk()
            ->inertiaProps('users.data'))
            ->pluck('email');

        $this->assertTrue($emails->contains($customer->email));
        $this->assertFalse($emails->contains('without-orders@taskora.local'));
    }

    public function test_admin_sees_user_page(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER, ['email' => 'detail@taskora.local']);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.users.show', $target))
            ->assertOk();

        $this->assertSame('Admin/Users/Show', $response->inertiaPage()['component']);
        $this->assertSame($target->email, $response->inertiaProps('user.email'));
    }

    public function test_user_page_does_not_contain_password_hash(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.users.show', $target))
            ->assertOk();

        $this->assertStringNotContainsString($target->password, $response->getContent());
    }

    public function test_user_page_does_not_contain_remember_token(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER, ['remember_token' => 'secret-remember-token']);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.users.show', $target))
            ->assertOk();

        $this->assertStringNotContainsString('secret-remember-token', $response->getContent());
        $this->assertStringNotContainsString('remember_token', $response->getContent());
    }

    public function test_admin_user_react_pages_do_not_render_secret_field_names(): void
    {
        $source = collect([
            resource_path('js/Pages/Admin/Users/Index.jsx'),
            resource_path('js/Pages/Admin/Users/Show.jsx'),
            resource_path('js/Pages/Admin/Users/Edit.jsx'),
        ])->map(fn (string $path): string => file_get_contents($path))->implode("\n");

        $this->assertStringNotContainsString('remember_token', $source);
    }

    public function test_last_login_ip_is_masked_on_user_page(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER, ['last_login_ip' => '192.168.10.55']);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.users.show', $target))
            ->assertOk();

        $this->assertSame('192.168.*.*', $response->inertiaProps('user.last_login_ip'));
        $this->assertStringNotContainsString('192.168.10.55', $response->getContent());
    }

    public function test_admin_can_update_name_and_email(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->patch(route('admin.users.update', $target), $this->validUserPayload($target, [
                'name' => 'Updated Customer',
                'email' => 'updated-customer@taskora.local',
            ]))
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'name' => 'Updated Customer',
            'email' => 'updated-customer@taskora.local',
        ]);
    }

    public function test_admin_can_change_role_customer_to_performer(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->patch(route('admin.users.update', $target), $this->validUserPayload($target, [
                'role' => User::ROLE_PERFORMER,
            ]))
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertSame(User::ROLE_PERFORMER, $target->refresh()->role);
    }

    public function test_role_change_creates_user_admin_event(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $target = $this->user(User::ROLE_CUSTOMER);

        $this->actingAs($admin)
            ->patch(route('admin.users.update', $target), $this->validUserPayload($target, [
                'role' => User::ROLE_PERFORMER,
            ]))
            ->assertRedirect(route('admin.users.show', $target));

        $this->assertDatabaseHas('user_admin_events', [
            'target_user_id' => $target->id,
            'actor_user_id' => $admin->id,
            'type' => UserAdminEvent::TYPE_ROLE_CHANGED,
        ]);
    }

    public function test_admin_cannot_remove_admin_role_from_last_active_admin(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from(route('admin.users.edit', $admin))
            ->patch(route('admin.users.update', $admin), $this->validUserPayload($admin, [
                'role' => User::ROLE_CUSTOMER,
            ]))
            ->assertRedirect(route('admin.users.edit', $admin))
            ->assertSessionHasErrors('role');
    }

    public function test_admin_cannot_block_self(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->from(route('admin.users.show', $admin))
            ->post(route('admin.users.block', $admin), ['reason' => 'Нарушение правил тестовой платформы.'])
            ->assertRedirect(route('admin.users.show', $admin))
            ->assertSessionHasErrors('reason');
    }

    public function test_admin_can_block_regular_user(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->post(route('admin.users.block', $target), ['reason' => 'Многократное нарушение правил тестовой платформы.'])
            ->assertRedirect();

        $target->refresh();

        $this->assertSame(User::STATUS_BLOCKED, $target->status);
        $this->assertNotNull($target->blocked_at);
        $this->assertSame('Многократное нарушение правил тестовой платформы.', $target->block_reason);
    }

    public function test_blocked_user_cannot_login(): void
    {
        $user = $this->user(User::ROLE_CUSTOMER, [
            'email' => 'blocked-login@taskora.local',
            'status' => User::STATUS_BLOCKED,
        ]);

        $this->from('/login')
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/login')
            ->assertSessionHasErrors([
                'auth' => 'Аккаунт заблокирован. Обратитесь к администратору.',
            ]);

        $this->assertGuest();
    }

    public function test_authenticated_blocked_user_is_logged_out_on_next_request(): void
    {
        $user = $this->user(User::ROLE_CUSTOMER, ['status' => User::STATUS_BLOCKED]);

        $this->actingAs($user)
            ->get('/customer/dashboard')
            ->assertRedirect('/login')
            ->assertSessionHasErrors('auth');

        $this->assertGuest();
    }

    public function test_admin_can_unblock_user(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER, [
            'status' => User::STATUS_BLOCKED,
            'blocked_at' => now(),
            'block_reason' => 'Тестовая блокировка.',
        ]);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->post(route('admin.users.unblock', $target))
            ->assertRedirect();

        $target->refresh();

        $this->assertSame(User::STATUS_ACTIVE, $target->status);
        $this->assertNull($target->blocked_at);
        $this->assertNull($target->block_reason);
    }

    public function test_unblocked_user_can_login_again(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER, [
            'email' => 'unblocked-login@taskora.local',
            'status' => User::STATUS_BLOCKED,
            'blocked_at' => now(),
            'block_reason' => 'Тестовая блокировка.',
        ]);

        $admin = $this->user(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->post(route('admin.users.unblock', $target))
            ->assertRedirect();

        auth()->logout();

        $this->post('/login', [
            'email' => $target->email,
            'password' => 'password',
        ])->assertRedirect('/customer/dashboard');

        $this->assertAuthenticatedAs($target->refresh());
    }

    public function test_block_creates_user_admin_event(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $target = $this->user(User::ROLE_CUSTOMER);

        $this->actingAs($admin)
            ->post(route('admin.users.block', $target), ['reason' => 'Многократное нарушение правил тестовой платформы.'])
            ->assertRedirect();

        $this->assertDatabaseHas('user_admin_events', [
            'target_user_id' => $target->id,
            'actor_user_id' => $admin->id,
            'type' => UserAdminEvent::TYPE_USER_BLOCKED,
        ]);
    }

    public function test_unblock_creates_user_admin_event(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $target = $this->user(User::ROLE_CUSTOMER, ['status' => User::STATUS_BLOCKED]);

        $this->actingAs($admin)
            ->post(route('admin.users.unblock', $target))
            ->assertRedirect();

        $this->assertDatabaseHas('user_admin_events', [
            'target_user_id' => $target->id,
            'actor_user_id' => $admin->id,
            'type' => UserAdminEvent::TYPE_USER_UNBLOCKED,
        ]);
    }

    public function test_admin_can_update_admin_note(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->patch(route('admin.users.admin-note', $target), [
                'admin_note' => 'Проверить историю споров перед публичным запуском.',
            ])
            ->assertRedirect();

        $this->assertSame('Проверить историю споров перед публичным запуском.', $target->refresh()->admin_note);
    }

    public function test_admin_note_update_creates_user_admin_event(): void
    {
        $admin = $this->user(User::ROLE_ADMIN);
        $target = $this->user(User::ROLE_CUSTOMER);

        $this->actingAs($admin)
            ->patch(route('admin.users.admin-note', $target), [
                'admin_note' => 'Внутренняя заметка для администраторов.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('user_admin_events', [
            'target_user_id' => $target->id,
            'actor_user_id' => $admin->id,
            'type' => UserAdminEvent::TYPE_ADMIN_NOTE_UPDATED,
        ]);
    }

    public function test_last_login_at_and_ip_are_updated_on_successful_login(): void
    {
        $user = $this->user(User::ROLE_CUSTOMER, ['email' => 'login-meta@taskora.local']);

        $this->withServerVariables(['REMOTE_ADDR' => '10.20.30.40'])
            ->post('/login', [
                'email' => $user->email,
                'password' => 'password',
            ])
            ->assertRedirect('/customer/dashboard');

        $user->refresh();

        $this->assertNotNull($user->last_login_at);
        $this->assertSame('10.20.30.40', $user->last_login_ip);
    }

    public function test_invalid_email_validation_is_russian(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->from(route('admin.users.edit', $target))
            ->patch(route('admin.users.update', $target), $this->validUserPayload($target, [
                'email' => 'not-an-email',
            ]))
            ->assertRedirect(route('admin.users.edit', $target))
            ->assertSessionHasErrors([
                'email' => 'Введите корректную почту.',
            ]);
    }

    public function test_short_block_reason_fails_validation(): void
    {
        $target = $this->user(User::ROLE_CUSTOMER);

        $this->actingAs($this->user(User::ROLE_ADMIN))
            ->from(route('admin.users.show', $target))
            ->post(route('admin.users.block', $target), ['reason' => 'коротко'])
            ->assertRedirect(route('admin.users.show', $target))
            ->assertSessionHasErrors('reason');
    }

    public function test_user_page_shows_related_orders_services_tasks_when_present(): void
    {
        $target = $this->user(User::ROLE_PERFORMER);
        PerformerProfile::factory()->for($target)->create();
        $order = Order::factory()->create(['performer_id' => $target->id, 'title' => 'Связанный заказ']);
        $task = Task::factory()->create(['user_id' => $target->id, 'title' => 'Связанное задание']);
        $service = Service::factory()->create(['user_id' => $target->id, 'title' => 'Связанная услуга']);
        BetaFeedback::factory()->create(['user_id' => $target->id, 'title' => 'Связанный beta feedback']);

        $response = $this->actingAs($this->user(User::ROLE_ADMIN))
            ->get(route('admin.users.show', $target))
            ->assertOk();

        $this->assertSame($order->title, data_get($response->inertiaProps('related.performer_orders'), '0.title'));
        $this->assertSame($task->title, data_get($response->inertiaProps('related.tasks'), '0.title'));
        $this->assertSame($service->title, data_get($response->inertiaProps('related.services'), '0.title'));
        $this->assertSame('Связанный beta feedback', data_get($response->inertiaProps('related.beta_feedback'), '0.title'));
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admins_cannot_post_block_or_unblock(string $role): void
    {
        $target = $this->user(User::ROLE_CUSTOMER);

        $this->actingAs($this->user($role))
            ->post(route('admin.users.block', $target), ['reason' => 'Многократное нарушение правил тестовой платформы.'])
            ->assertForbidden();

        $this->actingAs($this->user($role))
            ->post(route('admin.users.unblock', $target))
            ->assertForbidden();
    }

    public static function nonAdminRoles(): array
    {
        return [
            'moderator' => [User::ROLE_MODERATOR],
            'customer' => [User::ROLE_CUSTOMER],
            'performer' => [User::ROLE_PERFORMER],
        ];
    }

    private function user(string $role, array $overrides = []): User
    {
        return User::factory()->create([
            'role' => $role,
            ...$overrides,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validUserPayload(User $user, array $overrides = []): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'admin_note' => $user->admin_note,
            ...$overrides,
        ];
    }
}
