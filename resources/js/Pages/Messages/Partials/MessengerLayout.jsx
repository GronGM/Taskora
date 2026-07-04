import { Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Clock, FileText, Info, MessageCircle, Paperclip, Search, Send, ShieldCheck, X } from 'lucide-react';
import { useEffect, useMemo, useState } from 'react';

const currency = new Intl.NumberFormat('ru-RU');

const focusClass = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950';

const statusTone = {
    awaiting_payment: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100',
    in_progress: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100',
    submitted_for_review: 'border-purple-200 bg-purple-50 text-purple-800 dark:border-purple-700 dark:bg-purple-950 dark:text-purple-100',
    revision_requested: 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-700 dark:bg-orange-950 dark:text-orange-100',
    completed: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-100',
    disputed: 'border-red-200 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-100',
    canceled: 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
};

const paymentTone = {
    unpaid: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100',
    held: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100',
    released: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-100',
    refunded: 'border-purple-200 bg-purple-50 text-purple-800 dark:border-purple-700 dark:bg-purple-950 dark:text-purple-100',
    canceled: 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
};

export function MessengerLayout({
    conversations = [],
    pagination = {},
    filters = {},
    tabs = [],
    orderStatusOptions = [],
    sortOptions = [],
    activeKey = null,
    children,
    details,
}) {
    const hasActiveConversation = Boolean(activeKey);
    const layoutColumns = hasActiveConversation
        ? 'md:grid-cols-[320px_minmax(0,1fr)] xl:grid-cols-[340px_minmax(0,1fr)_320px]'
        : 'md:grid-cols-[320px_minmax(0,1fr)]';

    return (
        <section className="mx-auto max-w-[1500px] px-3 py-4 sm:px-4 lg:px-6" data-testid="messages-layout">
            <div className={`grid min-w-0 gap-4 ${layoutColumns}`}>
                <aside className={`${hasActiveConversation ? 'hidden md:flex' : 'flex'} min-w-0 flex-col overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950`} data-testid="messages-conversation-list">
                    <ConversationListPanel
                        conversations={conversations}
                        pagination={pagination}
                        filters={filters}
                        tabs={tabs}
                        orderStatusOptions={orderStatusOptions}
                        sortOptions={sortOptions}
                        activeKey={activeKey}
                    />
                </aside>

                <main className={`${hasActiveConversation ? 'block' : 'hidden md:block'} min-w-0 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-950`}>
                    {children}
                </main>

                {details && (
                    <aside className="hidden min-w-0 xl:block">
                        {details}
                    </aside>
                )}
            </div>
        </section>
    );
}

export function InboxEmptyState({ firstConversation = null }) {
    return (
        <div className="flex min-h-[620px] flex-col items-center justify-center px-6 py-12 text-center">
            <div className="flex h-14 w-14 items-center justify-center rounded-lg bg-blue-50 text-blue-700 ring-1 ring-blue-100 dark:bg-blue-950 dark:text-blue-200 dark:ring-blue-800">
                <MessageCircle className="h-7 w-7" aria-hidden="true" />
            </div>
            <p className="mt-5 text-xl font-semibold text-slate-950 dark:text-white">Выберите переписку</p>
            <p className="mt-2 max-w-md text-sm leading-6 text-slate-600 dark:text-slate-300">
                Откройте заказ или спор, чтобы продолжить общение и видеть детали сделки.
            </p>
            <div className="mt-6 grid w-full max-w-2xl gap-3 text-left sm:grid-cols-3">
                <PlaceholderHint title="Заказы" text="Переписки по активным заказам и рабочим областям." />
                <PlaceholderHint title="Споры" text="Обсуждения с участием модератора." />
                <PlaceholderHint title="Непрочитанные" text="Новые сообщения, которые требуют внимания." />
            </div>
            {firstConversation?.url && (
                <Link href={firstConversation.url} className={`mt-6 inline-flex rounded-md bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 ${focusClass}`}>
                    Открыть последний диалог
                </Link>
            )}
        </div>
    );
}

