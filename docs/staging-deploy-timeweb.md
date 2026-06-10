# Staging deploy на Timeweb VPS

Документ описывает закрытый staging-деплой проекта «Таскора» из приватного GitHub-репозитория `GronGM/Taskora` на VPS Timeweb.

Staging не является production-запуском: доступ закрыт beta-паролем, `APP_DEBUG=false`, платежи работают только в `stub`-режиме, реальные платежные провайдеры не подключаются.

## Исходные данные

- VPS IPv4: `201.51.3.241`
- Основной домен: `таскора.рф`
- Основной punycode: `xn--80aa3aqkdf.xn--p1ai`
- Staging-домен: `staging.таскора.рф`
- Staging punycode: `staging.xn--80aa3aqkdf.xn--p1ai`
- GitHub repo: `https://github.com/GronGM/Taskora`
- Project path on server: `/var/www/taskora`
- Repo visibility: private. На сервере нужен read-only deploy key, personal access token хранить на сервере не нужно.

## 1. Проверка DNS

В панели DNS домена `таскора.рф` добавьте A-запись:

```text
staging  A  201.51.3.241
```

Проверка с локальной машины:

```bash
nslookup staging.xn--80aa3aqkdf.xn--p1ai
```

Ожидаемый IPv4: `201.51.3.241`. До настройки SSL HTTP-проверки выполняются по punycode-домену.

## 2. SSH на сервер

Подключение под root или пользователем с sudo:

```bash
ssh root@201.51.3.241
```

Дальше команды можно выполнять от root. Если используется отдельный deploy-пользователь, у него должны быть права на `/var/www/taskora`, reload `nginx` и reload `php8.4-fpm`.

## 3. Установка Nginx, PHP, PostgreSQL, Composer, Node, Certbot

Для Ubuntu:

```bash
apt update
apt install -y software-properties-common ca-certificates curl gnupg unzip git nginx postgresql postgresql-contrib certbot python3-certbot-nginx
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.4-fpm php8.4-cli php8.4-pgsql php8.4-mbstring php8.4-xml php8.4-curl php8.4-zip php8.4-bcmath php8.4-intl
curl -fsSL https://deb.nodesource.com/setup_24.x | bash -
apt install -y nodejs
curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
```

Проверка:

```bash
php --version
composer --version
node --version
npm --version
nginx -v
psql --version
```

## 4. Клонирование приватного репозитория

Создайте read-only deploy key на сервере:

```bash
ssh-keygen -t ed25519 -C "taskora-staging-deploy" -f ~/.ssh/taskora_staging_deploy -N ""
cat ~/.ssh/taskora_staging_deploy.pub
```

Добавьте публичный ключ в GitHub: `GronGM/Taskora` -> Settings -> Deploy keys -> Add deploy key. Включать `Allow write access` не нужно.

Настройте SSH alias:

```bash
cat >> ~/.ssh/config <<'EOF'
Host github-taskora
    HostName github.com
    User git
    IdentityFile ~/.ssh/taskora_staging_deploy
    IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config
ssh -T github-taskora
```

Клонирование:

```bash
mkdir -p /var/www
git clone git@github-taskora:GronGM/Taskora.git /var/www/taskora
cd /var/www/taskora
git checkout main
```

Если репозиторий позже станет публичным, можно заменить clone-команду на `git clone https://github.com/GronGM/Taskora.git /var/www/taskora`.

## 5. Настройка PostgreSQL

Создайте базу и пользователя. Пароль задайте новый, сложный, не из документации:

```bash
sudo -u postgres psql
```

```sql
CREATE DATABASE taskora_staging;
CREATE USER taskora WITH PASSWORD 'CHANGE_ME_STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON DATABASE taskora_staging TO taskora;
\c taskora_staging
GRANT ALL ON SCHEMA public TO taskora;
\q
```

## 6. Настройка `.env`

На сервере:

```bash
cd /var/www/taskora
cp docs/env.staging.example .env
nano .env
```

Обязательные значения для staging:

- `APP_ENV=staging`
- `APP_DEBUG=false`
- `APP_URL=https://staging.таскора.рф`
- `BETA_ACCESS_ENABLED=true`
- `BETA_ACCESS_PASSWORD` заменен на временный сложный пароль
- `TASKORA_PAYMENTS_MODE=stub`
- `PAYMENT_PROVIDER=stub`
- `PAYMENT_PROVIDER_MODE=stub`
- `DB_PASSWORD` заменен на пароль пользователя PostgreSQL

