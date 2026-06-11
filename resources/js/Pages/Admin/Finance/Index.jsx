import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const formatMoney = (amount) => new Intl.NumberFormat('ru-RU').format(amount ?? 0) + ' ₽';

export default function AdminFinance({ summary, operations, webhookEvents, labels }) {
    const operationTypes = labels?.operationTypes ?? {};
    const operationStatuses = labels?.operationStatuses ?? {};
    const webhookStatuses = labels?.webhookStatuses ?? {};

    const cards = [
        { title: 'Сумма в удержании', value: formatMoney(summary.escrow_amount) },
        { title: 'Комиссия платформы', value: formatMoney(summary.platform_fee_amount) },
        { title: 'Выплачено исполнителям', value: formatMoney(summary.paid_to_performers_amount) },
        { title: 'Возвращено заказчикам', value: formatMoney(summary.refunded_to_customers_amount) },
        { title: 'Операции оплаты', value: summary.payment_operations_count },
    ];

    return (
        <DashboardLayout>
            <Head title="Финансовая сводка" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Администрирование</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Финансовая сводка</h1>
                    </div>
                    <Link href="/admin/dashboard" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                        Вернуться в админ-панель
                    </Link>
                </div>

                <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    Реальные платежи не подключены, данные основаны на внутреннем финансовом журнале.
                </div>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                    {cards.map((card) => (
                        <article key={card.title} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm text-slate-500">{card.title}</p>
                            <p className="mt-3 text-2xl font-semibold text-slate-950">{card.value}</p>
                        </article>
                    ))}
                </div>

                <section className="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-xl font-semibold text-slate-950">Платежные операции</h2>
                    <div className="mt-5 overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-4 py-3 font-semibold">Дата</th>
                                    <th className="px-4 py-3 font-semibold">Заказ</th>
                                    <th className="px-4 py-3 font-semibold">Тип</th>
                                    <th className="px-4 py-3 font-semibold">Статус</th>
                                    <th className="px-4 py-3 text-right font-semibold">Сумма</th>
                                    <th className="px-4 py-3 font-semibold">Провайдер</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {operations.length === 0 && (
                                    <tr>
                                        <td colSpan="6" className="px-4 py-8 text-center text-slate-500">
                                            Платежных операций пока нет.
                                        </td>
                                    </tr>
                                )}
                                {operations.map((operation) => (
                                    <tr key={operation.id}>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-600">{operation.created_at}</td>
                                        <td className="px-4 py-4 text-slate-700">#{operation.order_id} {operation.order_title}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-700">{operationTypes[operation.type] ?? operation.type}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-700">{operationStatuses[operation.status] ?? operation.status}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-right font-semibold text-slate-950">{formatMoney(operation.amount)}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-700">{providerLabel(operation.provider)}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-xl font-semibold text-slate-950">Будущие события провайдера</h2>
                    <p className="mt-2 text-sm text-slate-600">Эндпоинт не подключен. Таблица готовит контракт для будущего провайдера.</p>
                    <div className="mt-5 overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-4 py-3 font-semibold">Дата</th>
                                    <th className="px-4 py-3 font-semibold">Провайдер</th>
                                    <th className="px-4 py-3 font-semibold">ID события</th>
                                    <th className="px-4 py-3 font-semibold">Тип события</th>
                                    <th className="px-4 py-3 font-semibold">Статус</th>
                                    <th className="px-4 py-3 font-semibold">Обработано</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {webhookEvents.length === 0 && (
                                    <tr>
                                        <td colSpan="6" className="px-4 py-8 text-center text-slate-500">
                                            Событий провайдера пока нет.
                                        </td>
                                    </tr>
                                )}
                                {webhookEvents.map((event) => (
                                    <tr key={event.id}>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-600">{event.created_at}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-700">{providerLabel(event.provider)}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-700">{event.event_id ?? '—'}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-700">{event.event_type}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-700">{webhookStatuses[event.status] ?? event.status}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-600">{event.processed_at ?? '—'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>
            </section>
        </DashboardLayout>
    );
}

function providerLabel(provider) {
    return provider === 'stub' ? 'Заглушка' : provider;
}
