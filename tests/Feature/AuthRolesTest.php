<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AuthRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_register_and_is_redirected_to_customer_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Новый заказчик',
            'email' => 'new-customer@taskora.local',
            'role' => User::ROLE_CUSTOMER,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/customer/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new-customer@taskora.local',
            'role' => User::ROLE_CUSTOMER,
        ]);
    }

    public function test_performer_can_register_and_is_redirected_to_performer_dashboard(): void
    {
        $response = $this->post('/register', [
            'name' => 'Новый исполнитель',
            'email' => 'new-performer@taskora.local',
            'role' => User::ROLE_PERFORMER,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/performer/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'new-performer@taskora.local',
            'role' => User::ROLE_PERFORMER,
        ]);
    }

    #[DataProvider('privilegedRoles')]
    public function test_public_registration_cannot_select_privileged_roles(string $role): void
    {
        $response = $this->from('/register')->post('/register', [
            'name' => 'Закрытая роль',
            'email' => "{$role}@public-taskora.local",
            'role' => $role,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertRedirect('/register');
        $response->assertSessionHasErrors('role');
        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => "{$role}@public-taskora.local",
        ]);
    }

    #[DataProvider('localUsers')]
    public function test_seeded_local_users_login_to_their_dashboards(string $email, string $dashboard): void
    {
        $this->seed();

        $response = $this->post('/login', [
            'email' => $email,
            'password' => 'password',
        ]);

        $response->assertRedirect($dashboard);
        $this->assertAuthenticated();
    }

    #[DataProvider('roleDashboards')]
    public function test_user_can_access_only_own_role_dashboard(string $role, string $allowedDashboard): void
    {
        $user = User::factory()->create(['role' => $role]);

        $this->actingAs($user)->get($allowedDashboard)->assertOk();

        foreach ($this->dashboardPathsExcept($allowedDashboard) as $blockedDashboard) {
            $this->actingAs($user)->get($blockedDashboard)->assertForbidden();
        }
    }

    public function test_dashboard_redirect_uses_user_role(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($user)->get('/dashboard')->assertRedirect('/admin/dashboard');
    }

    public static function privilegedRoles(): array
    {
        return [
            'moderator' => [User::ROLE_MODERATOR],
            'admin' => [User::ROLE_ADMIN],
        ];
    }

    public static function localUsers(): array
    {
        return [
            'customer' => ['customer@taskora.local', '/customer/dashboard'],
            'performer' => ['performer@taskora.local', '/performer/dashboard'],
            'moderator' => ['moderator@taskora.local', '/moderator/dashboard'],
            'admin' => ['admin@taskora.local', '/admin/dashboard'],
        ];
    }

    public static function roleDashboards(): array
    {
        return [
            'customer' => [User::ROLE_CUSTOMER, '/customer/dashboard'],
            'performer' => [User::ROLE_PERFORMER, '/performer/dashboard'],
            'moderator' => [User::ROLE_MODERATOR, '/moderator/dashboard'],
            'admin' => [User::ROLE_ADMIN, '/admin/dashboard'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function dashboardPathsExcept(string $allowedDashboard): array
    {
        return array_values(array_filter(
            ['/customer/dashboard', '/performer/dashboard', '/moderator/dashboard', '/admin/dashboard'],
            fn (string $dashboard): bool => $dashboard !== $allowedDashboard,
        ));
    }
}
