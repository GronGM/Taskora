import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

export default function Show({ dispute }) {
    const form = useForm({ body: '' });
    const isResolved = dispute.status === 'resolved';

    const submit = (event) => {
        event.preventDefault();
        form.post(dispute.message_url, {
            preserveScroll: true,
            onSuccess: () => form.reset('body'),
        });
    };

    return (
        <DashboardLayout>
            <Head title={`Спор #${dispute.id}`} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-red-700">Спор #{dispute.id}</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">{dispute.order.title}</h1>
                        <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">{dispute.description}</p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Link href={dispute.order.workspace_url} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Рабочая область
                        </Link>
                        <Link href={dispute.order.show_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            Карточка заказа
                        </Link>
                    </div>
                </div>

                <div className="mt-8 grid gap-4 md:grid-cols-4">
                    <Info label="Статус спора" value={dispute.status_label} />
                    <Info label="Причина" value={dispute.reason_label} />
                    <Info label="Сумма заказа" value={`${currency.format(dispute.order.price)} ₽`} />
                    <Info label="Оплата" value={dispute.order.payment_status_label} />
                </div>

                {isResolved && (
                    <div className="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-5 text-sm leading-6 text-emerald-900">
                        <p className="font-semibold">{dispute.resolution_label}</p>
                        <p className="mt-2">{dispute.moderator_comment}</p>
                    </div>
                )}

                <div className="mt-6 grid gap-6 xl:grid-cols-[1fr_420px]">
                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-2xl font-semibold text-slate-950">Переписка по спору</h2>
                        <div className="mt-5 space-y-4">
                            {dispute.messages.length > 0 ? (
                                dispute.messages.map((message) => <Message key={message.id} message={message} />)
                            ) : (
                                <EmptyState title="Сообщений пока нет" text="Опишите детали спора внутри платформы." />
                            )}
                        </div>

                        {!isResolved && (
                            <form onSubmit={submit} className="mt-6 border-t border-slate-200 pt-5">
                                <label htmlFor="body" className="text-sm font-semibold text-slate-900">Сообщение</label>
                                <textarea
                                    id="body"
                                    name="body"
                                    rows={5}
                                    value={form.data.body}
                                    onChange={(event) => form.setData('body', event.target.value)}
                                    className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                                {form.errors.body && <p className="mt-2 text-sm text-red-600">{form.errors.body}</p>}
                                <button
                                    type="submit"
                                    disabled={form.processing}
                                    className="mt-3 rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Отправить сообщение
                                </button>
                            </form>
                        )}
                    </section>

                    <aside className="space-y-6">
                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-xl font-semibold text-slate-950">Участники</h2>
                            <div className="mt-4 space-y-3 text-sm text-slate-700">
                                <Participant label="Заказчик" user={dispute.order.customer} />
                                <Participant label="Исполнитель" user={dispute.order.performer} />
                                <Participant label="Открыл спор" user={dispute.opened_by} />
                            </div>
                        </section>

                        <Materials materials={dispute.materials} />
                    </aside>
                </div>
            </section>
        </DashboardLayout>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-2 font-semibold text-slate-950">{value ?? 'Не указано'}</p>
        </div>
    );
}

function Participant({ label, user }) {
    return (
        <div className="rounded-lg bg-slate-50 p-4">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-2 font-semibold text-slate-950">{user?.name ?? 'Не указан'}</p>
        </div>
    );
}

function Message({ message }) {
    return (
        <article className={`rounded-lg border p-5 ${message.is_system ? 'border-blue-200 bg-blue-50' : 'border-slate-200 bg-slate-50'}`}>
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

function Materials({ materials }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="text-xl font-semibold text-slate-950">Материалы заказа</h2>
            <MaterialList title="Последние сообщения" items={materials.messages} render={(item) => `${item.author_role}: ${item.body}`} />
            <MaterialList title="Файлы" items={materials.files} render={(item) => item.original_name} />
            <MaterialList title="События" items={materials.events} render={(item) => item.type} />
            <MaterialList title="Сдачи работы" items={materials.submissions} render={(item) => `${item.status}: ${item.message}`} />
        </section>
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

function EmptyState({ title, text }) {
    return (
        <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-5 text-center">
            <p className="text-sm font-semibold text-slate-950">{title}</p>
            <p className="mt-2 text-sm leading-6 text-slate-600">{text}</p>
        </div>
    );
}
