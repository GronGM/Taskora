<?php

namespace App\Http\Controllers;

use App\Support\BetaAccess;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class BetaAccessController extends Controller
{
    public function show(Request $request): Response|RedirectResponse
    {
        if (! BetaAccess::enabled() || BetaAccess::hasAccess($request)) {
            return redirect()->intended(route('home'));
        }

        return Inertia::render('Auth/BetaAccess');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! BetaAccess::enabled()) {
            return redirect()->route('home');
        }

        $validated = $request->validate([
            'password' => ['required', 'string', 'max:255'],
        ]);

        if (! BetaAccess::hasPassword() || ! hash_equals((string) config('beta.password'), $validated['password'])) {
            throw ValidationException::withMessages([
                'password' => 'Неверный пароль доступа к тестированию.',
            ]);
        }

        BetaAccess::grant($request);

        return redirect()->intended(route('home'));
    }
}
