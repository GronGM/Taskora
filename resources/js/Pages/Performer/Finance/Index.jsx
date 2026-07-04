import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const formatMoney = (amount) => new Intl.NumberFormat('ru-RU').format(amount ?? 0) + ' ₽';

export default function PerformerFinance({ summary, ledgerEntries, labels }) {
    const accountLabels = labels?.accounts ?? {};
    const directionLabels = labels?.directions ?? {};

    const cards = [
        { title: 'Ожидает разблокировки', value: formatMoney(summary.pending_amount) },
        { title: 'Доступно к выводу', value: formatMoney(summary.available_amount) },
        { title: 'Всего разблокировано', value: formatMoney(summary.total_released_amount) },
        { title: 'Выполненные заказы', value: summary.completed_orders_count },
    ];

    return (
        <DashboardLayout>
            <Head title="Финансы" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Внутренний ledger</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Финансы</h1>
                    </div>
                    <Link href="/performer/dashboard" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                        Вернуться в кабинет
                    </Link>
                </div>

                <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    Это тестовая финансовая архитектура. Реальные выплаты пока не подключены.
                </div>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {cards.map((card) => (
                        <article key={card.title} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm text-slate-500">{card.title}</p>
                            <p className="mt-3 text-2xl font-semibold text-slate-950">{card.value}</p>
                        </article>
                    ))}
                </div>

                <section className="mt-8 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 className="text-xl font-semibold text-slate-950">История движения средств</h2>
                            <p className="mt-2 text-sm text-slate-600">Показаны ledger-записи, связанные с вашим внутренним балансом.</p>
                        </div>
                    </div>

                    <div className="mt-5 overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-4 py-3 font-semibold">Дата</th>
                                    <th className="px-4 py-3 font-semibold">Заказ</th>
                                    <th className="px-4 py-3 font-semibold">Тип счета</th>
                                    <th className="px-4 py-3 font-semibold">Направление</th>
                                    <th className="px-4 py-3 text-right font-semibold">Сумма</th>
                                    <th className="px-4 py-3 font-semibold">Описание</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {ledgerEntries.length === 0 && (
                                    <tr>
                                        <td colSpan="6" className="px-4 py-8 text-center text-slate-500">
                                            Финансовых записей пока нет.
                                        </td>
                                    </tr>
                                )}
                                {ledgerEntries.map((entry) => (
                                    <tr key={entry.id}>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-600">{entry.posted_at ?? '—'}</td>
                                        <td className="px-4 py-4">
                                            {entry.order_url ? (
                                                <Link href={entry.order_url} className="font-semibold text-blue-700 hover:text-blue-800">
                                                    #{entry.order_id} {entry.order_title}
                                                </Link>
                                            ) : (
                                                <span className="text-slate-500">Без заказа</span>
                                            )}
                                        </td>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-700">{accountLabels[entry.account] ?? entry.account}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-slate-700">{directionLabels[entry.direction] ?? entry.direction}</td>
                                        <td className="whitespace-nowrap px-4 py-4 text-right font-semibold text-slate-950">{formatMoney(entry.amount)}</td>
                                        <td className="min-w-64 px-4 py-4 text-slate-600">{entry.description}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <section className="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h2 className="text-xl font-semibold text-slate-950">Заявки на вывод</h2>
                            <p className="mt-2 text-sm text-slate-600">Реальные выплаты будут подключены позже.</p>
                        </div>
                        <button
                            type="button"
                            disabled
                            className="w-full max-w-full rounded-md bg-slate-200 px-4 py-2 text-center text-sm font-semibold leading-5 text-slate-500 md:w-auto"
                        >
                            Запросить вывод средств — будет доступно после подключения выплат
                        </button>
                    </div>
                </section>
            </section>
        </DashboardLayout>
    );
}
