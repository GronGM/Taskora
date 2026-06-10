# Архитектура Taskora

Taskora проектируется как модульный монолит на Laravel. Это одно приложение и одна база данных, но бизнес-области разделены на модули, чтобы проект можно было развивать без хаотичного роста контроллеров, моделей и сервисов.

Микросервисы в MVP не используются.

## Цели Архитектуры

- Один понятный deployable-проект.
- Четкие границы модулей.
- Laravel policies для доступа к защищенным действиям.
- Явные переходы статусов заказа.
- Модерация пользовательского контента до публикации или отправки.
- Защита от передачи контактов и увода сделки с платформы.
- Платежи только как локальная заглушка.
- Без ИИ-функций в MVP.

## Предлагаемая Структура Laravel

```text
app/
  Modules/
    Auth/
    Users/
    Access/
    Categories/
    Services/
    Tasks/
    Offers/
    Orders/
    Messaging/
    Files/
    Reviews/
    Disputes/
    Moderation/
    Notifications/
    Billing/
    Admin/
  Support/

resources/js/
  Components/
  Layouts/
  Pages/
    Public/
    Auth/
    Catalog/
    Services/
    Performers/
    Dashboards/
  lib/

routes/
  web.php
  auth.php
  admin.php
```

Внутри каждого модуля:

```text
ModuleName/
  Actions/
  Http/
    Controllers/
    Requests/
  Models/
  Policies/
  Queries/
  Services/
```

`app/Support` использовать только для реально общих вещей: форматирование денег, нормализация текста, enum helpers, общие value objects.

## Модули

| Модуль | Ответственность |
|---|---|
| `Auth` | регистрация, вход, выход, восстановление доступа |
| `Users` | пользователи, профили, настройки аккаунта |
| `Access` | роли, права, policies |
| `Categories` | дерево категорий услуг и заданий |
| `Services` | готовые услуги исполнителей, опции услуг, публикация |
| `Tasks` | индивидуальные задания заказчиков |
| `Offers` | отклики исполнителей на задания |
| `Orders` | заказы, рабочая область, статусы, таймлайн |
| `Messaging` | чат внутри заказа |
| `Files` | загрузка, хранение, доступ и проверка файлов |
| `Reviews` | отзывы после завершения заказа |
| `Disputes` | споры и решения модерации |
| `Moderation` | проверки контента, флаги, ручная модерация |
| `Notifications` | внутренние database-уведомления в интерфейсе |
| `Billing` | заглушка платежей, ledger, комиссии, возвраты и будущие webhook-контракты |
| `Admin` | админка и модераторская панель |

## Основные Роли

- `guest` — неавторизованный пользователь.
- `customer` — заказчик.
- `performer` — исполнитель.
- `moderator` — модератор.
- `admin` — администратор.

В текущем MVP используется одна основная роль в поле `users.role`. Новые публичные регистрации получают роль `customer` или `performer`; роли `moderator` и `admin` нельзя выбрать на публичной регистрации.

Для публичного доверия у исполнителя хранятся производные поля `performer_rating`, `performer_reviews_count` и `performer_completed_orders_count`. Они не редактируются вручную и пересчитываются из отзывов и завершенных заказов через `ReviewAggregateService`.

Модель `User` содержит методы:

- `isCustomer()`
- `isPerformer()`
- `isModerator()`
- `isAdmin()`
- `dashboardPath()`

Защита кабинетов выполняется через middleware `role`.

## Auth И Dashboard-Маршруты

| Метод | Маршрут | Назначение | Доступ |
|---|---|---|---|
| `GET` | `/login` | страница входа | guest |
| `POST` | `/login` | вход | guest |
| `GET` | `/register` | страница регистрации | guest |
| `POST` | `/register` | регистрация заказчика или исполнителя | guest |
| `POST` | `/logout` | выход | auth |
| `GET` | `/dashboard` | редирект в кабинет по роли | auth |
| `GET` | `/customer/dashboard` | кабинет заказчика | `role:customer` |
| `GET` | `/performer/dashboard` | кабинет исполнителя | `role:performer` |
| `GET` | `/moderator/dashboard` | панель модератора | `role:moderator` |
| `GET` | `/admin/dashboard` | админ-панель | `role:admin` |

Редиректы после входа:

- `customer` → `/customer/dashboard`
- `performer` → `/performer/dashboard`
- `moderator` → `/moderator/dashboard`
- `admin` → `/admin/dashboard`

## Основные Сущности

| Сущность | Назначение |
|---|---|
| `User` | аккаунт пользователя |
| `PerformerProfile` | публичный профиль исполнителя |
| `PerformerPortfolioItem` | публичная работа портфолио исполнителя |
| `Category` | категория или подкатегория |
| `Service` | готовая услуга |
| `ServicePackage` | пакет услуги с ценой, сроком и количеством правок |
| `Task` | индивидуальное задание |
| `TaskOffer` | отклик исполнителя на задание |
| `TaskFile` | файл, прикрепленный к заданию |
| `Order` | заказ из услуги или выбранного отклика |
| `OrderSubmission` | сдача работы исполнителем на проверку |
| `OrderMessage` | сообщение участника в рабочей области заказа |
| `OrderFile` | приватный файл заказа |
| `OrderEvent` | системное событие заказа |
| `PaymentOperation` | внутренняя платежная операция без реального шлюза |
| `LedgerEntry` | неизменяемая запись движения средств во внутреннем ledger |
| `ProviderWebhookEvent` | заготовка хранения будущих webhook-событий провайдера |
| `PayoutRequest` | заготовка заявки на вывод средств без банковских реквизитов |
| `FileAttachment` | будущий общий файл пользователя вне заказа |
| `Review` | отзыв по завершенному заказу |
| `Dispute` | спор по заказу |
| `ModerationFlag` | срабатывание модерации |
| `Notification` | уведомление пользователя |

