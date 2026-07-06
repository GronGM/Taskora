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

    public function test_user_can_upload_avatar_and_it_appears_in_shared_props(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Cache::flush();
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($user)
            ->post(route('settings.avatar'), [
                'avatar' => \Illuminate\Http\UploadedFile::fake()->image('me.png', 300, 300),
            ])
            ->assertRedirect(route('settings.edit'));

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        \Illuminate\Support\Facades\Storage::disk('public')->assertExists($user->avatar_path);

        $this->actingAs($user)
            ->get(route('settings.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('account.avatar_url', fn ($url) => str_contains((string) $url, $user->avatar_path)));
    }

    public function test_avatar_rejects_non_images(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_CUSTOMER]);

        $this->actingAs($user)
            ->post(route('settings.avatar'), [
                'avatar' => \Illuminate\Http\UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('avatar');
    }

    public function test_wallet_shared_props_for_performer_and_customer(): void
    {
        \Illuminate\Support\Facades\Cache::flush();
        $customer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        $performer = User::factory()->create(['role' => User::ROLE_PERFORMER]);

        \App\Models\Order::factory()->for($customer, 'customer')->for($performer, 'performer')->inProgress()->create(['price' => 4000]);

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('account.wallet.total', 4000)
                ->where('account.wallet.rows.0.label', 'Зарезервировано в заказах'));

        \Illuminate\Support\Facades\Cache::flush();

        $this->actingAs($performer)
            ->get(route('performer.dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('account.wallet.rows.0.label', 'Доступно к выводу')
                ->has('account.wallet.rows.1.amount'));
    }

    public function test_guest_cannot_open_settings(): void
    {
        $this->get(route('settings.edit'))->assertRedirect('/login');
    }
}
