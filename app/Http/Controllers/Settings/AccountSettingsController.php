<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class AccountSettingsController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('Settings/Index', [
            'account' => [
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role_label' => $request->user()->isPerformer() ? 'Исполнитель' : 'Заказчик',
            ],
        ]);
    }

    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('settings.edit')->with('success', 'Данные аккаунта обновлены.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return redirect()->route('settings.edit')->with('success', 'Пароль изменен.');
    }
}
