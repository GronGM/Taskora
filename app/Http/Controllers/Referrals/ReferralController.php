<?php

namespace App\Http\Controllers\Referrals;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public const COOKIE_NAME = 'taskora_ref';

    public const COOKIE_MINUTES = 60 * 24 * 30;

    public function redirect(Request $request, string $code): RedirectResponse
    {
        $referrer = User::query()
            ->where('referral_code', strtolower($code))
            ->where('status', '!=', 'blocked')
            ->first();

        $response = redirect()->route('register');

        if ($referrer && $request->user()?->id !== $referrer->id) {
            $response->withCookie(cookie(self::COOKIE_NAME, $referrer->referral_code, self::COOKIE_MINUTES));
        }

        return $response;
    }

    public function index(Request $request): Response
    {
        $user = $request->user();

        $referrals = $user->referrals()
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (User $referral): array => [
                'id' => $referral->id,
                'name' => $referral->name,
                'role' => $referral->role === User::ROLE_PERFORMER ? 'Исполнитель' : 'Заказчик',
                'registered_at' => $referral->created_at?->format('d.m.Y'),
            ]);

        return Inertia::render('Referrals/Index', [
            'referralUrl' => route('referral.redirect', $user->referral_code),
            'referrals' => $referrals,
            'referralsCount' => $user->referrals()->count(),
        ]);
    }
}
