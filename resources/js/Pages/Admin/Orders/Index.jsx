import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const formatMoney = (amount) => new Intl.NumberFormat('ru-RU').format(amount ?? 0) + ' ₽';

const statusTone = {
    awaiting_payment: 'bg-amber-50 text-amber-700 ring-amber-200',
    in_progress: 'bg-blue-50 text-blue-700 ring-blue-200',
    submitted_for_review: 'bg-purple-50 text-purple-700 ring-purple-200',
    revision_requested: 'bg-orange-50 text-orange-700 ring-orange-200',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    disputed: 'bg-red-50 text-red-700 ring-red-200',
    canceled: 'bg-slate-100 text-slate-700 ring-slate-200',
};

const paymentTone = {
    unpaid: 'bg-amber-50 text-amber-700 ring-amber-200',
    held: 'bg-blue-50 text-blue-700 ring-blue-200',
    released: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    refunded: 'bg-red-50 text-red-700 ring-red-200',
    canceled: 'bg-slate-100 text-slate-700 ring-slate-200',
};

export default function Index({ orders = {}, filters = {}, summary = {}, options = {} }) {
    const rows = orders.data ?? [];
    const form = useForm({
        q: filters.q ?? '',
        status: filters.status ?? 'all',
        payment_status: filters.payment_status ?? 'all',
        source_type: filters.source_type ?? 'all',
        has_dispute: filters.has_dispute ?? 'all',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
        price_min: filters.price_min ?? '',
        price_max: filters.price_max ?? '',
        customer_id: filters.customer_id ?? '',
        performer_id: filters.performer_id ?? '',
        sort: filters.sort ?? 'newest',
    });

    const submit = (event) => {
        event.preventDefault();

        router.get('/admin/orders', compactFilters(form.data), {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const activeFilters = filterChips(filters, options);
    const activeFilterCount = activeFilters.length;
    const [filtersOpen, setFiltersOpen] = useState(false);

    return (
        <DashboardLayout>
            <Head title="Заказы" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Администрирование</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Заказы</h1>
                        <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                            Диагностика заказов только для чтения: статусы, участники, источник, споры, события рабочей области и внутренний финансовый журнал. Статусы и платежи здесь не изменяются.
                        </p>
                    </div>
                    <Link href="/admin/dashboard" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                        В админ-панель
                    </Link>
                </div>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Summary title="Всего заказов" value={summary.total ?? 0} />
                    <Summary title="Найдено" value={summary.filtered ?? 0} />
                    <Summary title="Активные споры" value={summary.active_disputes ?? 0} />
                    <Summary title="Удерживается" value={formatMoney(summary.held_amount ?? 0)} />
                </div>

                <form onSubmit={submit} className="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-3 border-b border-slate-100 pb-4 lg:hidden">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <p className="text-base font-semibold text-slate-950">Фильтры</p>
                                {activeFilterCount > 0 && (
                                    <p className="mt-1 text-sm text-slate-500">
                                        Активно: {activeFilterCount}
                                    </p>
                                )}
                            </div>
                            <button
                                type="button"
                                aria-controls="admin-order-filters"
                                aria-expanded={filtersOpen}
                                onClick={() => setFiltersOpen((open) => !open)}
                                className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                            >
                                {filtersOpen ? 'Скрыть фильтры' : 'Показать фильтры'}
                            </button>
                        </div>
                        <Link href="/admin/orders" className="inline-flex w-full justify-center rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 sm:w-auto">
                            Сбросить фильтры
                        </Link>
                    </div>

                    <div id="admin-order-filters" className={`${filtersOpen ? 'block' : 'hidden'} pt-5 lg:block lg:pt-0`}>
                        <div className="hidden lg:mb-5 lg:block">
                            <h2 className="text-lg font-semibold text-slate-950">Фильтры</h2>
                        </div>
                        <div className="grid gap-4 lg:grid-cols-6">
                        <label className="lg:col-span-2">
                            <span className="text-sm font-medium text-slate-700">Поиск</span>
                            <input
                                value={form.data.q}
                                onChange={(event) => form.setData('q', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                placeholder="ID, название, email заказчика или исполнителя"
                            />
                        </label>
                        <Select label="Статус" value={form.data.status} onChange={(value) => form.setData('status', value)} options={options.statuses} />
                        <Select label="Оплата" value={form.data.payment_status} onChange={(value) => form.setData('payment_status', value)} options={options.payment_statuses} />
                        <Select label="Источник" value={form.data.source_type} onChange={(value) => form.setData('source_type', value)} options={options.source_types} />
                        <Select label="Спор" value={form.data.has_dispute} onChange={(value) => form.setData('has_dispute', value)} options={options.has_dispute} />
                        <label>
                            <span className="text-sm font-medium text-slate-700">Дата от</span>
                            <input
                                type="date"
                                value={form.data.date_from}
                                onChange={(event) => form.setData('date_from', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <label>
                            <span className="text-sm font-medium text-slate-700">Дата до</span>
                            <input
                                type="date"
                                value={form.data.date_to}
                                onChange={(event) => form.setData('date_to', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <label>
                            <span className="text-sm font-medium text-slate-700">Цена от</span>
                            <input
                                type="number"
                                min="0"
                                value={form.data.price_min}
                                onChange={(event) => form.setData('price_min', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <label>
                            <span className="text-sm font-medium text-slate-700">Цена до</span>
                            <input
                                type="number"
                                min="0"
                                value={form.data.price_max}
                                onChange={(event) => form.setData('price_max', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <label>
                            <span className="text-sm font-medium text-slate-700">ID заказчика</span>
                            <input
                                type="number"
                                min="1"
                                value={form.data.customer_id}
                                onChange={(event) => form.setData('customer_id', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <label>
                            <span className="text-sm font-medium text-slate-700">ID исполнителя</span>
                            <input
                                type="number"
                                min="1"
                                value={form.data.performer_id}
                                onChange={(event) => form.setData('performer_id', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            />
                        </label>
                        <Select label="Сортировка" value={form.data.sort} onChange={(value) => form.setData('sort', value)} options={options.sorts} />
                        </div>

                        <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                            <button type="submit" className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                Применить
                            </button>
                            <Link href="/admin/orders" className="hidden rounded-md border border-slate-300 bg-white px-4 py-2 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50 lg:inline-flex">
                                Сбросить фильтры
                            </Link>
                        </div>
                    </div>
                </form>

                {activeFilters.length > 0 && (
                    <div className="mt-5 flex flex-wrap gap-2">
                        {activeFilters.map((chip) => (
                            <span key={chip.key} className="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 ring-1 ring-blue-100">
                                {chip.label}
                            </span>
                        ))}
                    </div>
                )}

                <div className="mt-8 space-y-4">
                    {rows.length > 0 ? (
                        rows.map((order) => <OrderCard key={order.id} order={order} />)
                    ) : (
                        <EmptyState title="Заказы не найдены" text="Измените поиск или фильтры, чтобы увидеть заказы." />
                    )}
                </div>

                {(orders.prev_page_url || orders.next_page_url) && (
                    <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-slate-500">
                            Страница {orders.current_page} из {orders.last_page}. Найдено: {orders.total}
                        </p>
                        <div className="flex gap-2">
                            {orders.prev_page_url && <Link href={orders.prev_page_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Назад</Link>}
                            {orders.next_page_url && <Link href={orders.next_page_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Вперед</Link>}
                        </div>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}

function OrderCard({ order }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
                <div className="min-w-0">
                    <div className="flex flex-wrap items-center gap-2">
                        <Badge tone={statusTone[order.status]}>{order.status_label}</Badge>
                        <Badge tone={paymentTone[order.payment_status]}>{order.payment_status_label}</Badge>
                        <Badge>{order.source_type_label}</Badge>
                        {order.has_active_dispute && <Badge tone="bg-red-50 text-red-700 ring-red-200">Активный спор #{order.active_dispute_id}</Badge>}
                    </div>
                    <h2 className="mt-4 break-words text-xl font-semibold text-slate-950">#{order.id} {order.title}</h2>
                    <div className="mt-3 grid gap-3 text-sm sm:grid-cols-2 xl:grid-cols-4">
                        <Info label="Заказчик" value={order.customer?.email ?? '—'} />
                        <Info label="Исполнитель" value={order.performer?.email ?? '—'} />
                        <Info label="Создан" value={order.created_at ?? '—'} />
                        <Info label="Обновлен" value={order.updated_at ?? '—'} />
                    </div>
                </div>
                <div className="shrink-0 xl:text-right">
                    <p className="text-2xl font-semibold text-slate-950">{formatMoney(order.price)}</p>
                    <p className="mt-1 text-sm text-slate-500">Комиссия: {formatMoney(order.platform_fee_amount)}</p>
                    <p className="text-sm text-slate-500">Исполнителю: {formatMoney(order.performer_amount)}</p>
                    <Link href={order.show_url} className="mt-4 inline-flex rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                        Открыть
                    </Link>
                </div>
            </div>

            <div className="mt-5 grid gap-3 border-t border-slate-100 pt-4 text-sm sm:grid-cols-2 lg:grid-cols-4">
                <Info label="Срок" value={`${order.delivery_days} дн.`} />
                <Info label="Сдан" value={order.submitted_at ?? '—'} />
                <Info label="Проверка до" value={order.review_hold_until ?? '—'} />
                <Info label="Материалы" value={`Сообщения: ${order.messages_count}, файлы: ${order.files_count}, события: ${order.events_count}`} />
            </div>
        </article>
    );
}

function Summary({ title, value }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-slate-500">{title}</p>
            <p className="mt-3 text-2xl font-semibold text-slate-950">{value}</p>
        </article>
    );
}

function Select({ label, value, onChange, options = [] }) {
    return (
        <label>
            <span className="text-sm font-medium text-slate-700">{label}</span>
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>{option.label}</option>
                ))}
            </select>
        </label>
    );
}

function Info({ label, value }) {
    return (
        <div className="min-w-0">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-1 break-words text-slate-800">{value}</p>
        </div>
    );
}

function Badge({ children, tone = 'bg-slate-100 text-slate-700 ring-slate-200' }) {
    return <span className={`rounded-md px-3 py-1 text-xs font-semibold ring-1 ${tone}`}>{children}</span>;
}

function EmptyState({ title, text }) {
    return (
        <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-10 text-center">
            <h2 className="text-2xl font-semibold text-slate-950">{title}</h2>
            <p className="mt-3 text-sm leading-6 text-slate-600">{text}</p>
        </div>
    );
}

function compactFilters(data) {
    return Object.fromEntries(
        Object.entries(data).filter(([, value]) => value !== '' && value !== 'all'),
    );
}

function filterChips(filters, options) {
    const selectOptions = {
        status: options.statuses,
        payment_status: options.payment_statuses,
        source_type: options.source_types,
        has_dispute: options.has_dispute,
        sort: options.sorts,
    };

    return Object.entries(filters)
        .filter(([, value]) => value !== '' && value !== 'all' && value !== 'newest')
        .map(([key, value]) => {
            const option = selectOptions[key]?.find((item) => item.value === value);

            return {
                key,
                label: `${filterLabels[key] ?? key}: ${option?.label ?? value}`,
            };
        });
}

const filterLabels = {
    q: 'Поиск',
    status: 'Статус',
    payment_status: 'Оплата',
    source_type: 'Источник',
    has_dispute: 'Спор',
    date_from: 'Дата от',
    date_to: 'Дата до',
    price_min: 'Цена от',
    price_max: 'Цена до',
    customer_id: 'ID заказчика',
    performer_id: 'ID исполнителя',
    sort: 'Сортировка',
};
