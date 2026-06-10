import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

export default function Index({ disputes, currentStatus, statusTabs }) {
    return (
        <DashboardLayout>
            <Head title="Споры и арбитраж" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <p className="text-sm font-semibold uppercase text-red-700">Модерация</p>
                <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Споры и арбитраж</h1>

                <div className="mt-6 flex flex-wrap gap-2">
                    {statusTabs.map((tab) => (
                        <Link
                            key={tab.value}
                            href={`/moderator/disputes?status=${tab.value}`}
                            className={`rounded-md px-4 py-2 text-sm font-semibold ring-1 ${
                                currentStatus === tab.value
                                    ? 'bg-slate-950 text-white ring-slate-950'
                                    : 'bg-white text-slate-700 ring-slate-200 hover:bg-slate-50'
                            }`}
                        >
                            {tab.label}
                        </Link>
                    ))}
                </div>

                <div className="mt-6 rounded-lg border border-slate-200 bg-white shadow-sm">
                    {disputes.length > 0 ? (
                        <div className="divide-y divide-slate-200">
                            {disputes.map((dispute) => (
                                <article key={dispute.id} className="grid gap-4 p-5 lg:grid-cols-[1fr_180px] lg:items-center">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="rounded-md bg-red-50 px-3 py-1 text-xs font-semibold text-red-700 ring-1 ring-red-200">
                                                {dispute.status_label}
                                            </span>
                                            <span className="rounded-md bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                                Заказ #{dispute.order_id}
                                            </span>
                                        </div>
                                        <h2 className="mt-3 text-lg font-semibold text-slate-950">{dispute.order_title}</h2>
                                        <p className="mt-2 text-sm leading-6 text-slate-600">
                                            {dispute.reason_label} · открыл: {dispute.opened_by ?? 'не указан'} · сумма {currency.format(dispute.price ?? 0)} ₽
                                        </p>
                                        <p className="mt-1 text-sm text-slate-500">
                                            Заказчик: {dispute.customer ?? 'не указан'} · Исполнитель: {dispute.performer ?? 'не указан'} · {dispute.created_at}
                                        </p>
                                    </div>
                                    <Link href={dispute.show_url} className="rounded-md bg-slate-950 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-slate-800">
                                        Рассмотреть
                                    </Link>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="p-8 text-center">
                            <p className="text-sm font-semibold text-slate-950">Споров в этом статусе нет</p>
                            <p className="mt-2 text-sm text-slate-600">Новые обращения появятся здесь после открытия спора участником заказа.</p>
                        </div>
                    )}
                </div>
            </section>
        </DashboardLayout>
    );
}
