import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

const statusClasses = {
    awaiting_payment: 'bg-amber-50 text-amber-700 ring-amber-200',
    in_progress: 'bg-blue-50 text-blue-700 ring-blue-200',
    submitted_for_review: 'bg-purple-50 text-purple-700 ring-purple-200',
    revision_requested: 'bg-orange-50 text-orange-700 ring-orange-200',
    completed: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    disputed: 'bg-red-50 text-red-700 ring-red-200',
    canceled: 'bg-slate-100 text-slate-700 ring-slate-200',
};

const eventClasses = {
    contact_blocked: 'border-red-200 bg-red-50 text-red-900',
    file_uploaded: 'border-blue-200 bg-blue-50 text-blue-900',
    message_sent: 'border-slate-200 bg-slate-50 text-slate-800',
    payment_stub_paid: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    review_hold_started: 'border-purple-200 bg-purple-50 text-purple-900',
    order_completed: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    funds_released: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    funds_refunded: 'border-red-200 bg-red-50 text-red-900',
    order_canceled: 'border-slate-300 bg-slate-100 text-slate-800',
    dispute_opened: 'border-red-200 bg-red-50 text-red-900',
    dispute_message_sent: 'border-red-200 bg-red-50 text-red-900',
    dispute_under_review: 'border-amber-200 bg-amber-50 text-amber-900',
    dispute_resolved: 'border-emerald-200 bg-emerald-50 text-emerald-900',
    revision_requested: 'border-orange-200 bg-orange-50 text-orange-900',
    revision_requested_by_moderator: 'border-orange-200 bg-orange-50 text-orange-900',
};

