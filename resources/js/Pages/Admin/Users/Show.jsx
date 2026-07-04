import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const statusTone = {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    blocked: 'bg-red-50 text-red-700 ring-red-200',
};

export default function Show({ user = {}, related = {}, events = [], labels = {} }) {
    const blockForm = useForm({ reason: '' });
    const noteForm = useForm({ admin_note: user.admin_note ?? '' });

    const block = (event) => {
        event.preventDefault();

        if (!window.confirm('Заблокировать пользователя? Он не сможет войти в систему.')) {
            return;
        }

        blockForm.post(user.block_url, { preserveScroll: true });
    };

    const unblock = () => {
        if (!window.confirm('Разблокировать пользователя? Аккаунт снова сможет войти в систему.')) {
            return;
        }

        blockForm.post(user.unblock_url, { preserveScroll: true });
    };

    const updateNote = (event) => {
        event.preventDefault();
        noteForm.patch(user.admin_note_url, { preserveScroll: true });
    };

    return (
        <DashboardLayout>
            <Head title={`Пользователь ${user.email}`} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Пользователь</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{user.name}</h1>
                        <p className="mt-3 text-sm text-slate-600">{user.email}</p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Link href="/admin/users" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            К списку
                        </Link>
                        <Link href={user.edit_url} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Редактировать
                        </Link>
                    </div>
                </div>

                <div className="mt-8 grid gap-6 lg:grid-cols-[1fr_360px]">
                    <div className="space-y-6">
                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div className="flex flex-wrap items-center gap-2">
                                <Badge>{user.role_label}</Badge>
                                <Badge tone={statusTone[user.status]}>{user.status_label}</Badge>
                            </div>
                            <div className="mt-6 grid gap-4 sm:grid-cols-2">
                                <Info label="ID" value={user.id} />
                                <Info label="Дата регистрации" value={user.created_at} />
                                <Info label="Последний вход" value={user.last_login_at ?? '—'} />
                                <Info label="IP последнего входа" value={user.last_login_ip ?? '—'} />
                                <Info label="Рейтинг исполнителя" value={user.performer_rating ?? '—'} />
                                <Info label="Отзывы исполнителя" value={user.performer_reviews_count ?? 0} />
                            </div>
                            {user.status === 'blocked' && (
                                <div className="mt-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                                    <p className="font-semibold">Аккаунт заблокирован</p>
                                    <p className="mt-2">Дата: {user.blocked_at ?? '—'}</p>
                                    <p>Кем: {user.blocked_by ?? '—'}</p>
                                    <p className="mt-2 whitespace-pre-wrap">{user.block_reason}</p>
                                </div>
                            )}
                        </section>

                        {user.performer_profile && (
                            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <h2 className="text-lg font-semibold text-slate-950">Профиль исполнителя</h2>
                                <p className="mt-3 text-sm text-slate-600">{user.performer_profile.display_name}</p>
                                <p className="mt-1 text-sm text-slate-600">{user.performer_profile.headline}</p>
                                <p className="mt-1 text-sm text-slate-500">Статус проверки: {user.performer_profile.verification_status}</p>
                                <Link href={user.performer_profile.public_url} className="mt-4 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800">
                                    Открыть публичный профиль
                                </Link>
                            </section>
                        )}

                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">Связанные данные</h2>
                            <div className="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                                {Object.entries(related.counts ?? {}).map(([key, value]) => (
                                    <Info key={key} label={countLabels[key] ?? key} value={value} />
                                ))}
                            </div>
                        </section>

                        <RelatedList title="Заказы как заказчик" items={related.customer_orders} />
                        <RelatedList title="Заказы как исполнитель" items={related.performer_orders} />
                        <RelatedList title="Задания" items={related.tasks} />
                        <RelatedList title="Услуги" items={related.services} />
                        <RelatedList title="Beta feedback" items={related.beta_feedback} />
                        <RelatedList title="Флаги модерации" items={related.moderation_flags} />

                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">Аудит действий администратора</h2>
                            {events.length > 0 ? (
                                <div className="mt-5 divide-y divide-slate-100">
                                    {events.map((event) => (
                                        <article key={event.id} className="py-4">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <Badge>{event.type_label}</Badge>
                                                <span className="text-sm text-slate-500">{event.created_at}</span>
                                            </div>
                                            <p className="mt-2 text-sm text-slate-600">Администратор: {event.actor ?? '—'}</p>
                                            {event.comment && <p className="mt-2 whitespace-pre-wrap text-sm text-slate-600">{event.comment}</p>}
                                        </article>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-3 text-sm text-slate-600">Событий пока нет.</p>
                            )}
                        </section>
                    </div>

                    <aside className="space-y-6">
                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">Админская заметка</h2>
                            <form onSubmit={updateNote} className="mt-4">
                                <textarea
                                    value={noteForm.data.admin_note}
                                    onChange={(event) => noteForm.setData('admin_note', event.target.value)}
                                    rows="7"
                                    className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                    placeholder="Внутренняя заметка для администраторов"
                                />
                                {noteForm.errors.admin_note && <p className="mt-2 text-sm text-red-600">{noteForm.errors.admin_note}</p>}
                                <button type="submit" disabled={noteForm.processing} className="mt-3 rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60">
                                    {noteForm.processing ? 'Сохраняем...' : 'Сохранить заметку'}
                                </button>
                            </form>
                        </section>

                        <section className="rounded-lg border border-red-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">Опасные действия</h2>
                            <p className="mt-3 text-sm leading-6 text-slate-600">
                                Физическое удаление, смена пароля и вход от имени пользователя в MVP отключены.
                            </p>
                            {user.status === 'blocked' ? (
                                <button type="button" onClick={unblock} disabled={blockForm.processing} className="mt-4 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-60">
                                    Разблокировать
                                </button>
                            ) : (
                                <form onSubmit={block} className="mt-4 space-y-3">
                                    <textarea
                                        value={blockForm.data.reason}
                                        onChange={(event) => blockForm.setData('reason', event.target.value)}
                                        rows="5"
                                        className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-red-500 focus:outline-none focus:ring-2 focus:ring-red-100"
                                        placeholder="Причина блокировки, минимум 10 символов"
                                    />
                                    {blockForm.errors.reason && <p className="text-sm text-red-600">{blockForm.errors.reason}</p>}
                                    <button type="submit" disabled={blockForm.processing} className="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-60">
                                        {blockForm.processing ? 'Блокируем...' : 'Заблокировать'}
                                    </button>
                                </form>
                            )}
                        </section>
                    </aside>
                </div>
            </section>
        </DashboardLayout>
    );
}

const countLabels = {
    customer_orders: 'Заказы как заказчик',
    performer_orders: 'Заказы как исполнитель',
    tasks: 'Задания',
    services: 'Услуги',
    given_reviews: 'Отзывы оставлены',
    received_reviews: 'Отзывы получены',
    opened_disputes: 'Споры открыты',
    resolved_disputes: 'Споры решены',
    beta_feedback: 'Beta feedback',
    moderation_flags: 'Флаги модерации',
};

function Info({ label, value }) {
    return (
        <div>
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-1 text-sm text-slate-900">{value ?? '—'}</p>
        </div>
    );
}

function RelatedList({ title, items = [] }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <h2 className="text-lg font-semibold text-slate-950">{title}</h2>
            {items.length > 0 ? (
                <div className="mt-4 divide-y divide-slate-100">
                    {items.map((item) => (
                        <article key={item.id} className="py-3">
                            <p className="font-medium text-slate-950">{item.title ?? item.reason ?? `#${item.id}`}</p>
                            <p className="mt-1 text-sm text-slate-500">
                                {item.status ?? item.payment_status ?? '—'} · {item.created_at ?? '—'}
                            </p>
                        </article>
                    ))}
                </div>
            ) : (
                <p className="mt-3 text-sm text-slate-600">Данных нет.</p>
            )}
        </section>
    );
}

function Badge({ children, tone = 'bg-slate-100 text-slate-700 ring-slate-200' }) {
    return <span className={`rounded-md px-3 py-1 text-xs font-semibold ring-1 ${tone}`}>{children}</span>;
}