## Категории И Публичный Каталог

В текущем MVP реализованы таблицы:

`categories`:

- `parent_id` для дерева категорий;
- `name`, `slug`, `description`, `icon`;
- `sort_order`;
- `is_active`.

`services`:

- `user_id` — исполнитель;
- `category_id`;
- `title`, `slug`, `short_description`, `description`;
- `price_from`, `delivery_days`;
- `status`: `draft`, `pending_review`, `published`, `rejected`, `archived`;
- `rating`, `reviews_count`, `orders_count`;
- `is_featured`.
- `moderated_by`, `moderated_at`, `rejection_reason`.

`service_packages`:

- `service_id`;
- `name`, `description`;
- `price`, `delivery_days`;
- `revisions_count`;
- `sort_order`.

`tasks`:

- `user_id` — заказчик;
- `category_id`;
- `title`, уникальный `slug`, `description`;
- `budget_min`, `budget_max`;
- `deadline_at`;
- `status`: `draft`, `published`, `closed`, `archived`;
- `offers_count`, `views_count`.

`task_offers`:

- `task_id`;
- `user_id` — исполнитель;
- `message`;
- `price`, `delivery_days`;
- `status`: `submitted`, `accepted`, `withdrawn`, `rejected`.

`task_files`:

- `task_id`;
- `user_id`;
- `original_name`, `path`;
- `mime_type`, `size`.

`orders`:

- `customer_id` — заказчик;
- `performer_id` — исполнитель;
- `category_id`;
- `service_id`, если заказ создан из услуги;
- `task_id` и `task_offer_id`, если заказ создан из отклика;
- `source_type`: `service`, `task_offer`;
- `title`, `description`;
- `price`, `delivery_days`;
- `platform_fee_percent`, `platform_fee_amount`, `performer_amount`;
- `status`: `awaiting_payment`, `in_progress`, `submitted_for_review`, `revision_requested`, `completed`, `disputed`, `canceled`;
- `payment_status`: `unpaid`, `held`, `released`, `refunded`, `canceled`;
- `review_hold_days`: срок проверки, дефолт 10 дней, допустимый диапазон 5-40 дней;
- `review_hold_started_at`, `review_hold_until`, `auto_release_at`;
- `released_at`, `release_reason`: `customer_early_accept` или `auto_release`;
- `started_at`, `submitted_at`, `completed_at`, `canceled_at`.

`order_submissions`:

- `order_id`;
- `user_id` — исполнитель;
- `message`;
- `status`: `submitted`, `accepted`, `revision_requested`.

`order_messages`:

- `order_id`;
- `user_id` — участник заказа;
- `body`;
- `type`: `user_message`, `system_message`.

`order_files`:

- `order_id`;
- `user_id` — участник заказа;
- `original_name`, `stored_name`;
- `path`;
- `disk`: по умолчанию `local`;
- `mime_type`, `size`;
- `status`: `available`, `hidden`, `deleted`;
- `moderation_status`: `clean`, `flagged`, `pending_review`.

`order_events`:

- `order_id`;
- `user_id` nullable;
- `type`;
- `payload` json nullable.

`reviews`:

- `order_id` — уникальный заказ, по которому оставлен отзыв;
- `service_id` nullable — услуга из заказа;
- `task_id` nullable — задание из заказа;
- `customer_id` — автор отзыва;
- `performer_id` — исполнитель, получивший отзыв;
- `rating` — целое значение от 1 до 5;
- `comment` nullable, максимум 2000 символов;
- `status`: `published`, `hidden`;
- `is_public`;
- `published_at`, `hidden_at`.

`disputes`:

- `order_id`;
- `opened_by` — пользователь, открывший спор;
- `resolved_by` nullable — модератор или администратор, принявший решение;
- `status`: `open`, `under_review`, `resolved`, `canceled`;
- `reason`: `work_not_delivered`, `poor_quality`, `missed_deadline`, `requirements_mismatch`, `customer_unresponsive`, `performer_unresponsive`, `other`;
- `description`;
- `previous_order_status`, `previous_payment_status`;
- `resolution`: `release_to_performer`, `refund_to_customer`, `return_to_revision`;
- `moderator_comment`;
- `resolved_at`, `canceled_at`.

`dispute_messages`:

- `dispute_id`;
- `user_id`;
- `body`;
- `is_system`.

`notifications`:

- стандартная Laravel database notifications table;
- `id` uuid;
- `type` — PHP-класс уведомления;
- `notifiable_type`, `notifiable_id`;
- `data` json с полями `type`, `title`, `body`, `url`, `icon`, `severity`, `related_type`, `related_id`, `meta`;
- `read_at`;
- `created_at`, `updated_at`.

Связи:

- `Category hasMany Services`
- `Category belongsTo parent Category`
- `Category hasMany children Categories`
- `Service belongsTo User`
- `Service belongsTo Category`
- `Service hasMany ServicePackages`
- `ServicePackage belongsTo Service`
- `User hasMany Services`
- `User hasMany Tasks`
- `User hasMany TaskOffers`
- `Category hasMany Tasks`
- `Task belongsTo User as customer`
- `Task belongsTo Category`
- `Task hasMany TaskOffers`
- `Task hasMany TaskFiles`
- `TaskOffer belongsTo Task`
- `TaskOffer belongsTo User as performer`
- `TaskOffer hasOne Order`
- `TaskFile belongsTo Task`
- `TaskFile belongsTo User`
- `User hasMany customerOrders`
- `User hasMany performerOrders`
- `Service hasMany Orders`
- `Task hasMany Orders`
- `Order belongsTo customer User`
- `Order belongsTo performer User`
- `Order belongsTo Category`
- `Order belongsTo Service`
- `Order belongsTo Task`
- `Order belongsTo TaskOffer`
- `Order hasMany OrderSubmissions`
- `Order hasMany OrderMessages`
- `Order hasMany OrderFiles`
- `Order hasMany OrderEvents`
- `Order hasMany Disputes`
- `Order hasOne activeDispute`
- `OrderSubmission belongsTo Order`
- `OrderSubmission belongsTo User`
- `OrderMessage belongsTo Order`
- `OrderMessage belongsTo User`
- `OrderFile belongsTo Order`
- `OrderFile belongsTo User`
- `OrderEvent belongsTo Order`
- `OrderEvent belongsTo User nullable`
- `Order hasOne Review`
- `Review belongsTo Order`
- `Review belongsTo Service nullable`
- `Review belongsTo Task nullable`
- `Review belongsTo customer User`
- `Review belongsTo performer User`
- `Dispute belongsTo Order`
- `Dispute belongsTo openedBy User`
- `Dispute belongsTo resolvedBy User nullable`
- `Dispute hasMany DisputeMessages`
- `DisputeMessage belongsTo Dispute`
- `DisputeMessage belongsTo User`
- `User hasMany Notifications` через Laravel `Notifiable`

Публичные маршруты:

| Метод | Маршрут | Назначение | Доступ |
|---|---|---|---|
| `GET` | `/` | главная с категориями и популярными услугами | guest |
| `GET` | `/catalog` | каталог опубликованных услуг | guest |
| `GET` | `/catalog?category={slug}` | фильтр каталога по категории | guest |
| `GET` | `/catalog/{category:slug}` | страница категории | guest |
| `GET` | `/services/{service:slug}` | страница опубликованной услуги | guest |
| `GET` | `/tasks` | биржа опубликованных заданий | guest |
| `GET` | `/tasks?category={slug}` | фильтр заданий по категории | guest |
| `GET` | `/tasks/{task:slug}` | страница опубликованного задания | guest |
| `GET` | `/performers` | публичная витрина исполнителей | guest |
| `GET` | `/performers/{user}` | публичный профиль исполнителя с услугами, отзывами и портфолио | guest |
| `GET` | `/performers/{user}/reviews` | совместимый маршрут публичного профиля с отзывами | guest |

Публично отображаются только услуги со статусом `published`. Черновики, услуги на модерации, архивные и отклоненные услуги не должны попадать в каталог и карточки услуг.

## Профили Исполнителей, Специализации И Портфолио

`performer_profiles` хранит публичную витрину исполнителя и привязана к `users` через уникальный `user_id`. Профиль создается автоматически только для пользователя с ролью `performer` при первом открытии `/performer/profile` или через сидер. Роли `customer`, `moderator` и `admin` не создают performer profile через публичный интерфейс.

Ключевые поля `performer_profiles`: `display_name`, `headline`, `bio`, `experience_years`, `response_time_label`, `avatar_path`, `cover_path`, `portfolio_summary`, `verification_status`, `verification_note`, `verified_at`, `verified_by`, `submitted_for_verification_at`, `published_at`, `is_public`. Статусы проверки: `not_submitted`, `pending_review`, `verified`, `rejected`.

Специализации реализованы через `category_performer_profile`. Исполнитель выбирает до 7 активных категорий. Неактивные категории отклоняются request-валидацией. Специализации показываются на `/performers` и `/performers/{user}`.

`performer_portfolio_items` хранит публичные работы исполнителя: `title`, `description`, `category_id`, `image_path`, `file_path`, `external_url`, `sort_order`, `is_public`, `status`. Статусы: `draft`, `published`, `hidden`. Публичный профиль показывает только `published` и `is_public = true`.

Проверка профиля выполняется вручную модератором или администратором. Отправка на проверку требует публичное имя, заголовок, описание минимум 100 символов, минимум одну специализацию и минимум одну опубликованную работу портфолио или опубликованную услугу. Если подтвержденный профиль меняет `headline`, `bio`, `portfolio_summary` или специализации, статус снова становится `pending_review`.

Файлы портфолио хранятся на `public` disk и считаются публичными материалами исполнителя. `order_files` остаются приватными файлами заказа на `local` disk и скачиваются только через контроллер с policy. OCR и сложный парсинг документов портфолио в MVP не используются.

ContactGuard применяется к `display_name`, `headline`, `bio`, `portfolio_summary`, `title`, `description` и `external_url`. При обнаружении email, телефона, Telegram, WhatsApp, VK, Discord, Skype, платежных реквизитов или предложения уйти с платформы сохранение блокируется, а событие записывается в `moderation_flags`.

Публично отображаются только задания со статусом `published`. Черновики, закрытые и архивные задания возвращают `404` на прямой публичной ссылке и не попадают в `/tasks`.

Публичные рейтинги берутся только из опубликованных отзывов и завершенных оплаченных заказов. Если отзывов нет, карточки услуг и исполнителей показывают пустое состояние, а не искусственный рейтинг.

## Индивидуальные Задания И Отклики

Заказчик управляет только своими заданиями через защищенные маршруты с middleware `auth` и `role:customer`.