export default function Workspace({ role, order, statusLabels, paymentStatusLabels }) {
    const messageForm = useForm({ body: '' });
    const fileForm = useForm({ file: null });

    const submitMessage = (event) => {
        event.preventDefault();
        messageForm.post(order.message_url, {
            preserveScroll: true,
            onSuccess: () => messageForm.reset('body'),
        });
    };

    const submitFile = (event) => {
        event.preventDefault();
        fileForm.post(order.file_url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => fileForm.reset('file'),
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Рабочая область: ${order.title}`} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 xl:flex-row xl:items-start">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className={`rounded-md px-3 py-1 text-xs font-semibold ring-1 ${statusClasses[order.status] ?? statusClasses.awaiting_payment}`}>
                                {statusLabels[order.status]}
                            </span>
                            <span className="rounded-md bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                {order.source_label}
                            </span>
                            <span className="rounded-md bg-white px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-slate-200">
                                {paymentStatusLabels[order.payment_status]}
                            </span>
                        </div>
                        <h1 className="mt-4 max-w-4xl text-4xl font-semibold tracking-tight text-slate-950">{order.title}</h1>
                        <p className="mt-4 max-w-3xl whitespace-pre-line text-sm leading-7 text-slate-600">{order.description}</p>
                    </div>

                    <div className="flex flex-wrap gap-3">
                        <Link href={order.back_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            К заказам
                        </Link>
                        <Link href={order.show_url} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Карточка заказа
                        </Link>
                    </div>
                </div>

                <div className="mt-8 grid gap-4 md:grid-cols-4">
                    <Metric label="Сумма" value={`${currency.format(order.price)} ₽`} />
                    <Metric label={role === 'performer' ? 'К выплате' : 'Исполнителю'} value={`${currency.format(order.performer_amount)} ₽`} />
                    <Metric label="Комиссия" value={`${currency.format(order.platform_fee_amount)} ₽`} />
                    <Metric label="Срок" value={`${order.delivery_days} дн.`} />
                </div>

                <div className="mt-6 grid gap-4 lg:grid-cols-2">
                    <Participant title="Заказчик" participant={order.customer} />
                    <Participant title="Исполнитель" participant={order.performer} />
                </div>

                <div className="mt-6 space-y-3">
                    <Notice tone="amber">
                        {order.payment_mode === 'yookassa'
                            ? 'Оплата проходит через ЮKassa: деньги удерживаются до приемки работы.'
                            : 'Оплата сейчас работает как локальная заглушка. Реальный платежный шлюз не подключен.'}
                    </Notice>
                    {order.status === 'awaiting_payment' && (
                        <Notice tone="red">
                            Заказ еще не оплачен. Передача финальных материалов до оплаты не рекомендуется.
                        </Notice>
                    )}
                    {role === 'customer' && order.status === 'submitted_for_review' && (
                        <Notice tone="purple">
                            Работа на проверке до {order.review_hold_until}. Если вы не примете работу и не запросите доработку до этой даты, оплата будет разблокирована автоматически.
                        </Notice>
                    )}
                    {role === 'performer' && order.status === 'submitted_for_review' && (
                        <Notice tone="purple">
                            Работа отправлена на проверку. Если заказчик не запросит доработку или спор до {order.review_hold_until}, оплата будет разблокирована автоматически.
                        </Notice>
                    )}
                    {order.status === 'disputed' && (
                        <Notice tone="red">
                            По заказу открыт спор. Автоматическая разблокировка оплаты остановлена до решения модератора.
                            {order.active_dispute_url && (
                                <Link href={order.active_dispute_url} className="ml-2 underline">
                                    Открыть спор
                                </Link>
                            )}
                        </Notice>
                    )}
                    {order.status === 'completed' && order.release_reason === 'auto_release' && (
                        <Notice tone="emerald">
                            Оплата разблокирована автоматически по окончании срока проверки.
                        </Notice>
                    )}
                    {order.status === 'completed' && order.release_reason === 'customer_early_accept' && (
                        <Notice tone="emerald">
                            Оплата разблокирована заказчиком досрочно.
                        </Notice>
                    )}
                    {role === 'customer' && order.status === 'completed' && !order.review && order.can.review && (
                        <Notice tone="blue">
                            Заказ завершен. <Link href={order.review_create_url} className="underline">Оставить отзыв исполнителю</Link>
                        </Notice>
                    )}
                    {role === 'customer' && order.review && (
                        <Notice tone="slate">
                            Отзыв оставлен: {order.review.rating} / 5. <Link href={order.review.show_url} className="underline">Открыть отзыв</Link>
                        </Notice>
                    )}
                </div>

                <div className="mt-6 grid gap-6 xl:grid-cols-[1fr_380px]">
                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                            <div>
                                <p className="text-sm font-semibold uppercase text-blue-700">Чат</p>
                                <h2 className="mt-2 text-2xl font-semibold text-slate-950">Сообщения заказа</h2>
                            </div>
                            <p className="text-sm text-slate-500">До 4000 символов</p>
                        </div>

                        <div className="mt-5 space-y-4">
                            {order.messages.length > 0 ? (
                                order.messages.map((message) => <Message key={message.id} message={message} />)
                            ) : (
                                <EmptyState title="Сообщений пока нет" text="Начните обсуждение деталей заказа внутри Таскоры." />
                            )}
                        </div>

                        <form onSubmit={submitMessage} className="mt-6 border-t border-slate-200 pt-5">
                            <label htmlFor="body" className="text-sm font-semibold text-slate-900">
                                Новое сообщение
                            </label>
                            <textarea
                                id="body"
                                name="body"
                                rows={5}
                                value={messageForm.data.body}
                                onChange={(event) => messageForm.setData('body', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Напишите сообщение по заказу"
                            />
                            {messageForm.errors.body && <p className="mt-2 text-sm text-red-600">{messageForm.errors.body}</p>}
                            <button
                                type="submit"
                                disabled={messageForm.processing}
                                className="mt-3 rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Отправить сообщение
                            </button>
                        </form>
                    </section>

                    <aside className="space-y-6">
                        <QuickActions role={role} order={order} />
                        <Files order={order} fileForm={fileForm} submitFile={submitFile} />
                    </aside>
                </div>

                <section className="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">История</p>
                        <h2 className="mt-2 text-2xl font-semibold text-slate-950">Системные события</h2>
                    </div>

                    <div className="mt-5 space-y-3">
                        {order.events.length > 0 ? (
                            order.events.map((event) => <OrderEvent key={event.id} event={event} />)
                        ) : (
                            <EmptyState title="История пока пустая" text="Новые действия по заказу будут появляться здесь." />
                        )}
                    </div>
                </section>
            </section>
        </DashboardLayout>
    );
}

function Metric({ label, value }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-2 text-xl font-semibold text-slate-950">{value}</p>
        </div>
    );
}

function Participant({ title, participant }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-xs font-semibold uppercase text-slate-500">{title}</p>
            <p className="mt-2 text-lg font-semibold text-slate-950">{participant.name}</p>
            <p className="mt-1 text-sm text-slate-500">{participant.role_label}</p>
        </div>
    );
}

function Notice({ children, tone }) {
    const classes = {
        amber: 'border-amber-200 bg-amber-50 text-amber-900',
        blue: 'border-blue-200 bg-blue-50 text-blue-900',
        emerald: 'border-emerald-200 bg-emerald-50 text-emerald-900',
        purple: 'border-purple-200 bg-purple-50 text-purple-900',
        red: 'border-red-200 bg-red-50 text-red-800',
        slate: 'border-slate-200 bg-white text-slate-900',
    }[tone] ?? 'border-amber-200 bg-amber-50 text-amber-900';

    return (
        <div className={`rounded-lg border p-4 text-sm font-semibold ${classes}`}>
            {children}
        </div>
    );
}

function Message({ message }) {
    const isSystem = message.type === 'system_message';

    return (
        <article className={`rounded-lg border p-5 ${isSystem ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50'}`}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold text-slate-950">{message.author}</p>
                    <p className="mt-1 text-xs uppercase text-slate-500">{message.author_role}</p>
                </div>
                <p className="text-xs text-slate-500">{message.created_at}</p>
            </div>
            <p className="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{message.body}</p>
        </article>
    );
}

function Files({ order, fileForm, submitFile }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div>
                <p className="text-sm font-semibold uppercase text-blue-700">Файлы</p>
                <h2 className="mt-2 text-2xl font-semibold text-slate-950">Материалы заказа</h2>
                <p className="mt-2 text-sm leading-6 text-slate-600">До 20 MB. Файлы хранятся приватно и скачиваются только участниками заказа.</p>
            </div>

            <div className="mt-5 space-y-3">
                {order.files.length > 0 ? (
                    order.files.map((file) => <FileItem key={file.id} file={file} />)
                ) : (
                    <EmptyState title="Файлов пока нет" text="Загрузите ТЗ, результат или вспомогательные материалы." />
                )}
            </div>

            <form onSubmit={submitFile} className="mt-5 border-t border-slate-200 pt-5">
                <label htmlFor="file" className="text-sm font-semibold text-slate-900">
                    Загрузить файл
                </label>
                <input
                    id="file"
                    name="file"
                    type="file"
                    onChange={(event) => fileForm.setData('file', event.target.files?.[0] ?? null)}
                    className="mt-2 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-slate-950 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-white"
                />
                {fileForm.errors.file && <p className="mt-2 text-sm text-red-600">{fileForm.errors.file}</p>}
                <button
                    type="submit"
                    disabled={fileForm.processing}
                    className="mt-3 w-full rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    Загрузить
                </button>
            </form>
        </section>
    );
}

function FileItem({ file }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <div className="flex items-start justify-between gap-3">
                <div>
                    <p className="break-all text-sm font-semibold text-slate-950">{file.original_name}</p>
                    <p className="mt-1 text-xs text-slate-500">
                        {file.author_role}: {file.author} · {formatBytes(file.size)}
                    </p>
                </div>
                <a href={file.download_url} className="shrink-0 rounded-md bg-white px-3 py-2 text-xs font-semibold text-slate-800 ring-1 ring-slate-200 hover:bg-slate-100">
                    Скачать
                </a>
            </div>
        </article>
    );
}

