# Environment

Документ фиксирует текущее состояние локальной среды для проекта «Таскора» и минимальный путь запуска Laravel + Inertia + React каркаса.

## Текущий Статус

Проверено в `C:\Users\Gron\Documents\New project 2`.

| Инструмент | Команда проверки | Статус |
|---|---|---|
| Git | `git --version` | доступен: `git version 2.54.0.windows.1` |
| PHP | `php --version` | доступен: `PHP 8.4.22` |
| Composer | `composer --version` | доступен: `Composer 2.10.1` |
| Node | `node --version` | доступен: `v24.15.0` |
| npm | `npm --version` | доступен: `11.12.1` |
| Docker | `docker --version` | не найден в `PATH`; не нужен для первого запуска |
| PostgreSQL CLI | `psql --version` | не установлен; на первом этапе используется SQLite |
| Redis server | `redis-server --version` | не установлен; на первом этапе используются database-драйверы Laravel |

Git и PHP установлены через `winget`. Composer установлен официальным PHP-установщиком как `C:\Users\Gron\bin\composer.phar` с bat-оберткой `C:\Users\Gron\bin\composer.bat`.

PHP настроен для Laravel: включены расширения `curl`, `fileinfo`, `mbstring`, `openssl`, `pdo_pgsql`, `pdo_sqlite`, `pgsql`, `sqlite3`, `zip`.

## Обновление PATH В Текущей PowerShell-Сессии

Если новая консоль еще не видит Git, PHP или Composer, можно обновить `PATH` без перезапуска терминала:

```powershell
$machine = [Environment]::GetEnvironmentVariable('Path', 'Machine')
$user = [Environment]::GetEnvironmentVariable('Path', 'User')
$env:Path = "$machine;$user"
```

После этого проверить:

```powershell
git --version
php --version
composer --version
node --version
npm --version
```

## Самый Простой Запуск Для Solo-Разработчика

На текущем этапе Docker, PostgreSQL и Redis не обязательны. Проект можно запускать на SQLite.

```powershell
composer install
npm install
php artisan migrate
npm run dev
php artisan serve --host=127.0.0.1 --port=8000
```

В отдельной вкладке браузера открыть:

```text
http://127.0.0.1:8000
```

## Переменные Окружения Для Первого Этапа

Для локального запуска используется SQLite:

```env
DB_CONNECTION=sqlite
```

PostgreSQL оставлен в `.env.example` как будущая настройка, но не подключается на первом этапе:

```env
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=taskora
# DB_USERNAME=taskora
# DB_PASSWORD=
```

Платежный шлюз не подключен. В проекте используется только заглушка режима платежей:

```env
TASKORA_PAYMENTS_MODE=stub
```

## Опциональная Установка Docker Позже

Docker Desktop пригодится позже для PostgreSQL и Redis, но не блокирует текущий каркас.

```powershell
winget install --id Docker.DockerDesktop -e
```

После установки Docker Desktop обычно требуется перезапуск Windows или повторный вход в систему.