| Метод | Маршрут | Назначение | Защита |
|---|---|---|---|
| `GET` | `/customer/tasks` | список своих заданий | `TaskPolicy::viewAny` |
| `GET` | `/customer/tasks/create` | форма создания задания | `TaskPolicy::create` |
| `POST` | `/customer/tasks` | создание черновика или публикация | `TaskPolicy::create` |
| `GET` | `/customer/tasks/{task}` | просмотр своего задания и откликов | `TaskPolicy::view` |
| `GET` | `/customer/tasks/{task}/edit` | редактирование своего задания | `TaskPolicy::update` |
| `PUT/PATCH` | `/customer/tasks/{task}` | обновление своего задания | `TaskPolicy::update` |
| `POST` | `/customer/tasks/{task}/publish` | публикация черновика | `TaskPolicy::publish` |
| `POST` | `/customer/tasks/{task}/archive` | архивирование задания | `TaskPolicy::archive` |
| `POST` | `/customer/task-offers/{offer}/accept` | выбор отклика и создание заказа | `role:customer` и проверка владения заданием |
| `POST` | `/customer/task-offers/{offer}/reject` | отклонение отклика | `TaskOfferPolicy::reject` |

Исполнитель работает с откликами через защищенные маршруты с middleware `auth` и `role:performer`.

| Метод | Маршрут | Назначение | Защита |
|---|---|---|---|
| `POST` | `/tasks/{task}/offers` | отправка отклика на опубликованное задание | `TaskOfferPolicy::create` |
| `GET` | `/performer/offers` | список своих откликов | `TaskOfferPolicy::viewAny` |
| `POST` | `/performer/task-offers/{offer}/withdraw` | отзыв своего отклика | `TaskOfferPolicy::withdraw` |

Правила:

- чужие задания недоступны заказчику и возвращают `403`;
- новое задание сохраняется как `draft`, если заказчик не нажал публикацию;
- публикация переводит только свой черновик в `published`;
- архивное задание не показывается публично;
- исполнитель может отправить только один отклик на опубликованное задание;
- заказчик и гость не могут отправить отклик;
- исполнитель может отозвать только свой отклик со статусом `submitted`;
- заказчик видит отклики только на свои задания, может отклонить отклик или выбрать исполнителя;
- выбор отклика создает заказ, закрывает задание и отклоняет остальные отправленные отклики.

Поток задания:

1. Заказчик создает черновик задания или сразу публикует его.
2. `ContactGuard` проверяет `title` и `description` до сохранения.
3. Опубликованное задание появляется на `/tasks` и в публичной карточке `/tasks/{task:slug}`.
4. Исполнитель отправляет отклик с сообщением, ценой и сроком.
5. `ContactGuard` проверяет `message` отклика до сохранения.
6. Заказчик открывает свое задание в кабинете и видит список откликов.
7. Заказчик выбирает подходящий отклик.
8. Система создает заказ, закрывает задание, помечает выбранный отклик как `accepted`, а остальные отправленные отклики как `rejected`.

## Управление Услугами Исполнителя

Исполнитель управляет только своими услугами через защищенные маршруты с middleware `auth` и `role:performer`.

| Метод | Маршрут | Назначение | Защита |
|---|---|---|---|
| `GET` | `/performer/services` | список своих услуг | роль исполнителя |
| `GET` | `/performer/services/create` | форма создания | роль исполнителя |
| `POST` | `/performer/services` | создание услуги | `ServicePolicy::create` |
| `GET` | `/performer/services/{service}/edit` | редактирование своей услуги | `ServicePolicy::update` |
| `PUT/PATCH` | `/performer/services/{service}` | обновление своей услуги | `ServicePolicy::update` |
| `POST` | `/performer/services/{service}/submit-review` | отправка на модерацию | `ServicePolicy::submitReview` |
| `POST` | `/performer/services/{service}/archive` | архивирование | `ServicePolicy::archive` |

Правила:

- чужие услуги недоступны исполнителю и возвращают `403`;
- новая услуга всегда создается как `draft`, если пользователь не нажал отправку на модерацию;
- пользователь не выбирает публичный статус формы;
- отправка на модерацию переводит услугу в `pending_review`;
- изменение важных полей опубликованной услуги переводит ее в `pending_review`;
- архивная услуга не отправляется на модерацию повторно.

Для валидации используются `StoreServiceRequest`, `UpdateServiceRequest` и `SubmitServiceForReviewRequest`. Текстовые поля проходят через `App\Services\Moderation\ContactGuard` до сохранения.

Услугу со статусом `pending_review` исполнитель может открыть, но не может редактировать до решения модератора. Отклоненная услуга (`rejected`) показывает причину отказа и может быть отредактирована перед повторной отправкой.

## Модерация Услуг

Модераторская очередь доступна ролям `moderator` и `admin`. Модератор не получает доступ к админским настройкам платформы, но может работать с очередью услуг и флагами.

| Метод | Маршрут | Назначение | Доступ |
|---|---|---|---|
| `GET` | `/moderator/services` | услуги `pending_review` | `role:moderator,admin` |
| `GET` | `/moderator/services/{service}` | просмотр услуги на проверке | `ServicePolicy::review` |
| `POST` | `/moderator/services/{service}/approve` | публикация услуги | `ServicePolicy::approve` |
| `POST` | `/moderator/services/{service}/reject` | отклонение услуги | `ServicePolicy::reject` |
| `GET` | `/moderator/moderation-flags` | открытые флаги | `role:moderator,admin` |
| `POST` | `/moderator/moderation-flags/{flag}/resolve` | обработка флага | `role:moderator,admin` |

Поток модерации:

