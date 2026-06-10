import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

export default function Show({ dispute, resolutionOptions }) {
    const messageForm = useForm({ body: '' });
    const resolveForm = useForm({
        resolution: resolutionOptions[0]?.value ?? 'release_to_performer',
        moderator_comment: '',
    });

    const submitMessage = (event) => {
        event.preventDefault();
        messageForm.post(dispute.message_url, {
            preserveScroll: true,
            onSuccess: () => messageForm.reset('body'),
        });
    };

    const submitResolve = (event) => {
        event.preventDefault();
        resolveForm.post(dispute.resolve_url, { preserveScroll: true });
    };

    return (
        <DashboardLayout>
            <Head title={`Спор #${dispute.id}`} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-red-700">Арбитраж</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Спор #{dispute.id}</h1>
                        <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{dispute.description}</p>
                    </div>
                    <Link href="/moderator/disputes" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        К списку
                    </Link>
                </div>

                <div className="mt-8 grid gap-4 md:grid-cols-4">
                    <Info label="Статус" value={dispute.status_label} />
                    <Info label="Причина" value={dispute.reason_label} />
                    <Info label="Сумма" value={`${currency.format(dispute.order.price)} ₽`} />
                    <Info label="Оплата" value={dispute.order.payment_status_label} />
                </div>

                <div className="mt-6 grid gap-6 xl:grid-cols-[1fr_420px]">
                    <main className="space-y-6">
                        <Panel title="Заказ">
                            <p className="text-lg font-semibold text-slate-950">{dispute.order.title}</p>
                            <p className="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{dispute.order.description}</p>
                            <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                <Info label="Статус заказа" value={dispute.order.status_label} />
                                <Info label="К выплате" value={`${currency.format(dispute.order.performer_amount)} ₽`} />
                            </div>
                        </Panel>

                        <Panel title="Сообщения спора">
                            <div className="space-y-4">
                                {dispute.messages.map((message) => <Message key={message.id} message={message} />)}
                            </div>

                            {dispute.status !== 'resolved' && (
                                <form onSubmit={submitMessage} className="mt-5 border-t border-slate-200 pt-5">
                                    <label htmlFor="body" className="text-sm font-semibold text-slate-900">Ответ модератора</label>
                                    <textarea
                                        id="body"
                                        name="body"
                                        rows={4}
                                        value={messageForm.data.body}
                                        onChange={(event) => messageForm.setData('body', event.target.value)}
                                        className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                    {messageForm.errors.body && <p className="mt-2 text-sm text-red-600">{messageForm.errors.body}</p>}
                                    <button className="mt-3 rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700" type="submit" disabled={messageForm.processing}>
                                        Отправить сообщение
                                    </button>
                                </form>
                            )}
                        </Panel>

                        <Materials materials={dispute.materials} />
                    </main>

                    <aside className="space-y-6">
                        <Panel title="Участники">
                            <Participant label="Заказчик" user={dispute.order.customer} />
                            <Participant label="Исполнитель" user={dispute.order.performer} />
                            <Participant label="Открыл спор" user={dispute.opened_by} />
                        </Panel>

                        {dispute.can_take && (
                            <Panel title="Взять в работу">
                                <Link href={dispute.take_url} method="post" as="button" className="w-full rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                                    Взять спор в работу
                                </Link>
                            </Panel>
                        )}

                        {dispute.can_resolve && (
                            <Panel title="Решение">
                                <p className="text-sm leading-6 text-amber-800">
                                    Решение изменит статус заказа и оплаты. Реальный платежный шлюз не подключен.
                                </p>
                                <form onSubmit={submitResolve} className="mt-4 space-y-4">
                                    <div>
                                        <label htmlFor="resolution" className="text-sm font-semibold text-slate-900">Итог</label>
                                        <select
                                            id="resolution"
                                            name="resolution"
                                            value={resolveForm.data.resolution}
                                            onChange={(event) => resolveForm.setData('resolution', event.target.value)}
                                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        >
                                            {resolutionOptions.map((option) => (
                                                <option key={option.value} value={option.value}>{option.label}</option>
                                            ))}
                                        </select>
                                        {resolveForm.errors.resolution && <p className="mt-2 text-sm text-red-600">{resolveForm.errors.resolution}</p>}
                                    </div>
                                    <div>
                                        <label htmlFor="moderator_comment" className="text-sm font-semibold text-slate-900">Комментарий модератора</label>
                                        <textarea
                                            id="moderator_comment"
                                            name="moderator_comment"
                                            rows={5}
                                            value={resolveForm.data.moderator_comment}
                                            onChange={(event) => resolveForm.setData('moderator_comment', event.target.value)}
                                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                        />
                                        {resolveForm.errors.moderator_comment && <p className="mt-2 text-sm text-red-600">{resolveForm.errors.moderator_comment}</p>}
                                    </div>
                                    <button type="submit" disabled={resolveForm.processing} className="w-full rounded-md bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60">
                                        Сохранить решение
                                    </button>
                                </form>
                            </Panel>
                        )}

                        {dispute.status === 'resolved' && (
                            <Panel title="Итог">
                                <p className="text-sm font-semibold text-slate-950">{dispute.resolution_label}</p>
                                <p className="mt-2 text-sm leading-6 text-slate-600">{dispute.moderator_comment}</p>
                            </Panel>
                        )}
                    </aside>
                </div>
            </section>
        </DashboardLayout>
    );
}

function Panel({ title, children }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="text-xl font-semibold text-slate-950">{title}</h2>
            <div className="mt-4">{children}</div>
        </section>
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

function Participant({ label, user }) {
    return (
        <div className="mb-3 rounded-lg bg-slate-50 p-4">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-2 font-semibold text-slate-950">{user?.name ?? 'Не указан'}</p>
        </div>
    );
}

function Message({ message }) {
    return (
        <article className={`rounded-lg border p-4 ${message.is_system ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50'}`}>
            <div className="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p className="text-sm font-semibold text-slate-950">{message.author}</p>
                    <p className="mt-1 text-xs uppercase text-slate-500">{message.author_role}</p>
                </div>
                <p className="text-xs text-slate-500">{message.created_at}</p>
            </div>
            <p className="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{message.body}</p>
        </article>
    );
}

function Materials({ materials }) {
    return (
        <Panel title="Материалы заказа">
            <MaterialList title="Сообщения заказа" items={materials.messages} render={(item) => `${item.author_role}: ${item.body}`} />
            <MaterialList title="Файлы заказа" items={materials.files} render={(item) => item.original_name} />
            <MaterialList title="События заказа" items={materials.events} render={(item) => item.type} />
            <MaterialList title="Сдачи работы" items={materials.submissions} render={(item) => `${item.status}: ${item.message}`} />
        </Panel>
    );
}

function MaterialList({ title, items, render }) {
    return (
        <div className="mt-5">
            <p className="text-sm font-semibold text-slate-900">{title}</p>
            {items.length > 0 ? (
                <div className="mt-2 space-y-2">
                    {items.map((item) => (
                        <div key={item.id} className="rounded-md bg-slate-50 p-3 text-sm leading-6 text-slate-700">
                            {render(item)}
                        </div>
                    ))}
                </div>
            ) : (
                <p className="mt-2 text-sm text-slate-500">Нет данных.</p>
            )}
        </div>
    );
}
