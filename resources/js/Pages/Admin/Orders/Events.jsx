import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

export default function Events({ order, events = {}, filters = {}, options = {}, links = {} }) {
    const rows = events.data ?? [];
    const form = useForm({
        type: filters.type ?? 'all',
        sort: filters.sort ?? 'newest',
    });

    const submit = (event) => {
        event.preventDefault();

        router.get(`/admin/orders/${order.id}/events`, compactFilters(form.data), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    return (
        <DashboardLayout>
            <Head title={`События заказа #${order.id}`} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div className="min-w-0">
                        <p className="text-sm font-semibold uppercase text-blue-700">Журнал событий</p>
                        <h1 className="mt-3 break-words text-4xl font-semibold tracking-normal text-slate-950">
                            Заказ #{order.id}: {order.title}
                        </h1>
                        <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                            Полный read-only журнал workspace и order events. Фильтр влияет только на отображение.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href={links.show} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            Карточка заказа
                        </Link>
                        <Link href={links.ledger} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Ledger
                        </Link>
                    </div>
                </div>

                <form onSubmit={submit} className="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <Select label="Тип события" value={form.data.type} onChange={(value) => form.setData('type', value)} options={options.types} />
                        <Select label="Сортировка" value={form.data.sort} onChange={(value) => form.setData('sort', value)} options={options.sorts} />
                    </div>
                    <div className="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
                        <button type="submit" className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Применить
                        </button>
                        <Link href={`/admin/orders/${order.id}/events`} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            Сбросить
                        </Link>
                    </div>
                </form>

                <div className="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col justify-between gap-2 sm:flex-row sm:items-center">
                        <h2 className="text-xl font-semibold text-slate-950">События</h2>
                        <p className="text-sm text-slate-500">Найдено: {events.total ?? rows.length}</p>
                    </div>

                    {rows.length > 0 ? (
                        <ul className="mt-5">
                            {rows.map((event) => (
                                <li key={event.id} className="border-t border-slate-100 py-4 first:border-t-0 first:pt-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge>{event.type_label}</Badge>
                                        <span className="text-sm font-semibold text-slate-800">{event.actor}</span>
                                        <span className="text-sm text-slate-500">{event.created_at}</span>
                                    </div>
                                    {event.summary && <p className="mt-3 break-words text-sm leading-6 text-slate-700">{event.summary}</p>}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <div className="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                            <p className="text-lg font-semibold text-slate-950">Событий не найдено</p>
                            <p className="mt-2 text-sm text-slate-500">Попробуйте другой тип события или сбросьте фильтр.</p>
                        </div>
                    )}
                </div>

                {(events.prev_page_url || events.next_page_url) && (
                    <div className="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-slate-500">
                            Страница {events.current_page} из {events.last_page}. Найдено: {events.total}
                        </p>
                        <div className="flex gap-2">
                            {events.prev_page_url && <Link href={events.prev_page_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Назад</Link>}
                            {events.next_page_url && <Link href={events.next_page_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Вперед</Link>}
                        </div>
                    </div>
                )}
            </section>
        </DashboardLayout>
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

function Badge({ children }) {
    return <span className="rounded-md bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">{children}</span>;
}

function compactFilters(data) {
    return Object.fromEntries(
        Object.entries(data).filter(([, value]) => value !== '' && value !== 'all' && value !== 'newest'),
    );
}
