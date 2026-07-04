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
    const revisionForm = useForm({ revision_comment: '' });

    const submitRevision = (event) => {
        event.preventDefault();
        revisionForm.post(order.request_revision_url, {
            preserveScroll: true,
            onSuccess: () => revisionForm.reset('revision_comment'),
        });
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
                        <h1 className="mt-4 max-w-4xl text-4xl font-semibold tracking-tight text-slate-950">{order.title}</h1>
                        <p className="mt-4 max-w-3xl whitespace-pre-line text-sm leading-7 text-slate-600">{order.description}</p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Link href={order.workspace_url} className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Открыть рабочую область
                        </Link>
                        <Link href="/customer/orders" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            К заказам
                        </Link>
                    </div>
                </div>

                <div className="mt-8 rounded-lg border border-amber-200 bg-amber-50 p-5">
                    <p className="text-sm font-semibold text-amber-900">Это локальная заглушка оплаты. Реальный платежный шлюз будет подключен позже.</p>
                </div>

                {order.status === 'submitted_for_review' && (
                    <div className="mt-4 rounded-lg border border-purple-200 bg-purple-50 p-5">
                        <p className="text-sm font-semibold text-purple-900">
                            Работа на проверке до {order.review_hold_until}. Если вы не примете работу и не запросите доработку до этой даты, оплата будет разблокирована автоматически.
                        </p>
                    </div>
                )}

                {order.status === 'disputed' && (
                    <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-5">
                        <p className="text-sm font-semibold text-red-900">
                            По заказу открыт спор. Автоматическая разблокировка оплаты остановлена до решения модератора.
                            {order.active_dispute_url && (
                                <Link href={order.active_dispute_url} className="ml-2 underline">
                                    Открыть спор
                                </Link>
                            )}
                        </p>
                    </div>
                )}

                {order.status === 'completed' && order.release_reason === 'auto_release' && (
                    <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                        <p className="text-sm font-semibold text-emerald-900">Оплата разблокирована автоматически по окончании срока проверки.</p>
                    </div>
                )}

                {order.status === 'completed' && order.release_reason === 'customer_early_accept' && (
                    <div className="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-5">
                        <p className="text-sm font-semibold text-emerald-900">Оплата разблокирована заказчиком досрочно.</p>
                    </div>
                )}

                {order.status === 'completed' && !order.review && order.can_review && (
                    <div className="mt-4 rounded-lg border border-blue-200 bg-blue-50 p-5">
                        <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                            <p className="text-sm font-semibold text-blue-900">Заказ завершен. Оставьте отзыв исполнителю, чтобы помочь другим заказчикам.</p>
                            <Link href={order.review_create_url} className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Оставить отзыв исполнителю
                            </Link>
                        </div>
                    </div>
                )}

                {order.review && (
                    <div className="mt-4 rounded-lg border border-slate-200 bg-white p-5">
                        <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                            <p className="text-sm font-semibold text-slate-900">Отзыв оставлен: {order.review.rating} / 5</p>
                            <Link href={order.review.show_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                Открыть отзыв
                            </Link>
                        </div>
                    </div>
                )}

                <div className="mt-6 grid gap-6 lg:grid-cols-[1fr_380px]">
                    <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-2xl font-semibold text-slate-950">Детали заказа</h2>
                        <div className="mt-5 grid gap-4 text-sm text-slate-600 sm:grid-cols-2">
                            <Info label="Исполнитель" value={order.participant} />
                            <Info label="Источник" value={order.source_label} />
                            <Info label="Цена" value={`${currency.format(order.price)} ₽`} />
                            <Info label="Комиссия платформы" value={`${currency.format(order.platform_fee_amount)} ₽ (${order.platform_fee_percent}%)`} />
                            <Info label="Сумма исполнителю" value={`${currency.format(order.performer_amount)} ₽`} />
                            <Info label="Срок" value={`${order.delivery_days} дн.`} />
                            <Info label="Статус заказа" value={statusLabels[order.status]} />
                            <Info label="Статус оплаты" value={paymentStatusLabels[order.payment_status]} />
                            <Info label="Срок проверки" value={order.review_hold_until} />
                            <Info label="Разблокировка оплаты" value={order.release_reason_label} />
                        </div>
                    </article>

                    <aside className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <p className="text-sm font-semibold uppercase text-blue-700">Действия</p>
                        <div className="mt-5 space-y-3">
                            <Link href={order.workspace_url} className="block w-full rounded-md bg-slate-950 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-slate-800">
                                Открыть рабочую область
                            </Link>
                            {order.status === 'awaiting_payment' && (
                                <>
                                    <Link href={order.mark_paid_url} method="post" as="button" className="w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                                        Оплатить (заглушка)
                                    </Link>
                                    <Link href={order.cancel_url} method="post" as="button" className="w-full rounded-md border border-red-200 bg-white px-5 py-3 text-sm font-semibold text-red-700 hover:bg-red-50">
                                        Отменить
                                    </Link>
                                </>
                            )}
                            {order.status === 'submitted_for_review' && (
                                <>
                                    <p className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm font-semibold leading-6 text-amber-900">
                                        Нажимайте эту кнопку только если полностью уверены, что работа выполнена качественно. После разблокировки средств спор по оплате может быть ограничен.
                                    </p>
                                    <Link href={order.complete_url} method="post" as="button" className="w-full rounded-md bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                                        Принять работу и разблокировать оплату
                                    </Link>
                                    <form onSubmit={submitRevision} className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                        <label htmlFor="revision_comment" className="text-sm font-semibold text-amber-950">
                                            Опишите, что именно нужно исправить
                                        </label>
                                        <textarea
                                            id="revision_comment"
                                            name="revision_comment"
                                            rows={4}
                                            value={revisionForm.data.revision_comment}
                                            onChange={(event) => revisionForm.setData('revision_comment', event.target.value)}
                                            className="mt-2 w-full rounded-md border border-amber-200 bg-white px-4 py-3 text-sm leading-6 text-slate-900 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                                            placeholder="Например: исправьте структуру первого блока и добавьте недостающие выводы."
                                        />
                                        {revisionForm.errors.revision_comment && <p className="mt-2 text-sm text-red-600">{revisionForm.errors.revision_comment}</p>}
                                        <button
                                            type="submit"
                                            disabled={revisionForm.processing}
                                            className="mt-3 w-full rounded-md border border-amber-300 bg-white px-5 py-3 text-sm font-semibold text-amber-800 hover:bg-amber-100 disabled:cursor-not-allowed disabled:opacity-60"
                                        >
                                            Запросить доработку
                                        </button>
                                    </form>
                                </>
                            )}
                            {order.status === 'completed' && !order.review && order.can_review && (
                                <Link href={order.review_create_url} className="block w-full rounded-md bg-blue-600 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-blue-700">
                                    Оставить отзыв исполнителю
                                </Link>
                            )}
                            {order.review && (
                                <Link href={order.review.show_url} className="block w-full rounded-md border border-slate-300 bg-white px-5 py-3 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                    Отзыв оставлен
                                </Link>
                            )}
                            {order.can_open_dispute && (
                                <Link href={order.open_dispute_url} className="block w-full rounded-md border border-red-200 bg-white px-5 py-3 text-center text-sm font-semibold text-red-700 hover:bg-red-50">
                                    Открыть спор
                                </Link>
                            )}
                            {order.status === 'disputed' && order.active_dispute_url && (
                                <Link href={order.active_dispute_url} className="block w-full rounded-md bg-red-600 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-red-700">
                                    Открыть спор
                                </Link>
                            )}
                            {['in_progress', 'revision_requested', 'completed', 'canceled'].includes(order.status) && (
                                <p className="text-sm leading-6 text-slate-600">Для текущего статуса доступны только просмотр и ожидание следующего действия исполнителя.</p>
                            )}
                        </div>
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
                <p className="mt-4 text-sm leading-6 text-slate-600">Исполнитель еще не отправлял работу на проверку.</p>
            )}
        </section>
    );
}