1. Исполнитель отправляет услугу на модерацию.
2. Услуга получает статус `pending_review`.
3. Модератор открывает карточку услуги, видит описание, пакеты, исполнителя и связанные `moderation_flags`.
4. При одобрении статус меняется на `published`, `rejection_reason` очищается, заполняются `moderated_by` и `moderated_at`.
5. После публикации услуга становится видимой в публичном каталоге.
6. При отклонении статус меняется на `rejected`, сохраняется причина отказа, заполняются `moderated_by` и `moderated_at`.
7. Отклоненная услуга не видна публично, а исполнитель видит причину отказа и может отправить исправленную услугу повторно.

## Поток Заказа Из Готовой Услуги

1. Исполнитель создает услугу в кабинете.
2. Система проверяет описание на запрещенные контакты.
3. Услуга сохраняется как `draft` или получает статус модерации `pending_review`.
4. Модератор одобряет или отклоняет услугу.
5. Заказчик открывает карточку услуги.
6. Заказчик выбирает пакет услуги и создает `Order` со статусом `awaiting_payment`.
7. В заказе фиксируются цена, срок, комиссия платформы и сумма исполнителю.
8. Локальная заглушка оплаты переводит `payment_status` в `held`, а заказ — в `in_progress`.
9. Исполнитель отправляет результат через `OrderSubmission`.
10. Заказ получает статус `submitted_for_review`.
11. Заказчик принимает работу или запрашивает доработку.
12. После приемки заказ становится `completed`, а `payment_status` — `released`.

## Поток Заказа Из Индивидуального Задания

1. Заказчик создает задание: категория, бюджет, срок, описание, файлы.
2. Система проверяет описание и названия файлов на контакты.
3. Задание публикуется или отправляется на ручную модерацию.
4. Исполнители отправляют отклики с ценой, сроком и сообщением.
5. Отклик проверяется антиуводом контактов.
6. Заказчик выбирает отклик.
7. Создается `Order`, связанный с `Task` и `TaskOffer`.
8. Выбранный отклик получает статус `accepted`, остальные отправленные отклики по заданию переводятся в `rejected`.
9. Задание получает статус `closed`.
10. Дальше заказ идет по общему статусному процессу с локальной заглушкой оплаты.

## Рабочая Область Заказа

Доступ к рабочей области заказа имеют только:

- `customer`, если он является владельцем заказа;
- `performer`, если он является исполнителем заказа.

`moderator` и `admin` не получают доступ к workspace в текущем MVP. Их будущий доступ должен идти через отдельный модуль споров и арбитража, а не через обычные маршруты участника заказа.

Маршруты заказчика:

| Метод | Маршрут | Назначение | Доступ |
|---|---|---|---|
| `GET` | `/customer/orders/{order}/workspace` | рабочая область заказа | участник-заказчик |
| `POST` | `/customer/orders/{order}/messages` | отправка сообщения | участник-заказчик |
| `POST` | `/customer/orders/{order}/files` | загрузка файла | участник-заказчик |
| `GET` | `/customer/orders/{order}/files/{file}/download` | приватное скачивание файла | участник-заказчик |

Маршруты исполнителя:

| Метод | Маршрут | Назначение | Доступ |
|---|---|---|---|
| `GET` | `/performer/orders/{order}/workspace` | рабочая область заказа | участник-исполнитель |
| `POST` | `/performer/orders/{order}/messages` | отправка сообщения | участник-исполнитель |
| `POST` | `/performer/orders/{order}/files` | загрузка файла | участник-исполнитель |
| `GET` | `/performer/orders/{order}/files/{file}/download` | приватное скачивание файла | участник-исполнитель |

Поток сообщений:

1. Участник заказа открывает workspace.
2. `OrderPolicy::viewWorkspace` проверяет участие в заказе.
3. При отправке сообщения request валидирует непустой текст и лимит 4000 символов.
4. `ContactGuard` проверяет текст до сохранения.
5. Если контакт найден, `OrderMessage` не создается, создаются `ModerationFlag` и `OrderEvent` с типом `contact_blocked`.
6. Если нарушений нет, создается `OrderMessage` с типом `user_message`.
7. Создается `OrderEvent` с типом `message_sent`.

Поток файлов:

1. Участник заказа выбирает файл в workspace.
2. Request валидирует размер до 20 MB и разрешенные расширения: `pdf`, `doc`, `docx`, `xls`, `xlsx`, `ppt`, `pptx`, `txt`, `csv`, `png`, `jpg`, `jpeg`, `webp`, `zip`.
3. `rar` не разрешен в MVP из-за нестабильной MIME-валидации без дополнительных зависимостей.
4. `ContactGuard` проверяет `original_name`.
5. Для `txt` и `csv` дополнительно проверяется текстовое содержимое.
6. Для `docx`, `pdf`, `xlsx`, `pptx` и изображений полноценный парсинг содержимого и OCR не выполняются без внешних зависимостей.
7. Если найден контакт, файл не сохраняется, создаются `ModerationFlag` и `OrderEvent` с типом `contact_blocked`.
8. Если нарушений нет, файл сохраняется на приватном `local` disk в `storage/app/private`.
9. Создается `OrderFile` и `OrderEvent` с типом `file_uploaded`.
10. Скачивание идет только через controller route после проверки участника заказа; прямые ссылки из storage не выдаются.

`storage:link` должен открывать только `storage/app/public` для будущих публичных аватаров и изображений. Каталог `order_files`/`orders/{order_id}` остается на приватном `local` disk и не должен попадать в public storage или CDN без отдельного защищенного контроллера.

