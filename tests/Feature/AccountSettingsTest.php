<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AccountSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_performer_can_open_settings(): void
    {
        foreach ([User::ROLE_CUSTOMER, User::ROLE_PERFORMER] as $role) {
            $user = User::factory()->create(['role' => $role]);

            $this->actingAs($user)
                ->get(route('settings.edit'))
                ->assertOk()
                ->assertInertia(fn ($page) => $page
                    ->component('Settings/Index')
                    ->where('account.email', $user->email));
        }
    }

    public function test_user_can_update_name_and_email(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($user)
            ->patch(route('settings.update'), ['name' => 'Новое Имя', 'email' => 'new-email@example.com'])
            ->assertRedirect(route('settings.edit'));

        $user->refresh();
        $this->assertSame('Новое Имя', $user->name);
        $this->assertSame('new-email@example.com', $user->email);
    }

    public function test_email_must_be_unique(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $other = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('settings.update'), ['name' => $user->name, 'email' => $other->email])
            ->assertSessionHasErrors('email');
    }

    public function test_user_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PERFORMER, 'password' => Hash::make('old-password-1')]);

        $this->actingAs($user)
            ->patch(route('settings.password'), [
                'current_password' => 'old-password-1',
                'password' => 'new-password-22',
                'password_confirmation' => 'new-password-22',
            ])
            ->assertRedirect(route('settings.edit'));

        $this->assertTrue(Hash::check('new-password-22', $user->refresh()->password));
    }

    public function test_password_change_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_PERFORMER, 'password' => Hash::make('old-password-1')]);

        $this->actingAs($user)
            ->patch(route('settings.password'), [
                'current_password' => 'wrong-password',
                'password' => 'new-password-22',
                'password_confirmation' => 'new-password-22',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertTrue(Hash::check('old-password-1', $user->refresh()->password));
    }

    public function test_guest_cannot_open_settings(): void
    {
        $this->get(route('settings.edit'))->assertRedirect('/login');
    }
}
