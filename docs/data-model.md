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
- performer_rating
- performer_reviews_count
- performer_completed_orders_count
- email_verified_at
- timestamps

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
- sort_order
- is_active
- timestamps

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
- customer_id
- category_id
- title
- description
- budget_min
- budget_max
- deadline_at
- status
- moderation_status
- timestamps

### offers

- id
- task_id
- performer_id
- message
- price
- delivery_days
- status
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

### payment_records

- id
- order_id
- amount
- platform_fee_percent
- platform_fee_amount
- performer_amount
- status
- timestamps

### order_messages

- id
- order_id
- sender_id
- body
- moderation_status
- blocked_at
- timestamps

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
- Исполнитель видит публичные доступные задания, свои отклики, свои услуги и свои заказы.
- Модератор видит только данные, нужные для модерации.
- Администратор управляет настройками платформы и имеет доступ к более широким операционным данным.

## Примечания

- Фрагменты модерации хранить осторожно. Если достаточно хэша найденного контакта, не сохранять полный контакт.
- Добавить индексы для публичных фильтров: категория, статус, статус модерации, цена, срок, рейтинг и даты.
- Использовать soft delete только там, где восстановление действительно является продуктовым требованием.