export function ConversationView({ conversation }) {
    return (
        <div className="flex min-h-[620px] min-w-0 flex-col">
            <ConversationHeader conversation={conversation} />
            <div className="flex-1 min-w-0 space-y-4 overflow-hidden bg-slate-50/70 px-3 py-4 dark:bg-slate-900/45 sm:px-5">
                <MobileDetails conversation={conversation} />
                <SafetyBanner />
                <ConversationStartCard conversation={conversation} />
                <TimelineCards events={conversation.timeline_events ?? []} />
                <AttachmentStrip files={conversation.files ?? []} />
                <MessageList messages={conversation.messages ?? []} />
            </div>
            <MessageComposer conversation={conversation} />
        </div>
    );
}

export function ConversationDetails({ conversation, compact = false }) {
    const isDispute = conversation.type === 'dispute';

    return (
        <div className={`${compact ? '' : 'rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950'} min-w-0`} data-testid="message-details-panel">
            <div className="flex items-center gap-2">
                <Info className="h-4 w-4 text-blue-600 dark:text-blue-300" aria-hidden="true" />
                <h2 className="text-base font-semibold text-slate-950 dark:text-white">{isDispute ? 'Детали спора' : 'Детали заказа'}</h2>
            </div>

            {isDispute ? <DisputeDetails conversation={conversation} /> : <OrderDetails conversation={conversation} />}
        </div>
    );
}

function ConversationListPanel({ conversations, pagination, filters, tabs, orderStatusOptions, sortOptions, activeKey }) {
    const [filtersOpen, setFiltersOpen] = useState(false);
    const hasActiveFilters = Number(filters.active_count ?? 0) > 0;

    return (
        <>
            <div className="border-b border-slate-200 p-4 dark:border-slate-800">
                <p className="text-xs font-semibold uppercase text-blue-700 dark:text-blue-300">Мессенджер</p>
                <div className="mt-2 flex items-start justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white">Сообщения</h1>
                        <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">Заказы и споры</p>
                    </div>
                    {hasActiveFilters && (
                        <Link href="/messages" className={`hidden shrink-0 rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900 sm:inline-flex ${focusClass}`}>
                            Сбросить фильтры
                        </Link>
                    )}
                </div>

                <div className="mt-4 grid grid-cols-2 gap-2" aria-label="Вкладки сообщений">
                    {tabs.map((tab) => (
                        <Link
                            key={tab.value}
                            href={queryUrl(filters, { tab: tab.value, page: null })}
                            className={`rounded-md px-3 py-2 text-center text-xs font-semibold transition ${focusClass} ${
                                filters.tab === tab.value
                                    ? 'bg-blue-600 text-white dark:bg-blue-500'
                                    : 'border border-slate-200 text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-blue-950 dark:hover:text-blue-100'
                            }`}
                        >
                            {tab.label}
                        </Link>
                    ))}
                </div>

                <div className="mt-4 flex items-center justify-between gap-2 md:hidden">
                    <button
                        type="button"
                        className={`inline-flex min-h-10 items-center justify-center rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-900 ${focusClass}`}
                        aria-expanded={filtersOpen}
                        aria-controls="messages-filter-panel"
                        data-testid="messages-filters-toggle"
                        onClick={() => setFiltersOpen((open) => !open)}
                    >
                        {filtersOpen ? 'Скрыть фильтры' : 'Показать фильтры'}
                        {hasActiveFilters && <span className="ml-1 text-blue-700 dark:text-blue-300">· {filters.active_count}</span>}
                    </button>
                    {hasActiveFilters && (
                        <Link href="/messages" className={`shrink-0 rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900 ${focusClass}`}>
                            Сбросить фильтры
                        </Link>
                    )}
                </div>

                {hasActiveFilters && (
                    <div className="mt-3 flex flex-wrap gap-2" aria-label="Активные фильтры">
                        {filters.q && <Chip>Поиск: {filters.q}</Chip>}
                        {filters.status && <Chip>Статус: {labelFor(orderStatusOptions, filters.status)}</Chip>}
                        {filters.sort !== 'newest' && <Chip>Сортировка: {labelFor(sortOptions, filters.sort)}</Chip>}
                        {filters.tab !== 'all' && <Chip>Раздел: {labelFor(tabs, filters.tab)}</Chip>}
                    </div>
                )}

                <form id="messages-filter-panel" action="/messages" method="get" className={`${filtersOpen ? 'block' : 'hidden'} mt-4 space-y-3 md:block`}>
                    {filters.tab !== 'all' && <input type="hidden" name="tab" value={filters.tab} />}
                    <label className="block">
                        <span className="text-sm font-semibold text-slate-900 dark:text-white">Поиск по диалогам</span>
                        <div className="relative mt-2">
                            <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" aria-hidden="true" />
                            <input
                                name="q"
                                type="search"
                                defaultValue={filters.q ?? ''}
                                placeholder="Заказ, спор или участник"
                                className="w-full rounded-md border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-950"
                            />
                        </div>
                    </label>
                    <div className="grid gap-3 sm:grid-cols-2 md:grid-cols-1">
                        <Select label="Статус" name="status" value={filters.status ?? ''} options={[{ value: '', label: 'Все статусы' }, ...orderStatusOptions]} />
                        <Select label="Сортировка" name="sort" value={filters.sort ?? 'newest'} options={sortOptions} />
                    </div>
                    <button type="submit" className={`w-full rounded-md bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 ${focusClass}`}>
                        Показать
                    </button>
                </form>
            </div>

            <div className="min-h-[420px] flex-1 overflow-y-auto p-2">
                {conversations.length > 0 ? (
                    <div className="space-y-2">
                        {conversations.map((conversation) => (
                            <ConversationCard key={conversation.key} conversation={conversation} active={conversation.key === activeKey} />
                        ))}
                    </div>
                ) : (
                    <ConversationListEmptyState hasActiveFilters={hasActiveFilters} />
                )}
            </div>

            {pagination.last_page > 1 && (
                <div className="flex items-center justify-between gap-2 border-t border-slate-200 p-3 text-xs text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    <span>{pagination.from ?? 0}-{pagination.to ?? 0} из {pagination.total ?? 0}</span>
                    <div className="flex gap-2">
                        {pagination.prev_page_url && <PageLink href={pagination.prev_page_url}>Назад</PageLink>}
                        {pagination.next_page_url && <PageLink href={pagination.next_page_url}>Дальше</PageLink>}
                    </div>
                </div>
            )}
        </>
    );
}

