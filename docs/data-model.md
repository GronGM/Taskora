# Модель Данных

Это первичная модель данных для MVP. Она намеренно компактная и должна уточняться при создании миграций.

## Основные Таблицы

### users

- id
- name
- email
- password
- role
- status
- blocked_at nullable
- blocked_by nullable
- block_reason nullable
- last_login_at nullable
- last_login_ip nullable
- admin_note nullable
- performer_rating
- performer_reviews_count
- performer_completed_orders_count
- email_verified_at
- timestamps

`users.status` принимает значения `active` и `blocked`. Заблокированный пользователь не может войти в систему; если активная сессия была создана до блокировки, следующий web-запрос разлогинит пользователя. Пароль, `remember_token`, reset-токены и полный IP не выводятся в админские Inertia props.

### user_admin_events

- id
- target_user_id
- actor_user_id
- type
- old_values json nullable
- new_values json nullable
- comment nullable
- timestamps

Таблица хранит аудит админских действий над пользователями: `role_changed`, `user_blocked`, `user_unblocked`, `admin_note_updated`. Физическое удаление пользователей, смена пароля администратором и impersonation в MVP не реализуются.

### performer_profiles

- id
- user_id
- display_name
- headline
- bio
- avatar_path
- response_time_minutes
- completed_orders_count
- rating_average
- rating_count
- is_verified
- is_available
- timestamps

### categories

- id
- parent_id
- name
- slug
- description
- icon nullable
- sort_order
- is_active
- timestamps

### task_types

- id
- category_id nullable
- name
- slug unique
- description nullable
- sort_order
- is_active
- timestamps

Админское управление:

- категории и виды заданий не удаляются физически в MVP;
- `is_active=false` скрывает справочник из публичных фильтров и новых форм, но не удаляет связи у существующих задач, услуг и избранного;
- slug генерируется при создании из названия, если админ оставил поле пустым, и получает суффикс при конфликте;
- при редактировании slug не меняется автоматически при изменении названия;
- `categories.parent_id` не может ссылаться на саму категорию или ее потомка;
- `task_types.category_id` в admin-форме должен указывать на активную категорию;
- публичные `description` категорий и видов заданий проверяются ContactGuard и не сохраняются при найденных контактах.

### services

- id
- performer_id
- category_id
- title
- slug
- short_description
- description
- base_price
- delivery_days
- status
- moderation_status
- rating
- reviews_count
- orders_count
- timestamps

### service_options

- id
- service_id
- title
- description
- price_delta
- delivery_days_delta
- is_active
- timestamps

### tasks

- id
- user_id
- category_id
- task_type_id nullable
- slug unique
- title
- description
- budget_min
- budget_max
- deadline_at
- status
- offers_count
- views_count
- timestamps

### task_offers

- id
- task_id
- user_id
- message
- price
- delivery_days
- status
- timestamps

### task_favorites

- id
- task_id
- user_id
- unique task_id, user_id
- timestamps

### performer_favorite_categories

- id
- user_id
- category_id
- unique user_id, category_id
- timestamps

### performer_favorite_task_types

- id
- user_id
- task_type_id
- unique user_id, task_type_id
- timestamps

### orders

- id
- customer_id
- performer_id
- service_id
- task_id
- offer_id
- title
- description
- amount
- status
- started_at
- due_at
- submitted_at
- completed_at
- canceled_at
- timestamps

### payment_operations

- id
- order_id
- user_id nullable
- provider
- provider_operation_id nullable
- type
- status
- amount
- currency
- idempotency_key nullable unique
- description nullable
- payload json nullable
- succeeded_at nullable
- failed_at nullable
- canceled_at nullable
- timestamps

Типы: `payment_hold`, `release_to_performer`, `refund_to_customer`, `platform_fee_capture`, `platform_fee_reverse`, `payout_stub`, `webhook_received`.

Статусы: `pending`, `succeeded`, `failed`, `canceled`.

### ledger_entries

- id
- payment_operation_id nullable
- order_id nullable
- user_id nullable
- account
- direction
- amount
- currency
- description nullable
- reference_type nullable
- reference_id nullable
- posted_at nullable
- timestamps

Счета: `customer_payment`, `escrow`, `performer_pending`, `performer_available`, `platform_fee`, `customer_refund`.

Направления: `debit`, `credit`.

Ledger-записи не редактируются после создания. Исправления должны оформляться новыми корректирующими записями.

### provider_webhook_events

- id
- provider
- event_id nullable
- event_type
- status
- payload json nullable
- processed_at nullable
- error_message nullable
- timestamps

Таблица готовит будущую интеграцию провайдера. Реальный webhook endpoint пока не подключен.

### payout_requests

- id
- performer_id
- amount
- currency
- status
- requested_at nullable
- reviewed_by nullable
- reviewed_at nullable
- paid_at nullable
- rejection_reason nullable
- timestamps

Статусы: `draft`, `pending_review`, `approved`, `rejected`, `paid`, `canceled`.

Банковские реквизиты, паспортные данные, ИНН, СНИЛС и KYC-документы в текущей схеме не хранятся.

### order_messages

- id
- order_id
- user_id
- body
- type
- timestamps

Сообщения заказа доступны только `customer_id` и `performer_id` заказа. Отправка проходит `ContactGuard`; заблокированное сообщение не сохраняется, вместо него создаются `moderation_flags` и `order_events.type = contact_blocked`.

### dispute_messages

- id
- dispute_id
- user_id
- body
- is_system
- timestamps

Сообщения спора доступны участникам заказа, модератору и администратору. Отправка пользовательского сообщения также проходит `ContactGuard`.

