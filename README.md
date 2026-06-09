# Taskora

Taskora, интерфейсное название «Таскора», — русскоязычный маркетплейс задач и услуг. Сервис проектируется как модульный монолит: заказчик может купить готовую услугу или разместить индивидуальное задание, исполнитель — опубликовать услугу, откликнуться на задание и выполнить заказ внутри платформы.

Проект не копирует Kwork и Studwork по дизайну, текстам, структуре страниц или фирменному стилю.

## Текущее Состояние

Создан рабочий Laravel + Inertia + React каркас:

- Laravel-приложение установлено в текущий каталог;
- подключен Inertia middleware;
- подключены React, Inertia React, Vite и Tailwind CSS;
- создан общий публичный layout;
- создана главная страница на русском языке;
- реализованы вход, регистрация и выход;
- добавлены роли `customer`, `performer`, `moderator`, `admin`;
- добавлены role-protected dashboard-страницы;
- добавлены локальные seed-пользователи для проверки;
- локальный первый запуск настроен на SQLite.

В проекте не подключены:

- реальный платежный шлюз;
- AI-функции;
- внешние платные API;
- Docker-зависимый запуск.

## Стек

- Laravel 13
- PHP 8.4
- Inertia Laravel
- React
- Vite
- Tailwind CSS
- SQLite для первого локального запуска
- PostgreSQL позже, после стабилизации MVP-схемы
- database-драйверы Laravel для cache, queue и session на первом этапе
- PHPUnit

## MVP

В MVP входят:

- регистрация и авторизация;
- роли `customer`, `performer`, `moderator`, `admin`;
- публичная главная страница;
- каталог услуг;
- страницы категорий;
- карточка услуги;
- профиль исполнителя;
- биржа заданий;
- кабинет заказчика;
- кабинет исполнителя;
- создание готового заказа из услуги;
- создание индивидуального задания;
- отклики исполнителей;
- создание заказа из выбранного отклика;
- рабочая область заказа;
- чат внутри заказа;
- загрузка файлов;
- статусы заказа;
- отзывы;
- споры;
- модерация услуг, заданий, сообщений и файлов;
- защита от передачи контактов и увода сделки с платформы;
- платежная заглушка без реального списания;
- админка и модераторская панель.

В MVP не входят:

- реальный платежный шлюз;
- AI-чат;
- AI-подбор исполнителей;
- AI-анализ файлов;
- AI-модерация;
- OCR для изображений;
- микросервисы;
- нативные мобильные приложения.

## Запуск

Установить PHP-зависимости:

```bash
composer install
```

Установить JS-зависимости:

```bash
npm install
```

Создать локальную SQLite-БД, если файла еще нет:

```bash
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
```

Выполнить миграции и сидеры:

```bash
php artisan migrate:fresh --seed
```

Собрать frontend:

```bash
npm run build
```

Запустить локальный сервер:

```bash
php artisan serve --host=127.0.0.1 --port=8000
```

Открыть:

```text
http://127.0.0.1:8000
```

Для разработки frontend можно отдельно запустить:

```bash
npm run dev
```

## Проверки

```bash
composer install
npm install
npm audit
php artisan migrate:fresh --seed
npm run build
php artisan route:list --except-vendor
php artisan test
composer validate --strict
```

## Локальные Тестовые Пользователи

Эти аккаунты создаются сидером только для локальной разработки. Не использовать такие email и пароль в production.

| Роль | Email | Пароль | Кабинет |
|---|---|---|---|
| Заказчик | `customer@taskora.local` | `password` | `/customer/dashboard` |
| Исполнитель | `performer@taskora.local` | `password` | `/performer/dashboard` |
| Модератор | `moderator@taskora.local` | `password` | `/moderator/dashboard` |
| Администратор | `admin@taskora.local` | `password` | `/admin/dashboard` |

Публичная регистрация разрешает выбрать только роли заказчика и исполнителя. Роли модератора и администратора назначаются только внутри платформы.

## Переменные Окружения

Основные локальные значения:

```dotenv
APP_NAME="Таскора"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

APP_LOCALE=ru
APP_FALLBACK_LOCALE=ru
APP_FAKER_LOCALE=ru_RU

DB_CONNECTION=sqlite

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

TASKORA_PLATFORM_FEE_PERCENT=15
TASKORA_CONTACT_GUARD_ENABLED=true
TASKORA_MODERATION_QUEUE_ENABLED=true
TASKORA_PAYMENTS_MODE=stub
```

Будущие настройки PostgreSQL оставлены в `.env.example` комментариями. На первом этапе PostgreSQL, Docker и Redis не требуются.

## Документация

- [Архитектура](docs/architecture.md)
- [План MVP](docs/mvp-roadmap.md)
- [MVP-объем](docs/mvp-scope.md)
- [Модель данных](docs/data-model.md)
- [Антиувод контактов](docs/moderation-anti-circumvention.md)
- [Локальная среда](docs/environment.md)

## Следующий Шаг

Лучший следующий этап — реализовать категории и публичный каталог услуг: базовую модель категорий, сидер стартовых направлений, публичный список и placeholder-карточки услуг.
