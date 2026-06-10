import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const severityClasses = {
    info: 'border-blue-200 bg-blue-50 text-blue-800',
    success: 'border-emerald-200 bg-emerald-50 text-emerald-800',
    warning: 'border-amber-200 bg-amber-50 text-amber-800',
    danger: 'border-red-200 bg-red-50 text-red-800',
};

const iconLabels = {
    service: 'US',
    task: 'ЗД',
    offer: 'ОТ',
    order: 'ЗК',
    message: 'СМ',
    file: 'ФЛ',
    dispute: 'СП',
};

export default function Index({ items = [], unreadCount = 0 }) {
    return (
        <DashboardLayout>
            <Head title="Уведомления" />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Активность</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Уведомления</h1>
                        <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                            Здесь собраны события по услугам, заданиям, заказам, сообщениям и спорам внутри Таскоры.
                        </p>
                    </div>

                    {unreadCount > 0 && (
                        <Link
                            href="/notifications/read-all"
                            method="post"
                            as="button"
                            className="rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Отметить все прочитанными
                        </Link>
                    )}
                </div>

                {items.length > 0 ? (
                    <div className="mt-8 space-y-4">
                        {items.map((notification) => (
                            <article
                                key={notification.id}
                                className={`rounded-lg border p-5 shadow-sm ${
                                    notification.is_read
                                        ? 'border-slate-200 bg-white'
                                        : 'border-blue-200 bg-blue-50/60'
                                }`}
                            >
                                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="flex gap-4">
                                        <span
                                            className={`grid h-11 w-11 shrink-0 place-items-center rounded-md border text-xs font-semibold ${
                                                severityClasses[notification.severity] ?? severityClasses.info
                                            }`}
                                        >
                                            {iconLabels[notification.icon] ?? 'IN'}
                                        </span>
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                {!notification.is_read && (
                                                    <span className="rounded-md bg-blue-600 px-2 py-1 text-xs font-semibold text-white">
                                                        Новое
                                                    </span>
                                                )}
                                                <span className="text-xs font-semibold uppercase text-slate-500">
                                                    {notification.created_at}
                                                </span>
                                            </div>
                                            <h2 className="mt-2 text-lg font-semibold text-slate-950">{notification.title}</h2>
                                            <p className="mt-2 text-sm leading-6 text-slate-600">{notification.body}</p>
                                        </div>
                                    </div>

                                    <div className="flex shrink-0 flex-wrap gap-2 sm:justify-end">
                                        {notification.url && (
                                            <Link
                                                href={notification.url}
                                                className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                            >
                                                Открыть
                                            </Link>
                                        )}
                                        {!notification.is_read && (
                                            <Link
                                                href={`/notifications/${notification.id}/read`}
                                                method="post"
                                                as="button"
                                                className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                            >
                                                Отметить прочитанным
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <p className="text-lg font-semibold text-slate-950">Уведомлений пока нет</p>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Новые события по заказам, заданиям и спорам появятся здесь.
                        </p>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}
