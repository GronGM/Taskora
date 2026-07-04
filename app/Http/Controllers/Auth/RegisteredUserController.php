<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Referrals\ReferralController;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $referrer = null;
        $referralCode = (string) $request->cookie(ReferralController::COOKIE_NAME, '');

        if ($referralCode !== '') {
            $referrer = User::query()
                ->where('referral_code', $referralCode)
                ->where('status', '!=', 'blocked')
                ->first();
        }

        $user = User::create([
            ...$request->validated(),
            'referred_by_id' => $referrer?->id,
        ]);

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->to($user->dashboardPath());
    }
}
