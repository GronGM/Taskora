import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

const focusClass = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950';

export default function DisputeShow({ conversation }) {
    const form = useForm({ body: '' });

    const submit = (event) => {
        event.preventDefault();
        form.post(conversation.message_url, {
            preserveScroll: true,
            onSuccess: () => form.reset('body'),
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Спор #${conversation.id}: сообщения`} />

            <section className="mx-auto max-w-6xl px-4 py-8 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div className="min-w-0">
                        <p className="text-sm font-semibold uppercase text-red-700 dark:text-red-300">Диалог спора #{conversation.id}</p>
                        <h1 className="mt-3 break-words text-3xl font-semibold tracking-normal text-slate-950 dark:text-white sm:text-4xl">{conversation.order.title}</h1>
                        <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">{conversation.description}</p>
                    </div>
                    <div className="flex shrink-0 flex-wrap gap-2">
                        <Link href="/messages" className={`rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 ${focusClass}`}>
                            К сообщениям
                        </Link>
                        <Link href={conversation.dispute_url} className={`rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 ${focusClass}`}>
                            Открыть спор
                        </Link>
                    </div>
                </div>

                <div className="mt-6 grid gap-4 md:grid-cols-4">
                    <Info label="Статус спора" value={conversation.status_label} />
                    <Info label="Причина" value={conversation.reason_label} />
                    <Info label="Статус заказа" value={conversation.order.status_label} />
                    <Info label="Сумма" value={`${currency.format(conversation.order.price)} ₽`} />
                </div>

                <div className="mt-6 grid gap-4 lg:grid-cols-3">
                    <Participant title="Заказчик" participant={conversation.participants.customer} />
                    <Participant title="Исполнитель" participant={conversation.participants.performer} />
                    <Participant title="Открыл спор" participant={conversation.participants.opened_by} />
                </div>

                {conversation.resolution_label && (
                    <div className="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm leading-6 text-emerald-900 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-100">
                        <p className="font-semibold">{conversation.resolution_label}</p>
                        {conversation.moderator_comment && <p className="mt-2">{conversation.moderator_comment}</p>}
                    </div>
                )}

                <Notice>{conversation.warning}</Notice>

                <section className="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <div className="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                        <div>
                            <p className="text-sm font-semibold uppercase text-red-700 dark:text-red-300">Арбитраж</p>
                            <h2 className="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">Сообщения спора</h2>
                        </div>
                        <p className="text-sm text-slate-500 dark:text-slate-400">До 4000 символов</p>
                    </div>

                    <MessageList messages={conversation.messages} />

                    {conversation.can_reply ? (
                        <form onSubmit={submit} className="mt-6 border-t border-slate-200 pt-5 dark:border-slate-800">
                            <label htmlFor="message_body" className="text-sm font-semibold text-slate-900 dark:text-white">
                                Новое сообщение
                            </label>
                            <textarea
                                id="message_body"
                                name="body"
                                rows={5}
                                value={form.data.body}
                                onChange={(event) => form.setData('body', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 text-slate-900 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-950"
                                placeholder="Напишите сообщение по спору"
                            />
                            {form.errors.body && <p className="mt-2 text-sm font-semibold text-red-600 dark:text-red-300">{form.errors.body}</p>}
                            <button
                                type="submit"
                                disabled={form.processing}
                                className={`mt-3 rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60 dark:bg-blue-500 dark:hover:bg-blue-400 ${focusClass}`}
                            >
                                {form.processing ? 'Отправляем...' : 'Отправить сообщение'}
                            </button>
                        </form>
                    ) : (
                        <Notice>Спор закрыт или недоступен для новых сообщений. Переписка остается доступной для просмотра.</Notice>
                    )}
                </section>
            </section>
        </DashboardLayout>
    );
}

function MessageList({ messages }) {
    if (messages.length === 0) {
        return (
            <div className="mt-5 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-6 text-center dark:border-slate-700 dark:bg-slate-950">
                <p className="font-semibold text-slate-950 dark:text-white">Сообщений пока нет</p>
                <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">Опишите детали спора внутри платформы.</p>
            </div>
        );
    }

    return (
        <div className="mt-5 space-y-4">
            {messages.map((message) => <Message key={message.id} message={message} />)}
        </div>
    );
}

function Message({ message }) {
    return (
        <article className={`rounded-lg border p-4 ${message.is_system ? 'border-amber-200 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/50' : message.is_own ? 'border-blue-200 bg-blue-50 dark:border-blue-700 dark:bg-blue-950/45' : 'border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-950'}`}>
            <div className="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold text-slate-950 dark:text-white">{message.author}</p>
                    <p className="mt-1 text-xs uppercase text-slate-500 dark:text-slate-400">{message.author_role}</p>
                </div>
                <p className="text-xs text-slate-500 dark:text-slate-400">{message.created_at}</p>
            </div>
            <p className="mt-4 whitespace-pre-line break-words text-sm leading-6 text-slate-700 dark:text-slate-200">{message.body}</p>
        </article>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{label}</p>
            <p className="mt-2 font-semibold text-slate-950 dark:text-white">{value ?? 'Не указано'}</p>
        </div>
    );
}

function Participant({ title, participant }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p className="text-xs font-semibold uppercase text-slate-500 dark:text-slate-400">{title}</p>
            <p className="mt-2 font-semibold text-slate-950 dark:text-white">{participant?.name ?? 'Не указан'}</p>
            <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{participant?.role_label}</p>
        </div>
    );
}

function Notice({ children }) {
    return (
        <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm font-semibold leading-6 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
            {children}
        </div>
    );
}