function ConversationListEmptyState({ hasActiveFilters }) {
    return (
        <div className="rounded-lg border border-dashed border-slate-300 p-6 text-center dark:border-slate-700">
            <p className="font-semibold text-slate-950 dark:text-white">
                {hasActiveFilters ? 'Диалогов по этому запросу нет.' : 'У вас пока нет переписок.'}
            </p>
            <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                {hasActiveFilters
                    ? 'Попробуйте изменить запрос или сбросить фильтры.'
                    : 'Когда появится заказ или спор, диалог появится здесь.'}
            </p>
            {hasActiveFilters && (
                <Link href="/messages" className={`mt-4 inline-flex rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900 ${focusClass}`}>
                    Сбросить фильтры
                </Link>
            )}
        </div>
    );
}

function ConversationCard({ conversation, active }) {
    return (
        <Link
            href={conversation.url}
            className={`block rounded-lg border p-3 transition ${focusClass} ${
                active
                    ? 'border-blue-400 bg-blue-50 shadow-sm dark:border-blue-500 dark:bg-blue-950/55'
                    : conversation.unread_count > 0
                        ? 'border-blue-200 bg-blue-50/70 hover:border-blue-300 dark:border-blue-800 dark:bg-blue-950/30 dark:hover:border-blue-600'
                        : 'border-transparent hover:border-slate-200 hover:bg-slate-50 dark:hover:border-slate-800 dark:hover:bg-slate-900'
            }`}
        >
            <div className="flex min-w-0 gap-3">
                <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-950 text-sm font-semibold text-white dark:bg-white dark:text-slate-950">
                    {initials(conversation.participant?.name)}
                </div>
                <div className="min-w-0 flex-1">
                    <div className="flex min-w-0 items-start justify-between gap-2">
                        <div className="min-w-0">
                            <p className="truncate text-sm font-semibold text-slate-950 dark:text-white">{conversation.participant?.name ?? 'Участник'}</p>
                            <p className="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{conversation.participant?.role_label}</p>
                        </div>
                        <span className="shrink-0 text-xs font-semibold text-slate-400 dark:text-slate-500">{conversation.last_message_time ?? conversation.last_message_at}</span>
                    </div>

                    <div className="mt-2 flex flex-wrap items-center gap-1.5">
                        <Badge>{conversation.type_label}</Badge>
                        {conversation.type === 'dispute' && <Badge tone="red">Спор</Badge>}
                        <Badge tone="slate">{conversation.status_label}</Badge>
                        {conversation.unread_count > 0 && <Badge tone="blue">{conversation.unread_count}</Badge>}
                    </div>

                    <p className="mt-2 line-clamp-2 break-words text-sm font-semibold text-slate-900 dark:text-slate-100">{conversation.title}</p>
                    <p className="mt-1 line-clamp-2 break-words text-xs leading-5 text-slate-600 dark:text-slate-300">
                        {conversation.last_message_author && <span className="font-semibold">{conversation.last_message_author}: </span>}
                        {conversation.last_message}
                    </p>
                </div>
            </div>
        </Link>
    );
}

