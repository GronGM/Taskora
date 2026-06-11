<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AdminMailSettingsController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/MailSettings/Index', [
            'warning' => 'На staging реальная почта может быть отключена. Для восстановления пароля используется log-mail или SMTP sandbox.',
            'hint' => 'Секреты не отображаются. Реальные SMTP-пароли хранятся только в .env.',
            'settings' => [
                'app_env' => config('app.env', app()->environment()),
                'mail_mailer' => config('mail.default', 'log'),
                'mail_host' => $this->presence(config('mail.mailers.smtp.host')),
                'mail_port' => $this->display(config('mail.mailers.smtp.port')),
                'mail_username' => $this->presence(config('mail.mailers.smtp.username')),
                'mail_password' => $this->presence(config('mail.mailers.smtp.password')),
                'mail_from_address' => $this->display(config('mail.from.address')),
                'mail_from_name' => $this->display(config('mail.from.name')),
            ],
        ]);
    }

    private function presence(mixed $value): string
    {
        return filled($value) ? 'Задан' : 'Не задан';
    }

    private function display(mixed $value): string
    {
        return filled($value) ? (string) $value : 'Не задан';
    }
}
