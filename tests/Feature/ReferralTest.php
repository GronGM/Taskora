<?php

namespace Tests\Feature;

use App\Http\Controllers\Referrals\ReferralController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_new_user_gets_referral_code(): void
    {
        $user = User::factory()->create();

        $this->assertNotNull($user->referral_code);
        $this->assertSame(10, strlen($user->referral_code));
    }

    public function test_referral_link_sets_cookie_and_redirects_to_register(): void
    {
        $referrer = User::factory()->create();

        $this->get(route('referral.redirect', $referrer->referral_code))
            ->assertRedirect(route('register'))
            ->assertCookie(ReferralController::COOKIE_NAME, $referrer->referral_code, false);
    }

    public function test_unknown_referral_code_does_not_set_cookie(): void
    {
        $this->get(route('referral.redirect', 'nonexistent'))
            ->assertRedirect(route('register'))
            ->assertCookieMissing(ReferralController::COOKIE_NAME);
    }

    public function test_registration_binds_referrer_from_cookie(): void
    {
        $referrer = User::factory()->create();

        $this->withUnencryptedCookie(ReferralController::COOKIE_NAME, $referrer->referral_code)
            ->post(route('register.store'), [
                'name' => 'Новый пользователь',
                'email' => 'invited@example.com',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => User::ROLE_CUSTOMER,
            ])
            ->assertRedirect();

        $invited = User::query()->where('email', 'invited@example.com')->firstOrFail();
        $this->assertSame($referrer->id, $invited->referred_by_id);
    }

    public function test_registration_without_cookie_has_no_referrer(): void
    {
        $this->post(route('register.store'), [
            'name' => 'Обычный пользователь',
            'email' => 'plain@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => User::ROLE_PERFORMER,
        ])->assertRedirect();

        $this->assertNull(User::query()->where('email', 'plain@example.com')->firstOrFail()->referred_by_id);
    }

    public function test_referrals_page_shows_invited_users(): void
    {
        $referrer = User::factory()->create(['role' => User::ROLE_CUSTOMER]);
        User::factory()->count(2)->create(['referred_by_id' => $referrer->id]);

        $this->actingAs($referrer)
            ->get(route('referrals.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Referrals/Index')
                ->where('referralsCount', 2)
                ->has('referrals', 2)
                ->where('referralUrl', route('referral.redirect', $referrer->referral_code)));
    }

    public function test_guest_cannot_view_referrals_page(): void
    {
        $this->get(route('referrals.index'))->assertRedirect('/login');
    }
}