TODO для будущих этапов:

- проверка содержимого `docx`, `pdf`, `xlsx`, `pptx`;
- OCR изображений;
- ручная очередь модерации файлов с `moderation_status = pending_review`.

## Статусы Заказа

```text
awaiting_payment
in_progress
submitted_for_review
revision_requested
disputed
completed
canceled
```

Текущие MVP-переходы:

- `awaiting_payment` → `in_progress` через локальную заглушку оплаты, событие `payment_stub_paid`;
- `in_progress` → `submitted_for_review` после сдачи работы исполнителем, события `work_submitted` и `review_hold_started`;
- `submitted_for_review` → `completed` после досрочной приемки заказчиком, события `order_completed` и `funds_released`, `release_reason = customer_early_accept`;
- `submitted_for_review` → `completed` по команде `orders:release-due`, если истек `review_hold_until`, события `order_completed` и `funds_released`, `release_reason = auto_release`;
- `in_progress`/`submitted_for_review`/`revision_requested` → `disputed` при открытии спора участником заказа, событие `dispute_opened`;
- `submitted_for_review` → `revision_requested` после запроса доработки, событие `revision_requested`, поля `review_hold_started_at`, `review_hold_until` и `auto_release_at` очищаются;
- `revision_requested` → `submitted_for_review` после повторной сдачи, события `work_submitted` и `review_hold_started`, срок проверки запускается заново;
- `disputed` → `completed` по решению `release_to_performer`, события `dispute_resolved` и `funds_released`, `release_reason = dispute_release_to_performer`;
- `disputed` → `canceled` по решению `refund_to_customer`, события `dispute_resolved` и `funds_refunded`;
- `disputed` → `revision_requested` по решению `return_to_revision`, события `dispute_resolved` и `revision_requested_by_moderator`;
- `awaiting_payment` → `canceled` при отмене до оплаты, событие `order_canceled`.

При создании заказа из услуги или выбранного отклика пишется событие `order_created`.

Исполнитель может отменить заказ напрямую только в состоянии `awaiting_payment` при `payment_status = unpaid`. После оплаты прямой отказ исполнителя запрещен; будущая отмена должна идти через спор или модератора.

Дальше статусные переходы стоит вынести в отдельные action-классы, чтобы контроллеры не росли.

## События Заказа

`OrderEventLogger` пишет события в `order_events`.

Текущие типы событий:

```text
order_created
payment_stub_paid
work_submitted
review_hold_started
revision_requested
order_completed
funds_released
order_canceled
dispute_opened
dispute_message_sent
dispute_under_review
dispute_resolved
funds_refunded
revision_requested_by_moderator
message_sent
file_uploaded
contact_blocked
```

События отображаются в workspace как история заказа. Старые тестовые данные не мигрируются, но новые действия пишут события сразу.

## Внутренние Уведомления

Модуль `Notifications` использует штатный Laravel database channel. В MVP не подключаются email, push, WebSocket, Broadcasting, Reverb или Pusher.

Ключевые классы:

- `App\Notifications\PlatformNotification` — один универсальный notification-класс с `type`, `title`, `body`, `url`, `icon`, `severity`, `related_type`, `related_id` и `meta`;
- `App\Services\Notifications\NotificationService` — единая точка отправки, дедупликации получателей и подготовки payload для Inertia;
- `App\Http\Controllers\Notifications\NotificationController` — список уведомлений, отметка одного уведомления и отметка всех уведомлений прочитанными.

Shared props через `HandleInertiaRequests` передают:

- `auth.user`;
- `notifications.unread_count`;
- `notifications.latest` — последние 5 уведомлений.

Полный список уведомлений доступен по `/notifications`. Пользователь может читать и помечать только свои уведомления.

События, которые создают уведомления:

- услуга одобрена или отклонена модератором;
- новый отклик, принятие отклика и отклонение отклика;
- заказ создан из услуги или отклика;
- заказ оплачен локальной заглушкой;
- работа отправлена на проверку;
- заказчик запросил доработку;
- заказ завершен или auto-release разблокировал оплату;
- новое сообщение или файл в рабочей области заказа;
- спор открыт, взят в работу, получил новое сообщение или был решен.

Правила:

- сообщения и файлы не уведомляют отправителя;
- уведомления одному и тому же пользователю не дублируются в рамках одного события;
- moderator/admin получают уведомления только по событиям, которые требуют их внимания: новый спор и сообщения в споре;
- системные события завершения заказа и auto-release уведомляют обе стороны заказа.

## Заглушка Платежей

Реальный платежный шлюз не подключается.

Текущая платежная архитектура отделяет lifecycle заказа от финансовой истории:

- `orders.status` отвечает за процесс работы: `awaiting_payment`, `in_progress`, `submitted_for_review`, `revision_requested`, `completed`, `disputed`, `canceled`;
- `orders.payment_status` отвечает за состояние оплаты: `unpaid`, `held`, `released`, `refunded`, `canceled`;
- `payment_operations` фиксирует внутренние операции `payment_hold`, `release_to_performer`, `refund_to_customer`, `platform_fee_capture`, `platform_fee_reverse`, `payout_stub`, `webhook_received`;
- `ledger_entries` хранит неизменяемую историю по счетам `customer_payment`, `escrow`, `performer_pending`, `performer_available`, `platform_fee`, `customer_refund`;
- `provider_webhook_events` готовит таблицу для будущего провайдера, но endpoint пока не подключен;
- `payout_requests` готовит будущие заявки на вывод без реквизитов, KYC и реальных выплат.