function QuickActions({ role, order }) {
    const revisionForm = useForm({ revision_comment: '' });

    const submitRevision = (event) => {
        event.preventDefault();
        revisionForm.post(order.request_revision_url, {
            preserveScroll: true,
            onSuccess: () => revisionForm.reset('revision_comment'),
        });
    };

    return (
        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <p className="text-sm font-semibold uppercase text-blue-700">Действия</p>
            <div className="mt-5 space-y-3">
                {role === 'customer' && order.can.mark_paid && (
                    <Link href={order.mark_paid_url} method="post" as="button" className="w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                        {order.payment_mode === 'yookassa' ? 'Оплатить картой' : 'Оплатить (заглушка)'}
                    </Link>
                )}
                {role === 'customer' && order.can.complete && (
                    <div className="space-y-3">
                        <p className="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm font-semibold leading-6 text-amber-900">
                            Нажимайте эту кнопку только если полностью уверены, что работа выполнена качественно. После разблокировки средств спор по оплате может быть ограничен.
                        </p>
                        <Link href={order.complete_url} method="post" as="button" className="w-full rounded-md bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700">
                            Принять работу и разблокировать оплату
                        </Link>
                    </div>
                )}
                {role === 'customer' && order.can.request_revision && (
                    <form onSubmit={submitRevision} className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <label htmlFor="workspace_revision_comment" className="text-sm font-semibold text-amber-950">
                            Опишите, что именно нужно исправить
                        </label>
                        <textarea
                            id="workspace_revision_comment"
                            name="revision_comment"
                            rows={4}
                            value={revisionForm.data.revision_comment}
                            onChange={(event) => revisionForm.setData('revision_comment', event.target.value)}
                            className="mt-2 w-full rounded-md border border-amber-200 bg-white px-4 py-3 text-sm leading-6 text-slate-900 outline-none focus:border-amber-500 focus:ring-2 focus:ring-amber-100"
                            placeholder="Например: доработайте второй вариант и приложите исходники."
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
                )}
                {role === 'customer' && order.can.review && (
                    <Link href={order.review_create_url} className="block w-full rounded-md bg-blue-600 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-blue-700">
                        Оставить отзыв исполнителю
                    </Link>
                )}
                {role === 'customer' && order.review && (
                    <Link href={order.review.show_url} className="block w-full rounded-md border border-slate-300 bg-white px-5 py-3 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        Отзыв оставлен
                    </Link>
                )}
                {order.can.open_dispute && (
                    <Link href={order.open_dispute_url} className="block w-full rounded-md border border-red-200 bg-white px-5 py-3 text-center text-sm font-semibold text-red-700 hover:bg-red-50">
                        Открыть спор
                    </Link>
                )}
                {order.status === 'disputed' && order.active_dispute_url && (
                    <Link href={order.active_dispute_url} className="block w-full rounded-md bg-red-600 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-red-700">
                        Открыть спор
                    </Link>
                )}
                {role === 'customer' && order.can.cancel_as_customer && (
                    <Link href={order.cancel_url} method="post" as="button" className="w-full rounded-md border border-red-200 bg-white px-5 py-3 text-sm font-semibold text-red-700 hover:bg-red-50">
                        Отменить заказ
                    </Link>
                )}
                {role === 'performer' && order.can.submit_work && (
                    <Link href={order.show_url} className="block w-full rounded-md bg-blue-600 px-5 py-3 text-center text-sm font-semibold text-white hover:bg-blue-700">
                        Сдать работу
                    </Link>
                )}
                {role === 'performer' && order.can.cancel_as_performer && (
                    <Link href={order.cancel_url} method="post" as="button" className="w-full rounded-md border border-red-200 bg-white px-5 py-3 text-sm font-semibold text-red-700 hover:bg-red-50">
                        Отказаться от заказа
                    </Link>
                )}
                {!hasActions(role, order) && (
                    <p className="text-sm leading-6 text-slate-600">Для текущего статуса доступны чат, файлы и просмотр истории.</p>
                )}
            </div>
        </section>
    );
}

