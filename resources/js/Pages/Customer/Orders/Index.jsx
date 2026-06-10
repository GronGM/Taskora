import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

const statusClasses = {
    awaiting_payment: 'bg-amber-50 text-amber-700',
    in_progress: 'bg-blue-50 text-blue-700',
    submitted_for_review: 'bg-purple-50 text-purple-700',
    revision_requested: 'bg-orange-50 text-orange-700',
    completed: 'bg-emerald-50 text-emerald-700',
    disputed: 'bg-red-50 text-red-700',
    canceled: 'bg-slate-100 text-slate-700',
};

export default function Index({ orders, statusLabels }) {
    return (
        <DashboardLayout>
            <Head title="Мои заказы" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Заказчик</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-normal text-slate-950">Мои заказы</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Заказы из услуг и выбранных откликов. Оплата пока работает как локальная заглушка.
                        </p>
                    </div>
                    <Link href="/catalog" className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                        Найти услугу
                    </Link>
                </div>

                {orders.length > 0 ? (
                    <div className="mt-8 grid gap-4">
                        {orders.map((order) => (
                            <article key={order.id} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className={`rounded-md px-3 py-1 text-xs font-semibold ${statusClasses[order.status] ?? statusClasses.awaiting_payment}`}>
                                                {statusLabels[order.status]}
                                            </span>
                                            <span className="rounded-md bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                                {order.source_label}
                                            </span>
                                        </div>
                                        <h2 className="mt-4 text-2xl font-semibold text-slate-950">{order.title}</h2>
                                        <div className="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-4">
                                            <p>Исполнитель: <span className="font-semibold text-slate-950">{order.participant}</span></p>
                                            <p>Сумма: <span className="font-semibold text-slate-950">{currency.format(order.price)} ₽</span></p>
                                            <p>Срок: <span className="font-semibold text-slate-950">{order.delivery_days} дн.</span></p>
                                            <p>Тип: <span className="font-semibold text-slate-950">{order.source_label}</span></p>
                                        </div>
                                    </div>
                                    <div className="flex flex-wrap gap-3">
                                        <Link href={order.workspace_url} className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                                            Открыть рабочую область
                                        </Link>
                                        <Link href={order.show_url} className="rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                                            Карточка
                                        </Link>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Заказов пока нет</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Закажите опубликованную услугу или выберите исполнителя из откликов на задание.
                        </p>
                        <Link href="/catalog" className="mt-6 inline-flex rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                            Перейти в каталог
                        </Link>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}