### conversation_reads

- id
- user_id
- conversation_type: `order` или `dispute`
- conversation_id
- last_read_at nullable
- timestamps
- unique user_id, conversation_type, conversation_id

Таблица хранит состояние прочтения для `/messages`. Непрочитанные считаются как сообщения после `last_read_at`, отправленные не текущим пользователем. Если записи нет или `last_read_at = null`, непрочитанными считаются все чужие сообщения в диалоге после создания заказа или спора.

Messages v2 не добавляет отдельную таблицу conversations: диалог заказа строится из `orders` + `order_messages`, диалог спора — из `disputes` + `dispute_messages`. Контекст в Inertia payload собирается из существующих заказов, споров, `order_events` и безопасных метаданных `order_files`. Приватные поля файлов (`stored_name`, `path`, `disk`) не должны попадать в payload; скачивание разрешается только через существующий download controller с policy-проверкой.

### files

- id
- owner_id
- order_id
- task_id
- service_id
- original_name
- stored_name
- disk
- path
- mime_type
- size
- moderation_status
- timestamps

### reviews

- id
- order_id
- service_id nullable
- task_id nullable
- customer_id
- performer_id
- rating
- comment
- status
- is_public
- published_at
- hidden_at
- timestamps

### disputes

- id
- order_id
- opened_by_id
- assigned_moderator_id
- reason
- status
- resolution
- timestamps

### moderation_flags

- id
- user_id
- subject_type
- subject_id
- source
- severity
- rule_code
- matched_value_hash
- excerpt
- status
- reviewed_by_id
- reviewed_at
- timestamps

## Правила Видимости

- Заказчик видит свои задания, отклики на свои задания и свои заказы.
- Исполнитель видит публичные доступные задания, свои отклики, избранные задания/категории/виды заданий, свои услуги и свои заказы.
- Модератор видит только данные, нужные для модерации.
- Администратор управляет настройками платформы и имеет доступ к более широким операционным данным.
- `/messages` показывает заказчику и исполнителю только их заказные диалоги и споры по их заказам.
- Модератор и администратор видят через `/messages` только диалоги споров; прямой чат заказа без спора им не открывается.
- Shared Inertia props для сообщений содержат только `messages.unread_count`, без тел сообщений и без пользовательских секретов.
- Администратор видит read-only список заказов и карточку заказа через `/admin/orders`: участники, источник, статусы, события, споры, payment operations и ledger. Этот доступ не дает права менять статусы, удалять заказы, скачивать приватные файлы напрямую, запускать выплаты или возвраты.
- Админские представления заказов не должны передавать `password`, `remember_token`, beta-пароль, полный IP, прямой `order_files.path`, `stored_name`, `disk` или download URL.

## Связи Биржи Заданий

- `Category hasMany TaskType`
- `Category hasMany Task`
- `Category hasMany PerformerFavoriteCategory`
- `TaskType belongsTo Category`
- `TaskType hasMany Task`
- `TaskType hasMany PerformerFavoriteTaskType`
- `Task belongsTo User as customer`
- `Task belongsTo Category`
- `Task belongsTo TaskType nullable`
- `Task hasMany TaskOffer`
- `Task hasMany TaskFavorite`
- `TaskOffer belongsTo Task`
- `TaskOffer belongsTo User as performer`
- `TaskFavorite belongsTo Task`
- `TaskFavorite belongsTo User`
- `User hasMany Task`
- `User hasMany TaskOffer`
- `User hasMany TaskFavorite`
- `User hasMany PerformerFavoriteCategory`
- `User hasMany PerformerFavoriteTaskType`

Правила:

- `task_type_id` в задании nullable, но обязателен в форме заказчика, если у выбранной категории есть активные виды заданий.
- Избранные категории, виды заданий и задания доступны только роли `performer`.
- В избранное можно добавить только опубликованное задание, активную категорию и активный вид задания.
- Публичные фильтры показывают только активные категории и активные виды заданий из активных категорий.
- Существующие опубликованные задания и услуги со скрытой категорией или скрытым видом задания продолжают открываться.
- Закрытое или архивное задание можно убрать из избранного, но нельзя добавить заново.
- Быстрые фильтры `/tasks?favorite_categories=1` и `/tasks?favorite_types=1` используют избранное текущего исполнителя.

## Связи Платежной Архитектуры

- `Order hasMany PaymentOperation`
- `Order hasMany LedgerEntry`
- `PaymentOperation belongsTo Order`
- `PaymentOperation belongsTo User nullable`
- `PaymentOperation hasMany LedgerEntry`
- `LedgerEntry belongsTo PaymentOperation nullable`
- `LedgerEntry belongsTo Order nullable`
- `LedgerEntry belongsTo User nullable`
- `User hasMany PaymentOperations`
- `User hasMany LedgerEntries`
- `User hasMany PayoutRequests as performer`
- `PayoutRequest belongsTo performer User`
- `PayoutRequest belongsTo reviewedBy User nullable`

## Примечания

- Фрагменты модерации хранить осторожно. Если достаточно хэша найденного контакта, не сохранять полный контакт.
- Добавить индексы для публичных фильтров: категория, статус, статус модерации, цена, срок, рейтинг и даты.
- Использовать soft delete только там, где восстановление действительно является продуктовым требованием.
- Платежный ledger предназначен для внутренней истории stub-операций. Перед production-платежами нужны провайдер, webhooks, фискализация, правила возвратов и юридическая проверка.
- `/admin/orders/{order}/ledger` показывает ledger только для диагностики. Корректировки финансовой истории должны идти через отдельные бизнес-операции и аудит, а не через ручное редактирование записей.
