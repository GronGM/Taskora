<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class ForgotPasswordController extends Controller
{
    public const STATUS_MESSAGE = 'Если аккаунт с такой почтой существует, мы отправили ссылку для сброса пароля.';

    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'mailLogNotice' => $this->shouldShowMailLogNotice(),
        ]);
    }

    public function store(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink($request->only('email'));

        return back()->with('status', self::STATUS_MESSAGE);
    }

    private function shouldShowMailLogNotice(): bool
    {
        return in_array(config('app.env'), ['local', 'staging'], true)
            && config('mail.default') === 'log';
    }
}
