import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

const formatMoney = (amount) => `${currency.format(amount ?? 0)} ₽`;

export default function Ledger({ order, operations = {}, ledgerEntries = {}, accountSummary = [], links = {} }) {
    const operationRows = operations.data ?? [];
    const ledgerRows = ledgerEntries.data ?? [];

    return (
        <DashboardLayout>
            <Head title={`Финансовый журнал заказа #${order.id}`} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div className="min-w-0">
                        <p className="text-sm font-semibold uppercase text-blue-700">Финансовая диагностика</p>
                        <h1 className="mt-3 break-words text-4xl font-semibold tracking-tight text-slate-950">
                            Финансовый журнал заказа #{order.id}
                        </h1>
                        <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                            Сводка платежных операций и записей финансового журнала только для чтения. Эта страница не меняет статусы,
                            не запускает выплаты, возвраты или внешние платежные запросы.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href={links.show} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            Карточка заказа
                        </Link>
                        <Link href={links.events} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            События
                        </Link>
                        <Link href={links.finance} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Финансовая сводка
                        </Link>
                    </div>
                </div>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Metric title="Сумма заказа" value={formatMoney(order.price)} />
                    <Metric title="Комиссия платформы" value={formatMoney(order.platform_fee_amount)} />
                    <Metric title="Исполнителю" value={formatMoney(order.performer_amount)} />
                    <Metric title="Статус оплаты" value={order.payment_status_label} />
                </div>

                <Panel title="Сводка по счетам" className="mt-8">
                    {accountSummary.length > 0 ? (
                        <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                            {accountSummary.map((entry) => (
                                <div key={`${entry.account}-${entry.direction}`} className="border-t border-slate-100 pt-4 first:border-t-0 first:pt-0 md:border-t-0 md:pt-0">
                                    <p className="text-sm font-semibold text-slate-950">{entry.account_label}</p>
                                    <p className="mt-1 text-sm text-slate-500">{entry.direction_label}</p>
                                    <p className="mt-2 text-2xl font-semibold text-slate-950">{formatMoney(entry.amount)}</p>
                                </div>
                            ))}
                        </div>
                    ) : (
                        <EmptyState title="Движений по счетам пока нет" />
                    )}
                </Panel>

                <div className="mt-8 grid gap-6 xl:grid-cols-2">
                    <Panel title="Платежные операции">
                        {operationRows.length > 0 ? (
                            <ul>
                                {operationRows.map((operation) => (
                                    <li key={operation.id} className="border-t border-slate-100 py-4 first:border-t-0 first:pt-0">
                                        <div className="flex flex-col justify-between gap-2 sm:flex-row">
                                            <div className="min-w-0">
                                                <p className="break-words text-sm font-semibold text-slate-950">
                                                    #{operation.id} {operation.type_label}
                                                </p>
                                                <p className="mt-1 text-sm text-slate-500">{operation.status_label} · {providerLabel(operation.provider)}</p>
                                            </div>
                                            <p className="text-lg font-semibold text-slate-950">{formatMoney(operation.amount)}</p>
                                        </div>
                                        <div className="mt-3 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                                            <span>Создана: {operation.created_at ?? '—'}</span>
                                            <span>Успешно: {operation.succeeded_at ?? '—'}</span>
                                            <span>Пользователь: {operation.user ?? '—'}</span>
                                            <span>Валюта: {operation.currency}</span>
                                        </div>
                                        {operation.description && <p className="mt-3 break-words text-sm text-slate-700">{operation.description}</p>}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Операций пока нет" />
                        )}
                        <Pagination paginator={operations} />
                    </Panel>

                    <Panel title="Записи журнала">
                        {ledgerRows.length > 0 ? (
                            <ul>
                                {ledgerRows.map((entry) => (
                                    <li key={entry.id} className="border-t border-slate-100 py-4 first:border-t-0 first:pt-0">
                                        <div className="flex flex-col justify-between gap-2 sm:flex-row">
                                            <div className="min-w-0">
                                                <p className="break-words text-sm font-semibold text-slate-950">
                                                    #{entry.id} {entry.account_label}
                                                </p>
                                                <p className="mt-1 text-sm text-slate-500">{entry.direction_label} · операция #{entry.payment_operation_id ?? '—'}</p>
                                            </div>
                                            <p className="text-lg font-semibold text-slate-950">{formatMoney(entry.amount)}</p>
                                        </div>
                                        <div className="mt-3 grid gap-2 text-xs text-slate-500 sm:grid-cols-2">
                                            <span>Проведено: {entry.posted_at ?? '—'}</span>
                                            <span>Создано: {entry.created_at ?? '—'}</span>
                                            <span>Пользователь: {entry.user ?? '—'}</span>
                                            <span>Валюта: {entry.currency}</span>
                                        </div>
                                        {entry.description && <p className="mt-3 break-words text-sm text-slate-700">{entry.description}</p>}
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <EmptyState title="Проводок пока нет" />
                        )}
                        <Pagination paginator={ledgerEntries} />
                    </Panel>
                </div>
            </section>
        </DashboardLayout>
    );
}

function Panel({ title, children, className = '' }) {
    return (
        <section className={`rounded-lg border border-slate-200 bg-white p-5 shadow-sm ${className}`}>
            <h2 className="text-xl font-semibold text-slate-950">{title}</h2>
            <div className="mt-5">{children}</div>
        </section>
    );
}

function Metric({ title, value }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-slate-500">{title}</p>
            <p className="mt-3 break-words text-2xl font-semibold text-slate-950">{value}</p>
        </article>
    );
}

function EmptyState({ title }) {
    return (
        <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
            <p className="text-sm font-semibold text-slate-700">{title}</p>
        </div>
    );
}

function Pagination({ paginator = {} }) {
    if (!paginator.prev_page_url && !paginator.next_page_url) {
        return null;
    }

    return (
        <div className="mt-6 flex flex-col gap-3 border-t border-slate-100 pt-4 sm:flex-row sm:items-center sm:justify-between">
            <p className="text-sm text-slate-500">
                Страница {paginator.current_page} из {paginator.last_page}. Найдено: {paginator.total}
            </p>
            <div className="flex gap-2">
                {paginator.prev_page_url && <Link href={paginator.prev_page_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Назад</Link>}
                {paginator.next_page_url && <Link href={paginator.next_page_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">Вперед</Link>}
            </div>
        </div>
    );
}

function providerLabel(provider) {
    return provider === 'stub' ? 'Заглушка' : provider;
}