function ConversationHeader({ conversation }) {
    const isDispute = conversation.type === 'dispute';
    const orderTitle = isDispute ? conversation.order.title : conversation.title;
    const primaryUrl = isDispute ? conversation.dispute_url : conversation.order_url;

    return (
        <header className="sticky top-0 z-10 border-b border-slate-200 bg-white/95 px-3 py-3 backdrop-blur dark:border-slate-800 dark:bg-slate-950/95 sm:px-5">
            <div className="flex min-w-0 flex-col gap-3">
                <div className="min-w-0">
                    <Link href="/messages" className={`mb-2 inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-900 md:hidden dark:text-blue-300 dark:hover:text-blue-100 ${focusClass}`} data-testid="messages-mobile-back">
                        <ArrowLeft className="h-4 w-4" aria-hidden="true" />
                        Назад к сообщениям
                    </Link>
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge tone={isDispute ? 'red' : 'blue'}>{conversation.type_label} #{conversation.id}</Badge>
                        <Badge tone="slate">{isDispute ? conversation.status_label : conversation.status_label}</Badge>
                        {!isDispute && <Badge tone="blue">{conversation.payment_status_label}</Badge>}
                    </div>
                    <h1 className="mt-2 line-clamp-2 min-w-0 break-words text-xl font-semibold tracking-tight text-slate-950 [overflow-wrap:anywhere] dark:text-white sm:text-2xl lg:line-clamp-none" title={orderTitle}>{orderTitle}</h1>
                    <p className="mt-1 break-words text-sm text-slate-600 dark:text-slate-300">
                        {isDispute ? `${conversation.reason_label} · ${conversation.order.payment_status_label}` : `${conversation.participant.role_label}: ${conversation.participant.name}`}
                    </p>
                </div>
                <div className="flex min-w-0 flex-wrap gap-2">
                    {primaryUrl && <ActionLink href={primaryUrl}>{isDispute ? 'Открыть спор' : 'Открыть заказ'}</ActionLink>}
                    {conversation.workspace_url && <ActionLink href={conversation.workspace_url} variant="dark">Открыть рабочую область</ActionLink>}
                </div>
            </div>
        </header>
    );
}

function PlaceholderHint({ title, text }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-900">
            <p className="text-sm font-semibold text-slate-950 dark:text-white">{title}</p>
            <p className="mt-2 text-xs leading-5 text-slate-600 dark:text-slate-300">{text}</p>
        </div>
    );
}

function MobileDetails({ conversation }) {
    return (
        <details className="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950 xl:hidden">
            <summary className="cursor-pointer text-sm font-semibold text-slate-950 marker:text-slate-400 dark:text-white">
                {conversation.type === 'dispute' ? 'Детали спора' : 'Детали заказа'}
            </summary>
            <div className="mt-4">
                <ConversationDetails conversation={conversation} compact />
            </div>
        </details>
    );
}

function SafetyBanner() {
    const [visible, setVisible] = useState(true);

    useEffect(() => {
        try {
            setVisible(window.localStorage.getItem('taskora_message_safety_banner') !== 'hidden');
        } catch {
            setVisible(true);
        }
    }, []);

    const hide = () => {
        setVisible(false);
        try {
            window.localStorage.setItem('taskora_message_safety_banner', 'hidden');
        } catch {
            // The banner can remain session-only when storage is unavailable.
        }
    };

    if (!visible) {
        return null;
    }

    return (
        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-950 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            <div className="flex items-start gap-3">
                <ShieldCheck className="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-200" aria-hidden="true" />
                <div className="min-w-0 flex-1">
                    <p className="text-sm font-semibold">Работайте и передавайте материалы только внутри Таскоры.</p>
                    <p className="mt-1 text-sm leading-6">Не отправляйте контакты, мессенджеры и платежные реквизиты.</p>
                    <Link href="/beta-testing" className={`mt-2 inline-flex text-sm font-semibold text-amber-800 underline-offset-4 hover:underline dark:text-amber-100 ${focusClass}`}>
                        Подробнее
                    </Link>
                </div>
                <button type="button" onClick={hide} aria-label="Скрыть предупреждение" className={`rounded-md p-2 text-amber-700 transition hover:bg-amber-100 dark:text-amber-100 dark:hover:bg-amber-900 ${focusClass}`}>
                    <X className="h-4 w-4" aria-hidden="true" />
                </button>
            </div>
        </div>
    );
}