Файл `.env` нельзя коммитить и нельзя отправлять в чат или документацию.

## 7. Сборка Laravel

Первичная сборка:

```bash
cd /var/www/taskora
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
php artisan key:generate --force
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache || php artisan route:clear
php artisan view:cache
chown -R www-data:www-data storage bootstrap/cache
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;
```

Не использовать `migrate:fresh` на staging, если там уже есть тестовые данные.

## 8. Nginx server block

Скопируйте пример конфига:

```bash
cp /var/www/taskora/deploy/nginx/taskora-staging.conf.example /etc/nginx/sites-available/taskora-staging
ln -s /etc/nginx/sites-available/taskora-staging /etc/nginx/sites-enabled/taskora-staging
nginx -t
systemctl reload nginx
```

Если на сервере другой PHP-FPM socket, замените строку:

```nginx
fastcgi_pass unix:/run/php/php8.4-fpm.sock;
```

Актуальный socket обычно можно найти командой:

```bash
ls /run/php/
```

## 9. HTTP-проверка

До SSL:

```bash
curl -I http://staging.xn--80aa3aqkdf.xn--p1ai
```

Ожидаемо: ответ Nginx/Laravel, затем в браузере должна открываться страница закрытого beta-доступа.

Проверки:

- beta gate включен;
- неверный пароль не пускает;
- правильный beta-пароль открывает `/`, `/catalog`, `/tasks`, `/login`, `/register`;
- в HTML не отображается beta-пароль;
- на страницах виден тестовый баннер;
- реальные платежи не включены.

## 10. SSL через Certbot

После успешной DNS и HTTP-проверки:

```bash
certbot --nginx -d staging.xn--80aa3aqkdf.xn--p1ai
systemctl reload nginx
curl -I https://staging.xn--80aa3aqkdf.xn--p1ai
```

Проверьте автообновление сертификата:

```bash
certbot renew --dry-run
```

## 11. Cron для schedule

Откройте crontab пользователя, от которого должен выполняться Laravel scheduler:

```bash
crontab -e
```

Добавьте:

```cron
* * * * * cd /var/www/taskora && php artisan schedule:run >> /dev/null 2>&1
```

## 12. Обновление staging через deploy script

Перед обновлением убедитесь, что GitHub Actions CI зеленый для `main`.

```bash
cd /var/www/taskora
bash deploy/scripts/deploy-staging.sh
```

Скрипт подтягивает `main`, ставит зависимости, собирает frontend, выполняет миграции без сброса базы, кеширует Laravel и перезагружает PHP-FPM/Nginx.

## 13. Откат

Безопасный временный откат к предыдущему коммиту:

```bash
cd /var/www/taskora
git log --oneline -5
git checkout <previous_commit_sha>
composer install --no-dev --optimize-autoloader --no-interaction --prefer-dist
npm ci
npm run build
php artisan config:cache
php artisan route:cache || php artisan route:clear
php artisan view:cache
sudo systemctl reload php8.4-fpm
sudo systemctl reload nginx
```

Возврат на `main`:

```bash
git checkout main
git pull --ff-only
bash deploy/scripts/deploy-staging.sh
```

Миграции назад выполнять только после отдельной оценки риска для данных staging.

## 14. Staging security checklist

- GitHub Actions CI зеленый перед деплоем.
- Репозиторий приватный, на сервере используется read-only deploy key.
- На сервере нет GitHub personal access token.
- `.env` существует только на сервере и не коммитится.
- `APP_DEBUG=false`.
- `BETA_ACCESS_ENABLED=true`, beta-пароль сложный и не сохранен в документации.
- `TASKORA_PAYMENTS_MODE=stub`, `PAYMENT_PROVIDER=stub`, `PAYMENT_PROVIDER_MODE=stub`.
- Реальные ЮKassa, CloudPayments, Robokassa и другие платежные провайдеры не подключены.
- Nginx не отдает `.env`, `.git`, dotfiles и приватные order/storage files.
- Staging закрыт от индексации через `X-Robots-Tag: noindex, nofollow`.
- Firewall разрешает только нужные порты: SSH, HTTP, HTTPS.
- Логи не содержат beta-пароль, ключи платежей и другие секреты.
- Есть резервная копия базы перед рискованными миграциями.
