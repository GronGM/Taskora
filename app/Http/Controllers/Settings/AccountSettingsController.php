<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
                'avatar_url' => $request->user()->accountAvatarUrl(),
            ],
        ]);
    }

    public function update(UpdateAccountRequest $request): RedirectResponse
    {
        $request->user()->update($request->validated());

        return redirect()->route('settings.edit')->with('success', 'Данные аккаунта обновлены.');
    }

    public function updateAvatar(Request $request): RedirectResponse
    {
        $request->validate([
            'avatar' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'avatar.required' => 'Выберите изображение.',
            'avatar.mimes' => 'Подойдут JPG, PNG или WebP.',
            'avatar.max' => 'Файл не должен превышать 5 МБ.',
        ]);

        $user = $request->user();
        $oldPath = $user->avatar_path;

        $user->update([
            'avatar_path' => $request->file('avatar')->store("avatars/{$user->id}", 'public'),
        ]);

        if ($oldPath && $oldPath !== $user->avatar_path) {
            Storage::disk('public')->delete($oldPath);
        }

        return redirect()->route('settings.edit')->with('success', 'Аватар обновлен.');
    }

    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return redirect()->route('settings.edit')->with('success', 'Пароль изменен.');
    }
}