`PaymentLedgerService` — единая точка записи финансовых событий. Методы сервиса идемпотентны через `idempotency_key`, поэтому повторный вызов для одного заказа не создает дубли `payment_operations` и `ledger_entries`.

Потоки:

- `mark-paid` создает `payment_hold`: `customer_payment` debit на сумму заказа, `escrow` credit на сумму заказа, `performer_pending` credit на сумму исполнителя, `platform_fee` credit на комиссию;
- досрочная приемка заказчиком, `orders:release-due` и решение спора `release_to_performer` создают `release_to_performer`, закрывают `escrow` и переносят сумму из `performer_pending` в `performer_available`;
- решение спора `refund_to_customer` создает `refund_to_customer`, отражает `customer_refund`, закрывает ожидающую сумму исполнителя и сторнирует комиссию через `platform_fee_reverse`;
- отмена неоплаченного заказа в `awaiting_payment/unpaid` не создает платежных операций.

Будущие provider events будут маппиться так:

| Provider event | Внутренняя операция |
|---|---|
| `payment.succeeded` | `payment_hold` |
| `payment.waiting_for_capture` | `webhook_received` |
| `payment.canceled` | `webhook_received` |
| `refund.succeeded` | `refund_to_customer` |
| `payout.succeeded` | `payout_stub` |

Реальные платежи, выплаты, фискализация, частичные возвраты и банковские реквизиты не входят в текущую архитектуру.

`Billing` в MVP хранит:

- сумму заказа;
- процент комиссии платформы;
- сумму комиссии;
- сумму исполнителю;
- статус оплаты;
- локальную заглушку удержания и выпуска средств.

Статусы оплаты:

```text
unpaid
held
released
refunded
canceled
```

Для разработки используется действие "Оплатить (заглушка)": оно не списывает деньги, а только переводит заказ в работу и помечает оплату как `held`.

После сдачи работы удержание не снимается сразу. Срок проверки хранится в полях `review_hold_started_at`, `review_hold_until` и `auto_release_at`. Заказчик может:

- принять работу досрочно, что переводит оплату в `released` и ставит `release_reason = customer_early_accept`;
- запросить доработку, что оставляет оплату в `held` и очищает срок проверки до повторной сдачи;
- на следующем этапе открыть спор, который должен останавливать автоматическую разблокировку.

Команда `php artisan orders:release-due` автоматически завершает только due-заказы `submitted_for_review` с `payment_status = held` и истекшим `review_hold_until`. Для таких заказов заполняются `completed_at`, `released_at`, `release_reason = auto_release`, а в `order_events` пишутся `order_completed` и `funds_released`.

Команда зарегистрирована в Laravel scheduler как hourly-задача. В production cron должен запускать `php artisan schedule:run` каждую минуту; сам scheduler решает, что `orders:release-due` нужно выполнить раз в час. Заказы в статусе `disputed` не подходят под условие команды и не разблокируются автоматически.

## Отзывы И Доверие

Модуль `Reviews` реализован как часть модульного монолита. Основные классы:

- `App\Models\Review`;
- `App\Policies\ReviewPolicy`;
- `App\Http\Requests\Customer\StoreReviewRequest`;
- `App\Http\Controllers\Customer\CustomerReviewController`;
- `App\Services\Reviews\ReviewAggregateService`.

Защищенные маршруты заказчика:

| Метод | Маршрут | Назначение | Защита |
|---|---|---|---|
| `GET` | `/customer/orders/{order}/review/create` | форма отзыва | `ReviewPolicy::create` |
| `POST` | `/customer/orders/{order}/review` | публикация отзыва | `ReviewPolicy::create`, `ContactGuard` |
| `GET` | `/customer/reviews` | список своих отзывов | `role:customer` |
| `GET` | `/customer/reviews/{review}` | просмотр своего отзыва | `ReviewPolicy::view` |

Правила создания:

1. Отзыв доступен только заказчику-владельцу заказа.
2. Заказ должен быть `completed` и `payment_status = released`.
3. На один заказ можно создать только один отзыв.
4. Исполнитель не может оставить отзыв самому себе.
5. `service_id`, `task_id`, `customer_id` и `performer_id` берутся из заказа, а не из пользовательского payload.
6. `rating` обязателен, диапазон 1-5.
7. `comment` необязателен, максимум 2000 символов.
8. Перед сохранением `comment` проверяется через `ContactGuard`.
9. При найденном контакте отзыв не сохраняется, создается `moderation_flag` с `entity_type = App\Models\Review` и причиной `contact_detected_in_review`.

Публикация отзыва выполняется в транзакции вместе с пересчетом агрегатов. `ReviewAggregateService` пересчитывает:

- `services.rating` и `services.reviews_count` по публичным опубликованным отзывам услуги;
- `services.orders_count` по завершенным заказам услуги с разблокированной оплатой;
- `users.performer_rating` и `users.performer_reviews_count` по публичным опубликованным отзывам исполнителя;
- `users.performer_completed_orders_count` по завершенным заказам исполнителя с разблокированной оплатой.

Пересчет выполненных заказов также вызывается при досрочной приемке заказчиком, авторазблокировке командой `orders:release-due` и решении спора в пользу исполнителя.

Публичное отображение:

- карточка услуги показывает рейтинг только при наличии отзывов;
- страница услуги показывает последние публичные отзывы;
- `/performers` показывает профиль, специализации, бейдж проверки, рейтинг, число отзывов, завершенные заказы и число опубликованных услуг;
- `/performers/{user}` показывает публичный профиль, услуги, отзывы и опубликованное портфолио без email исполнителя;
- `/performers/{user}/reviews` сохранен как совместимый маршрут и открывает публичный профиль с отзывами.