function OrderEvent({ event }) {
    return (
        <article className={`rounded-lg border p-4 ${eventClasses[event.type] ?? 'border-slate-200 bg-slate-50 text-slate-800'}`}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold">{event.label}</p>
                    <p className="mt-1 text-xs opacity-80">{event.actor_role}: {event.actor}</p>
                </div>
                <p className="text-xs opacity-70">{event.created_at}</p>
            </div>
            {event.summary && <p className="mt-2 text-sm leading-6 opacity-90">{event.summary}</p>}
        </article>
    );
}

function EmptyState({ title, text }) {
    return (
        <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 text-center">
            <p className="text-sm font-semibold text-slate-950">{title}</p>
            <p className="mt-2 text-sm leading-6 text-slate-600">{text}</p>
        </div>
    );
}

function hasActions(role, order) {
    if (role === 'customer') {
        return order.can.mark_paid || order.can.complete || order.can.request_revision || order.can.review || order.review || order.can.cancel_as_customer || order.can.open_dispute || order.active_dispute_url;
    }

    return order.can.submit_work || order.can.cancel_as_performer || order.can.open_dispute || order.active_dispute_url;
}

function formatBytes(size) {
    if (!size) {
        return 'размер не указан';
    }

    if (size < 1024) {
        return `${size} Б`;
    }

    if (size < 1024 * 1024) {
        return `${Math.round(size / 1024)} КБ`;
    }

    return `${(size / 1024 / 1024).toFixed(1)} МБ`;
}
