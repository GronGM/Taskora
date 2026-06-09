# Taskora

Taskora, интерфейсное название «Таскора», — русскоязычный маркетплейс задач и услуг. Сервис проектируется как модульный монолит: заказчик может купить готовую услугу или разместить индивидуальное задание, исполнитель — опубликовать услугу, откликнуться на задание и выполнить заказ внутри платформы.

Проект не копирует Kwork и Studwork по дизайну, текстам, структуре страниц или фирменному стилю.

## Текущее Состояние

Создан минимальный рабочий Laravel + Inertia + React каркас:

- Laravel-приложение установлено в текущий каталог;
- подключен Inertia middleware;
- подключены React, Inertia React, Vite и Tailwind CSS;
- создан общий публичный layout;
- создана главная страница на русском языке;
- добавлены базовые placeholder-страницы для навигации;
- локальный первый запуск настроен на SQLite.

Документы `AGENTS.md`, `docs/architecture.md`, `docs/mvp-roadmap.md` и папка `docs/` сохранены.

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

В проекте не подключены:

- реальный платежный шлюз;
- AI-функции;
- внешние платные API;
- Docker-зависимый запуск.

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

## Структура

Текущий минимальный каркас:

```text
app/
bootstrap/
config/
database/
docs/
public/
resources/
  css/
  js/
    Layouts/
    Pages/
routes/
storage/
tests/
artisan
composer.json
package.json
vite.config.js
```

Целевая структура модульного монолита описана в `docs/architecture.md`.

## Запуск

Установить PHP-зависимости:

```bash
composer install
```

Установить JS-зависимости:

```bash
npm install
```

Выполнить миграции для SQLite:

```bash
php artisan migrate
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
npm run build
php artisan about
php artisan test
```

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

Лучший следующий этап — реализовать авторизацию, роли `customer`, `performer`, `moderator`, `admin` и базовую русскоязычную структуру кабинетов.