function ConversationStartCard({ conversation }) {
    const order = conversation.type === 'dispute' ? conversation.order : conversation;

    return (
        <article className="rounded-lg border border-blue-200 bg-white p-4 shadow-sm dark:border-blue-800 dark:bg-slate-950">
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="min-w-0">
                    <p className="text-xs font-semibold uppercase text-blue-700 dark:text-blue-300">Карточка сделки</p>
                    <h2 className="mt-2 break-words text-lg font-semibold text-slate-950 dark:text-white">{order.title}</h2>
                    <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {conversation.type === 'dispute' ? conversation.reason_label : conversation.subtitle}
                    </p>
                </div>
                <div className="grid shrink-0 grid-cols-2 gap-2 text-sm sm:grid-cols-4 lg:grid-cols-2">
                    <MiniStat label="Цена" value={formatMoney(order.price)} />
                    <MiniStat label="Срок" value={order.delivery_days ? `${order.delivery_days} дн.` : 'Не указан'} />
                    <MiniStat label="Статус" value={order.status_label} />
                    <MiniStat label="Оплата" value={order.payment_status_label} />
                </div>
            </div>
        </article>
    );
}

function TimelineCards({ events }) {
    if (!events.length) {
        return null;
    }

    return (
        <div className="space-y-2" aria-label="Системные события заказа">
            {events.map((event) => (
                <article key={event.id} className="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-950">
                    <div className="flex items-start gap-3">
                        <Clock className="mt-0.5 h-4 w-4 shrink-0 text-slate-400" aria-hidden="true" />
                        <div className="min-w-0">
                            <p className="text-sm font-semibold text-slate-950 dark:text-white">{event.label}</p>
                            <p className="mt-1 break-words text-sm leading-6 text-slate-600 dark:text-slate-300">{event.summary}</p>
                            <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">{event.actor_role}: {event.actor} · {event.created_at}</p>
                        </div>
                    </div>
                </article>
            ))}
        </div>
    );
}

function AttachmentStrip({ files }) {
    if (!files.length) {
        return null;
    }

    return (
        <section className="rounded-lg border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950" aria-label="Файлы заказа">
            <div className="flex items-center gap-2">
                <Paperclip className="h-4 w-4 text-slate-500 dark:text-slate-400" aria-hidden="true" />
                <h2 className="text-sm font-semibold text-slate-950 dark:text-white">Последние файлы</h2>
            </div>
            <div className="mt-3 grid gap-2 sm:grid-cols-2">
                {files.slice(0, 4).map((file) => <FileCard key={file.id} file={file} />)}
            </div>
        </section>
    );
}

function MessageList({ messages }) {
    const groupedMessages = useMemo(() => groupMessages(messages), [messages]);

    if (!messages.length) {
        return (
            <div className="rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center dark:border-slate-700 dark:bg-slate-950">
                <p className="font-semibold text-slate-950 dark:text-white">Сообщений пока нет</p>
                <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Начните обсуждение внутри Таскоры, чтобы история сделки была сохранена.</p>
            </div>
        );
    }

    return (
        <div className="space-y-5" aria-label="Лента сообщений">
            {groupedMessages.map((group) => (
                <div key={group.date} className="space-y-3">
                    <div className="flex justify-center">
                        <span className="rounded-md border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-500 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-400">
                            {group.date}
                        </span>
                    </div>
                    {group.items.map((message) => <MessageBubble key={message.id} message={message} />)}
                </div>
            ))}
        </div>
    );
}

