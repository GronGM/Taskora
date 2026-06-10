import { Head, Link, useForm } from '@inertiajs/react';
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

export default function Show({ order, statusLabels, paymentStatusLabels }) {
    const form = useForm({
        message: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.post(order.submit_work_url);
    };

    return (
        <DashboardLayout>
            <Head title={order.title} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className={`rounded-md px-3 py-1 text-xs font-semibold ${statusClasses[order.status] ?? statusClasses.awaiting_payment}`}>
                                {statusLabels[order.status]}
                            </span>
                            <span className="rounded-md bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{order.source_label}</span>
                        </div>
                        <h1 className="mt-4 max-w-4xl text-4xl font-semibold tracking-normal text-slate-950">{order.title}</h1>
                        <p className="mt-4 max-w-3xl whitespace-pre-line text-sm leading-7 text-slate-600">{order.description}</p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Link href={order.workspace_url} className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Открыть рабочую область
                        </Link>
                        <Link href="/performer/orders" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            К заказам
                        </Link>
                    </div>
                </div>

                <div className="mt-8 grid gap-6 lg:grid-cols-[1fr_420px]">
                    <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-2xl font-semibold text-slate-950">Детали заказа</h2>
                        <div className="mt-5 grid gap-4 text-sm text-slate-600 sm:grid-cols-2">
                            <Info label="Заказчик" value={order.participant} />
                            <Info label="Источник" value={order.source_label} />
                            <Info label="Цена" value={`${currency.format(order.price)} ₽`} />
                            <Info label="К выплате" value={`${currency.format(order.performer_amount)} ₽`} />
                            <Info label="Комиссия платформы" value={`${currency.format(order.platform_fee_amount)} ₽`} />
                            <Info label="Срок" value={`${order.delivery_days} дн.`} />
                            <Info label="Статус заказа" value={statusLabels[order.status]} />
                            <Info label="Статус оплаты" value={paymentStatusLabels[order.payment_status]} />
                            <Info label="Срок проверки" value={order.review_hold_until} />
                            <Info label="Разблокировка оплаты" value={order.release_reason_label} />
                        </div>
                    </article>

                    <aside className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <p className="text-sm font-semibold uppercase text-blue-700">Действия</p>
                        <Link href={order.workspace_url} className="mt-5 block w-full rounded-md bg-slate-950 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-slate-800">
                            Открыть рабочую область
                        </Link>
                        {['in_progress', 'revision_requested'].includes(order.status) ? (
                            <form onSubmit={submit} className="mt-5 space-y-4">
                                <div>
                                    <label htmlFor="message" className="text-sm font-semibold text-slate-900">Комментарий к сдаче работы</label>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows={6}
                                        value={form.data.message}
                                        onChange={(event) => form.setData('message', event.target.value)}
                                        className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                    {form.errors.message && <p className="mt-2 text-sm text-red-600">{form.errors.message}</p>}
                                </div>
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Отправить работу на проверку
                                </button>
                            </form>
                        ) : (
                            <p className="mt-5 text-sm leading-6 text-slate-600">
                                {order.status === 'awaiting_payment' && 'Ожидайте, пока заказчик отметит оплату через локальную заглушку.'}
                                {order.status === 'submitted_for_review' && `Работа отправлена на проверку. Если заказчик не запросит доработку или спор до ${order.review_hold_until}, оплата будет разблокирована автоматически.`}
                                {order.status === 'disputed' && 'По заказу открыт спор. Автоматическая разблокировка оплаты остановлена до решения модератора.'}
                                {['completed', 'canceled'].includes(order.status) && 'Для завершенного или отмененного заказа доступны только просмотр и история.'}
                            </p>
                        )}
                        {order.can_open_dispute && (
                            <Link href={order.open_dispute_url} className="mt-4 block w-full rounded-md border border-red-200 bg-white px-5 py-3 text-center text-sm font-semibold text-red-700 hover:bg-red-50">
                                Открыть спор
                            </Link>
                        )}
                        {order.status === 'disputed' && order.active_dispute_url && (
                            <Link href={order.active_dispute_url} className="mt-4 block w-full rounded-md bg-red-600 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-red-700">
                                Открыть спор
                            </Link>
                        )}
                    </aside>
                </div>

                <Submissions submissions={order.submissions} />
            </section>
        </DashboardLayout>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-lg bg-slate-50 p-4">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-2 font-semibold text-slate-950">{value ?? 'Не указано'}</p>
        </div>
    );
}

function Submissions({ submissions }) {
    return (
        <section className="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="text-2xl font-semibold text-slate-950">Сдачи работы</h2>
            {submissions.length > 0 ? (
                <div className="mt-5 space-y-4">
                    {submissions.map((submission) => (
                        <article key={submission.id} className="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <div className="flex flex-wrap items-center justify-between gap-3">
                                <p className="text-sm font-semibold text-slate-950">{submission.author}</p>
                                <p className="text-xs font-semibold uppercase text-slate-500">{submission.status}</p>
                            </div>
                            <p className="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{submission.message}</p>
                            <p className="mt-3 text-xs text-slate-500">{submission.created_at}</p>
                        </article>
                    ))}
                </div>
            ) : (
                <p className="mt-4 text-sm leading-6 text-slate-600">Сдач работы пока нет.</p>
            )}
        </section>
    );
}
