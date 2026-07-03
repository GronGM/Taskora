<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Email-уведомления
    |--------------------------------------------------------------------------
    |
    | Дублирование ключевых внутренних уведомлений на email пользователя.
    | Локально и на staging используется MAIL_MAILER=log, поэтому письма
    | попадают в лог, а не во внешнюю почту. Список ключевых событий
    | определен в PlatformNotification::EMAIL_EVENT_TYPES.
    |
    */

    'email_enabled' => filter_var(env('TASKORA_EMAIL_NOTIFICATIONS_ENABLED', true), FILTER_VALIDATE_BOOLEAN),
];
