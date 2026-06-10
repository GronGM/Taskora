# Закрытое Тестирование

Этот документ описывает безопасный beta/staging-доступ к Taskora для друзей и первых тестировщиков. Это не production launch.

## Главные Ограничения

- Реальные платежи не подключены.
- Все платежи работают только как заглушка.
- ЮKassa, CloudPayments, Robokassa и другие провайдеры не подключены.
- Не вводить реальные платежные данные.
- Не использовать реальные пароли.
- Не загружать реальные документы, персональные данные, паспортные данные, ИНН, СНИЛС или KYC-документы.
- Не оставлять tunnel включенным без необходимости.
- `APP_DEBUG` должен быть `false`, если ссылку получают другие люди.

## Beta Access Mode

Перед отправкой ссылки тестировщикам включить общий beta-пароль:

```dotenv
BETA_ACCESS_ENABLED=true
BETA_ACCESS_PASSWORD=test12345
BETA_ACCESS_COOKIE_NAME=taskora_beta_access
APP_DEBUG=false
TASKORA_PAYMENTS_MODE=stub
```

Пароль хранится только в `.env`. Не коммитить реальный beta-пароль и не отправлять его в публичные чаты.

Если `BETA_ACCESS_ENABLED=false`, сайт работает как обычная локальная версия.

## Тестовый Баннер И Индексация

В `local`, `staging` или при `BETA_ACCESS_ENABLED=true` интерфейс показывает баннер:

```text
Тестовый режим: реальные платежи и выплаты не подключены. Используйте только тестовые данные.
```

Если `APP_ENV !== production` или `BETA_ACCESS_ENABLED=true`, приложение добавляет:

```html
<meta name="robots" content="noindex,nofollow">
```

`/robots.txt` в тестовом режиме отвечает:

```text
User-agent: *
Disallow: /
```

Тестовую версию нельзя индексировать поисковиками.

## Тестовые Аккаунты

Эти аккаунты создаются сидером только для локального и закрытого тестирования.

| Роль | Email | Пароль | Кабинет |
|---|---|---|---|
| Заказчик | `customer@taskora.local` | `password` | `/customer/dashboard` |
| Исполнитель | `performer@taskora.local` | `password` | `/performer/dashboard` |
| Модератор | `moderator@taskora.local` | `password` | `/moderator/dashboard` |
| Администратор | `admin@taskora.local` | `password` | `/admin/dashboard` |

Не использовать эти пароли для реальных аккаунтов и не вводить реальные платежные данные. Все платежи в текущей версии — заглушка.

## Маршруты Для Тестировщиков

- `/beta-testing` — правила, тестовые аккаунты и чек-листы по ролям.
- `/beta-feedback/create` — форма для сообщения о проблеме, UX-замечании, идее или вопросе.
- `/admin/beta-feedback` — очередь beta-обращений для администратора.

Чек-лист для друзей и первых тестировщиков хранится в `docs/beta-testing-checklist.md`. Публичная страница `/beta-testing` доступна в `local`, `staging` и при `BETA_ACCESS_ENABLED=true`; в production без beta-режима она должна отдавать 404.

## Вариант A — Cloudflare Quick Tunnel

1. Запустить Laravel:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

2. Собрать frontend:

```bash
npm run build
```

3. Запустить tunnel:

```bash
cloudflared tunnel --url http://localhost:8000
```

4. Отправить друзьям полученную ссылку и beta-пароль.

## Вариант B — ngrok

1. Запустить Laravel:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

2. Собрать frontend:

```bash
npm run build
```

3. Запустить ngrok:

```bash
ngrok http 8000
```

4. Отправить друзьям полученную ссылку и beta-пароль.

## Важные Предупреждения Для Tunnel

- Компьютер должен быть включен.
- Терминал с Laravel закрывать нельзя.
- Терминал с tunnel закрывать нельзя.
- Ссылка может меняться при перезапуске tunnel.
- Не использовать реальные данные.
- Не оставлять tunnel включенным без необходимости.
- `BETA_ACCESS_ENABLED` должен быть `true`.
- `APP_DEBUG` лучше держать `false`, если ссылку получают другие люди.
- `TASKORA_PAYMENTS_MODE` должен оставаться `stub`.

## Ручная Проверка

1. Установить в `.env`:

```dotenv
BETA_ACCESS_ENABLED=true
BETA_ACCESS_PASSWORD=test12345
APP_DEBUG=false
```

2. Открыть `http://127.0.0.1:8000`.
3. Проверить страницу «Закрытое тестирование Таскоры».
4. Ввести неверный пароль и убедиться, что доступ не выдан.
5. Ввести правильный пароль и убедиться, что открылась главная.
6. Проверить баннер тестового режима.
7. Проверить `/login`, `/catalog`, `/tasks`.
8. Проверить `/beta-testing` и `/beta-feedback/create`.
9. Отправить тестовое обращение и убедиться, что администратор видит его в `/admin/beta-feedback`.
10. Проверить, что beta-пароль не выводится в HTML.
11. Вернуть `BETA_ACCESS_ENABLED=false`, если закрытый доступ больше не нужен.

## Будущий Staging-Домен

Будущий вариант для более стабильного тестирования:

- `staging.taskora.ru`;
- VPS;
- HTTPS;
- Basic Auth или встроенный beta access;
- отдельная staging база;
- `noindex,nofollow`;
- `TASKORA_PAYMENTS_MODE=stub`;
- реальные платежи отключены;
- demo/test accounts не переносятся в production.

Настройка сервера, DNS, HTTPS и VPS в текущую задачу не входит.