function MessageBubble({ message }) {
    if (message.is_system) {
        return (
            <article className="mx-auto max-w-2xl rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-center text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
                <p className="font-semibold">{message.author_role}</p>
                <p className="mt-1 whitespace-pre-line break-words leading-6">{message.body}</p>
                <p className="mt-2 text-xs text-amber-700 dark:text-amber-200">{message.time_label ?? message.created_at}</p>
            </article>
        );
    }

    return (
        <div className={`flex ${message.is_own ? 'justify-end' : 'justify-start'}`}>
            <article className={`max-w-[85%] rounded-lg border px-4 py-3 sm:max-w-[72%] ${
                message.is_own
                    ? 'border-blue-200 bg-blue-600 text-white dark:border-blue-500 dark:bg-blue-500'
                    : 'border-slate-200 bg-white text-slate-800 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100'
            }`}>
                <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
                    <p className="text-sm font-semibold">{message.is_own ? 'Вы' : message.author}</p>
                    <span className={`text-xs ${message.is_own ? 'text-blue-100' : 'text-slate-500 dark:text-slate-400'}`}>{message.author_role}</span>
                    <span className={`text-xs ${message.is_own ? 'text-blue-100' : 'text-slate-500 dark:text-slate-400'}`}>{message.time_label ?? message.created_at}</span>
                </div>
                <p className="mt-2 whitespace-pre-line break-words text-sm leading-6 [overflow-wrap:anywhere]">{message.body}</p>
                {message.is_own && <p className="mt-2 text-right text-xs font-medium text-blue-100">Отправлено</p>}
            </article>
        </div>
    );
}

function MessageComposer({ conversation }) {
    const form = useForm({ body: '' });

    const submit = (event) => {
        event.preventDefault();
        form.post(conversation.message_url, {
            preserveScroll: true,
            onSuccess: () => form.reset('body'),
        });
    };

    if (!conversation.can_reply) {
        return (
            <div className="border-t border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-950">
                <p className="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm font-semibold text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                    Для текущего статуса доступен просмотр переписки. Новые сообщения недоступны.
                </p>
            </div>
        );
    }

    return (
        <form onSubmit={submit} className="sticky bottom-0 z-10 border-t border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-950 sm:p-4">
            <label htmlFor="message_body" className="sr-only">Текст сообщения</label>
            <div className="flex min-w-0 items-end gap-2">
                <textarea
                    id="message_body"
                    name="body"
                    rows={2}
                    aria-label="Текст сообщения"
                    value={form.data.body}
                    onChange={(event) => form.setData('body', event.target.value)}
                    onInput={autoResizeTextarea}
                    className="max-h-36 min-h-12 flex-1 resize-none rounded-lg border border-slate-300 bg-white px-4 py-3 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-950"
                    placeholder="Напишите сообщение…"
                />
                <button
                    type="submit"
                    aria-label={form.processing ? 'Отправляем сообщение' : 'Отправить сообщение'}
                    disabled={form.processing}
                    className={`inline-flex min-h-12 shrink-0 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-500 dark:hover:bg-blue-400 ${focusClass}`}
                >
                    <Send className="h-4 w-4" aria-hidden="true" />
                    <span className="hidden sm:inline">{form.processing ? 'Отправляем...' : 'Отправить'}</span>
                </button>
            </div>
            {form.errors.body && (
                <p className="mt-2 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm font-semibold text-red-700 dark:border-red-800 dark:bg-red-950 dark:text-red-200" role="alert">
                    {form.errors.body}
                </p>
            )}
            <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">Shift+Enter добавляет новую строку. Отправка выполняется кнопкой.</p>
        </form>
    );
}

function OrderDetails({ conversation }) {
    return (
        <div className="mt-4 space-y-4">
            <DetailGrid
                items={[
                    ['Статус', conversation.status_label],
                    ['Оплата', conversation.payment_status_label],
                    ['Цена', formatMoney(conversation.price)],
                    ['Комиссия', `${formatMoney(conversation.platform_fee_amount)} (${conversation.platform_fee_percent}%)`],
                    ['Исполнителю', formatMoney(conversation.performer_amount)],
                    ['Срок', conversation.delivery_days ? `${conversation.delivery_days} дн.` : 'Не указан'],
                    ['Проверка до', conversation.review_hold_until ?? 'Не запущена'],
                ]}
            />
            <Participants customer={conversation.customer} performer={conversation.performer} />
            <QuickLinks conversation={conversation} />
            <Warnings />
            <FilesList files={conversation.files ?? []} />
        </div>
    );
}