TODO после MVP: ограниченное окно редактирования отзыва, ручное скрытие отзывов модератором и расширенные метрики доверия профиля.

## Споры И Арбитраж

Спор открывается только по оплаченному заказу с `payment_status = held` и статусом `in_progress`, `submitted_for_review` или `revision_requested`. Открыть спор могут только участники заказа: заказчик-владелец или исполнитель. Гость, чужой пользователь, модератор или администратор не открывают спор от имени участника.

Поток открытия:

1. Policy проверяет участие пользователя в заказе, допустимый статус, удержанную оплату и отсутствие активного спора.
2. Создается `dispute` со статусом `open`, причиной, описанием и предыдущими статусами заказа/оплаты.
3. `orders.status` меняется на `disputed`, `payment_status` остается `held`.
4. `review_hold_until` и `auto_release_at` очищаются, поэтому `orders:release-due` не сможет разблокировать оплату.
5. Создаются `order_event` с типом `dispute_opened` и системное `dispute_message`.

Сообщения в споре доступны участникам заказа, модератору и администратору. Текст проверяется через `ContactGuard`; при нарушении сообщение не сохраняется, создается `moderation_flag` и `order_event` `contact_blocked`. При успешной отправке создается `dispute_message` и `order_event` `dispute_message_sent`.

Модераторский поток:

1. Модератор или администратор открывает `/moderator/disputes`.
2. `POST /moderator/disputes/{dispute}/take` переводит спор из `open` в `under_review`, пишет системное сообщение и событие `dispute_under_review`.
3. `POST /moderator/disputes/{dispute}/resolve` принимает решение с обязательным комментарием.
4. `release_to_performer` завершает заказ, переводит оплату в `released`, ставит `release_reason = dispute_release_to_performer`.
5. `refund_to_customer` отменяет заказ и переводит оплату в `refunded`.
6. `return_to_revision` возвращает заказ в `revision_requested`, оплата остается `held`, срок проверки будет запущен заново при повторной сдаче.

TODO после MVP: частичный возврат, частичная выплата исполнителю и распределение комиссии при `partial_split`.

## Модерация

Модерация нужна для:

- услуг;
- заданий;
- откликов;
- сообщений в чате;
- профилей исполнителей;
- названий файлов;
- текстовых файлов, если текст можно извлечь без OCR.

Результаты проверки:

- `approved` — нарушений нет;
- `blocked` — явное нарушение, действие не выполняется;
- `pending_review` — спорный случай, нужна ручная модерация.

Все срабатывания сохраняются в `moderation_flags`. Для услуг, заданий и откликов фиксируются пользователь, тип сущности, идентификатор сущности, причина, тип совпадения, найденный фрагмент и статус обработки флага. После ручной обработки флага заполняются `resolved_by`, `resolved_at`, а статус меняется на `resolved`. Допустимые статусы флага: `open`, `resolved`, `ignored`.

## Защита От Передачи Контактов

Запрещено передавать:

- телефоны;
- email;
- Telegram;
- WhatsApp;
- VK;
- Discord;
- Skype;
- банковские карты;
- платежные реквизиты;
- фразы вроде "напиши в тг", "давай напрямую", "оплата мимо сайта", "скинь номер", "перейдем в вотсап".

Проверка должна выполняться до отправки сообщения или публикации материала. Если нарушение явное, пользователь получает понятное предупреждение на русском языке:

```text
Сообщение не отправлено: в нем обнаружены контактные данные или предложение перейти вне Taskora. Обсуждайте заказ и оплату внутри платформы.
```

Для MVP использовать детерминированные правила: регулярные выражения, словари фраз, нормализацию пробелов и символов. ИИ-модерацию не добавлять.

## Доступ И Безопасность

- Все формы валидировать через request-классы.
- Доступ к заказам, чатам и файлам проверять через policies.
- Пользователь не должен видеть чужие заказы, чаты и файлы.
- Модератор видит только данные, нужные для модерации.
- Администратор управляет настройками платформы.
- Ограничить типы и размеры файлов.
- Rate limit включен для сообщений заказа и спора, загрузки файлов, создания заданий/услуг/споров, откликов и маршрутов уведомлений.
- Ошибки `403`, `404`, `429`, `500` и `503` отдавать через русскоязычную Inertia-страницу без технических деталей.
- Защитить вывод пользовательского HTML от XSS.
- Логировать важные действия: создание заказа, смена статуса, спор, блокировка модерацией.

## Rate Limits

| Область | Лимит | Limiter |
|---|---:|---|
| сообщения заказа и спора | 20 в минуту на пользователя | `taskora-order-messages` |
| файлы заказа | 10 за 10 минут на пользователя | `taskora-order-files` |
| создание задания, услуги или спора | 10 в час на пользователя | `taskora-create` |
| отклики на задания | 30 в час на пользователя | `taskora-offers` |
| уведомления | 60 в минуту на пользователя | `taskora-notifications` |

Все лимиты ключуются по `user_id`, для гостевых запросов используется IP. При превышении лимита web-запрос получает страницу `429` с понятным русским текстом.

## Интерфейс

Интерфейс пользователя — на русском языке.

Технические названия файлов, классов, таблиц, маршрутов и модулей можно писать на английском.

Основные layout:

- `PublicLayout` — публичная часть.
- `DashboardLayout` — кабинеты пользователей по ролям.
- `Orders/Workspace` — рабочая область заказа внутри `DashboardLayout`.
- `AdminLayout` — админка и модерация.
