import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const statusClasses = {
    awaiting_payment: 'border-amber-200 bg-amber-50 text-amber-800 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100',
    in_progress: 'border-blue-200 bg-blue-50 text-blue-800 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100',
    submitted_for_review: 'border-purple-200 bg-purple-50 text-purple-800 dark:border-purple-700 dark:bg-purple-950 dark:text-purple-100',
    revision_requested: 'border-orange-200 bg-orange-50 text-orange-800 dark:border-orange-700 dark:bg-orange-950 dark:text-orange-100',
    completed: 'border-emerald-200 bg-emerald-50 text-emerald-800 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-100',
    disputed: 'border-red-200 bg-red-50 text-red-800 dark:border-red-700 dark:bg-red-950 dark:text-red-100',
    canceled: 'border-slate-200 bg-slate-100 text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200',
};

const focusClass = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950';

export default function Index({ conversations = [], pagination = {}, filters = {}, tabs = [], orderStatusOptions = [], sortOptions = [] }) {
    return (
        <DashboardLayout>
            <Head title="Сообщения" />

            <section className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700 dark:text-blue-300">Единый inbox</p>
                        <h1 className="mt-3 text-3xl font-semibold tracking-normal text-slate-950 dark:text-white sm:text-4xl">Сообщения</h1>
                        <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Заказы и споры собраны в одном месте. Непрочитанные считаются по сообщениям других участников.
                        </p>
                    </div>

                    {filters.active_count > 0 && (
                        <Link
                            href="/messages"
                            className={`rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 ${focusClass}`}
                        >
                            Сбросить фильтры
                        </Link>
                    )}
                </div>

                <div className="mt-6 flex flex-wrap gap-2">
                    {tabs.map((tab) => (
                        <Link
                            key={tab.value}
                            href={queryUrl(filters, { tab: tab.value, page: null })}
                            className={`rounded-md px-3 py-2 text-sm font-semibold transition ${focusClass} ${
                                filters.tab === tab.value
                                    ? 'bg-blue-600 text-white dark:bg-blue-500'
                                    : 'border border-slate-200 bg-white text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-blue-950 dark:hover:text-blue-100'
                            }`}
                        >
                            {tab.label}
                        </Link>
                    ))}
                </div>

                {filters.active_count > 0 && (
                    <div className="mt-4 flex flex-wrap gap-2" aria-label="Активные фильтры">
                        {filters.q && <Chip>Поиск: {filters.q}</Chip>}
                        {filters.status && <Chip>Статус: {labelFor(orderStatusOptions, filters.status)}</Chip>}
                        {filters.sort !== 'newest' && <Chip>Сортировка: {labelFor(sortOptions, filters.sort)}</Chip>}
                        {filters.tab !== 'all' && <Chip>Раздел: {labelFor(tabs, filters.tab)}</Chip>}
                    </div>
                )}

                <form action="/messages" method="get" className="mt-5 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 md:grid-cols-[1fr_220px_220px_auto]">
                    {filters.tab !== 'all' && <input type="hidden" name="tab" value={filters.tab} />}
                    <div>
                        <label htmlFor="messages_q" className="text-sm font-semibold text-slate-900 dark:text-white">Поиск</label>
                        <input
                            id="messages_q"
                            name="q"
                            type="search"
                            defaultValue={filters.q ?? ''}
                            placeholder="Название заказа или участник"
                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-950"
                        />
                    </div>

                    <div>
                        <label htmlFor="messages_status" className="text-sm font-semibold text-slate-900 dark:text-white">Статус заказа</label>
                        <select
                            id="messages_status"
                            name="status"
                            defaultValue={filters.status ?? ''}
                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-950"
                        >
                            <option value="">Все статусы</option>
                            {orderStatusOptions.map((option) => (
                                <option key={option.value} value={option.value}>{option.label}</option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label htmlFor="messages_sort" className="text-sm font-semibold text-slate-900 dark:text-white">Сортировка</label>
                        <select
                            id="messages_sort"
                            name="sort"
                            defaultValue={filters.sort ?? 'newest'}
                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-950"
                        >
                            {sortOptions.map((option) => (
                                <option key={option.value} value={option.value}>{option.label}</option>
                            ))}
                        </select>
                    </div>

                    <div className="flex items-end">
                        <button
                            type="submit"
                            className={`w-full rounded-md bg-slate-950 px-5 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 ${focusClass}`}
                        >
                            Показать
                        </button>
                    </div>
                </form>

                <div className="mt-6 space-y-4">
                    {conversations.length > 0 ? (
                        conversations.map((conversation) => <ConversationCard key={`${conversation.type}-${conversation.id}`} conversation={conversation} />)
                    ) : (
                        <div className="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
                            <p className="text-lg font-semibold text-slate-950 dark:text-white">Диалогов пока нет</p>
                            <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                Здесь появятся переписки по заказам и спорам, доступные вашему аккаунту.
                            </p>
                        </div>
                    )}
                </div>

                {pagination.last_page > 1 && (
                    <div className="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-600 dark:text-slate-300">
                        <p>
                            Показано {pagination.from ?? 0}-{pagination.to ?? 0} из {pagination.total ?? 0}
                        </p>
                        <div className="flex gap-2">
                            {pagination.prev_page_url && <PageLink href={pagination.prev_page_url}>Назад</PageLink>}
                            {pagination.next_page_url && <PageLink href={pagination.next_page_url}>Дальше</PageLink>}
                        </div>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}

function ConversationCard({ conversation }) {
    return (
        <article className={`rounded-lg border p-5 shadow-sm ${conversation.unread_count > 0 ? 'border-blue-200 bg-blue-50/70 dark:border-blue-700 dark:bg-blue-950/35' : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900'}`}>
            <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <span className="rounded-md bg-slate-950 px-2.5 py-1 text-xs font-semibold text-white dark:bg-white dark:text-slate-950">
                            {conversation.type_label}
                        </span>
                        {conversation.type === 'dispute' && (
                            <span className="rounded-md bg-red-600 px-2.5 py-1 text-xs font-semibold text-white">Спор</span>
                        )}
                        <span className={`rounded-md border px-2.5 py-1 text-xs font-semibold ${statusClasses[conversation.status] ?? statusClasses.canceled}`}>
                            {conversation.status_label}
                        </span>
                        {conversation.dispute_status_label && (
                            <span className="rounded-md border border-red-200 bg-white px-2.5 py-1 text-xs font-semibold text-red-700 dark:border-red-700 dark:bg-slate-950 dark:text-red-200">
                                {conversation.dispute_status_label}
                            </span>
                        )}
                        {conversation.unread_count > 0 && (
                            <span className="rounded-md bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white">
                                {conversation.unread_count} непрочит.
                            </span>
                        )}
                    </div>

                    <h2 className="mt-3 break-words text-xl font-semibold text-slate-950 dark:text-white">{conversation.title}</h2>
                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{conversation.subtitle}</p>
                    <p className="mt-3 text-sm font-semibold text-slate-800 dark:text-slate-100">
                        {conversation.participant.role_label}: {conversation.participant.name}
                    </p>
                    <p className="mt-3 whitespace-pre-line break-words text-sm leading-6 text-slate-600 dark:text-slate-300">
                        {conversation.last_message_author && <span className="font-semibold text-slate-900 dark:text-white">{conversation.last_message_author}: </span>}
                        {conversation.last_message}
                    </p>
                    <p className="mt-2 text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{conversation.last_message_at}</p>
                </div>

                <div className="flex shrink-0 flex-wrap gap-2 lg:justify-end">
                    <Link
                        href={conversation.url}
                        className={`rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 ${focusClass}`}
                    >
                        Открыть
                    </Link>
                </div>
            </div>
        </article>
    );
}

function Chip({ children }) {
    return (
        <span className="rounded-md border border-blue-200 bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-800 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-100">
            {children}
        </span>
    );
}

function PageLink({ href, children }) {
    return (
        <Link
            href={href}
            className={`rounded-md border border-slate-300 bg-white px-4 py-2 font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 ${focusClass}`}
        >
            {children}
        </Link>
    );
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