function DisputeDetails({ conversation }) {
    return (
        <div className="mt-4 space-y-4">
            <DetailGrid
                items={[
                    ['Статус спора', conversation.status_label],
                    ['Причина', conversation.reason_label],
                    ['Решение', conversation.resolution_label ?? 'Пока нет'],
                    ['Статус заказа', conversation.order.status_label],
                    ['Оплата', conversation.order.payment_status_label],
                    ['Сумма', formatMoney(conversation.order.price)],
                ]}
            />
            {conversation.moderator_comment && (
                <div className="rounded-lg border border-emerald-200 bg-emerald-50 p-3 text-sm leading-6 text-emerald-900 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-100">
                    <p className="font-semibold">Комментарий модератора</p>
                    <p className="mt-1">{conversation.moderator_comment}</p>
                </div>
            )}
            <Participants customer={conversation.participants.customer} performer={conversation.participants.performer} openedBy={conversation.participants.opened_by} />
            <QuickLinks conversation={conversation} />
            <Warnings />
            <FilesList files={conversation.files ?? []} />
        </div>
    );
}

function DetailGrid({ items }) {
    return (
        <div className="grid gap-2">
            {items.map(([label, value]) => (
                <div key={label} className="rounded-lg bg-slate-50 p-3 dark:bg-slate-900">
                    <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{label}</p>
                    <p className="mt-1 break-words text-sm font-semibold text-slate-950 dark:text-white">{value ?? 'Не указано'}</p>
                </div>
            ))}
        </div>
    );
}

function Participants({ customer, performer, openedBy = null }) {
    return (
        <div className="space-y-2">
            <p className="text-sm font-semibold text-slate-950 dark:text-white">Участники</p>
            <Participant user={customer} fallbackRole="Заказчик" />
            <Participant user={performer} fallbackRole="Исполнитель" />
            {openedBy && <Participant user={openedBy} fallbackRole="Открыл спор" />}
        </div>
    );
}

function Participant({ user, fallbackRole }) {
    return (
        <div className="flex min-w-0 items-center gap-3 rounded-lg border border-slate-200 p-3 dark:border-slate-800">
            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-sm font-semibold text-slate-700 dark:bg-slate-900 dark:text-slate-200">
                {initials(user?.name)}
            </div>
            <div className="min-w-0">
                <p className="truncate text-sm font-semibold text-slate-950 dark:text-white">{user?.name ?? 'Не указан'}</p>
                <p className="text-xs text-slate-500 dark:text-slate-400">{user?.role_label ?? fallbackRole}</p>
            </div>
        </div>
    );
}

function QuickLinks({ conversation }) {
    const links = conversation.type === 'dispute'
        ? [
            ['Открыть спор', conversation.dispute_url],
            ['Открыть заказ', conversation.order.order_url],
            ['Открыть рабочую область', conversation.order.workspace_url],
        ]
        : [
            ['Открыть заказ', conversation.order_url],
            ['Открыть рабочую область', conversation.workspace_url],
            ['Открыть спор', conversation.active_dispute_url],
        ];

    return (
        <div className="space-y-2">
            <p className="text-sm font-semibold text-slate-950 dark:text-white">Быстрые ссылки</p>
            <div className="grid gap-2">
                {links.filter(([, href]) => Boolean(href)).map(([label, href]) => <ActionLink key={label} href={href}>{label}</ActionLink>)}
            </div>
        </div>
    );
}

function Warnings() {
    return (
        <div className="space-y-2">
            <p className="rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm font-semibold text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">Оплата пока в stub-режиме.</p>
            <p className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm font-semibold text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">Не передавайте контакты вне платформы.</p>
        </div>
    );
}

function FilesList({ files }) {
    return (
        <div className="space-y-2">
            <p className="text-sm font-semibold text-slate-950 dark:text-white">Файлы</p>
            {files.length > 0 ? (
                <div className="grid gap-2">
                    {files.map((file) => <FileCard key={file.id} file={file} />)}
                </div>
            ) : (
                <p className="rounded-lg border border-dashed border-slate-300 p-3 text-sm text-slate-500 dark:border-slate-700 dark:text-slate-400">Файлов пока нет.</p>
            )}
        </div>
    );
}

function FileCard({ file }) {
    return (
        <div className="min-w-0 rounded-lg border border-slate-200 p-3 dark:border-slate-800">
            <div className="flex min-w-0 items-start gap-2">
                <FileText className="mt-0.5 h-4 w-4 shrink-0 text-slate-500 dark:text-slate-400" aria-hidden="true" />
                <div className="min-w-0 flex-1">
                    <p className="break-words text-sm font-semibold text-slate-950 dark:text-white">{file.original_name}</p>
                    <p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                        {file.author_role}: {file.author} · {formatBytes(file.size)} · {file.status_label}
                    </p>
                </div>
            </div>
            {file.download_url ? (
                <Link href={file.download_url} className={`mt-3 inline-flex rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900 ${focusClass}`}>
                    Скачать
                </Link>
            ) : (
                <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">Скачивание недоступно для этой роли или статуса файла.</p>
            )}
        </div>
    );
}

function MiniStat({ label, value }) {
    return (
        <div className="rounded-lg bg-slate-50 p-3 dark:bg-slate-900">
            <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{label}</p>
            <p className="mt-1 text-sm font-semibold text-slate-950 dark:text-white">{value}</p>
        </div>
    );
}

function Select({ label, name, value, options }) {
    return (
        <label className="block">
            <span className="text-sm font-semibold text-slate-900 dark:text-white">{label}</span>
            <select
                name={name}
                defaultValue={value}
                className="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-950"
            >
                {options.map((option) => <option key={option.value} value={option.value}>{option.label}</option>)}
            </select>
        </label>
    );
}

function Badge({ children, tone = 'default' }) {
    const classes = {
        default: 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
        blue: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100',
        red: 'border-red-200 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-100',
        slate: 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
    }[tone] ?? statusTone[tone] ?? paymentTone[tone] ?? 'border-slate-200 bg-slate-50 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200';

    return <span className={`inline-flex rounded-md border px-2 py-1 text-xs font-semibold ${classes}`}>{children}</span>;
}

function Chip({ children }) {
    return (
        <span className="rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100">
            {children}
        </span>
    );
}

function ActionLink({ href, children, variant = 'light' }) {
    const classes = variant === 'dark'
        ? 'bg-slate-950 text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200'
        : 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800';

    return (
        <Link href={href} className={`inline-flex max-w-full items-center justify-center rounded-md px-3 py-2 text-center text-sm font-semibold transition ${classes} ${focusClass}`}>
            {children}
        </Link>
    );
}

function PageLink({ href, children }) {
    return (
        <Link href={href} className={`rounded-md border border-slate-300 px-3 py-2 font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-900 ${focusClass}`}>
            {children}
        </Link>
    );
}

function groupMessages(messages) {
    return messages.reduce((groups, message) => {
        const date = message.date_label ?? 'Без даты';
        const existing = groups.find((group) => group.date === date);

        if (existing) {
            existing.items.push(message);
            return groups;
        }

        groups.push({ date, items: [message] });
        return groups;
    }, []);
}

function autoResizeTextarea(event) {
    event.currentTarget.style.height = 'auto';
    event.currentTarget.style.height = `${Math.min(event.currentTarget.scrollHeight, 144)}px`;
}

function initials(name) {
    if (!name) {
        return 'Т';
    }

    return name
        .trim()
        .split(/\s+/)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toUpperCase();
}

function formatMoney(amount) {
    return `${currency.format(amount ?? 0)} ₽`;
}

function formatBytes(size) {
    if (!size) {
        return '0 Б';
    }

    if (size < 1024) {
        return `${size} Б`;
    }

    if (size < 1024 * 1024) {
        return `${Math.round(size / 1024)} КБ`;
    }

    return `${(size / 1024 / 1024).toFixed(1)} МБ`;
}

function labelFor(options, value) {
    return options.find((option) => option.value === value)?.label ?? value;
}

function queryUrl(filters, updates = {}) {
    const next = { ...filters, ...updates };
    const params = new URLSearchParams();

    if (next.tab && next.tab !== 'all') {
        params.set('tab', next.tab);
    }

    if (next.q) {
        params.set('q', next.q);
    }

    if (next.status) {
        params.set('status', next.status);
    }

    if (next.sort && next.sort !== 'newest') {
        params.set('sort', next.sort);
    }

    if (next.page) {
        params.set('page', next.page);
    }

    const query = params.toString();

    return query ? `/messages?${query}` : '/messages';
}
