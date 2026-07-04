import { Head, Link, router, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const statusTone = {
    active: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    blocked: 'bg-red-50 text-red-700 ring-red-200',
};

export default function Index({ users = {}, filters = {}, summary = {}, labels = {} }) {
    const rows = users.data ?? [];
    const form = useForm({
        q: filters.q ?? '',
        role: filters.role ?? 'all',
        status: filters.status ?? 'all',
        has_performer_profile: filters.has_performer_profile ?? 'all',
        has_orders: filters.has_orders ?? 'all',
        sort: filters.sort ?? 'newest',
    });

    const submit = (event) => {
        event.preventDefault();
        router.get('/admin/users', form.data, { preserveState: true, preserveScroll: true });
    };

    return (
        <DashboardLayout>
            <Head title="Пользователи" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Администрирование</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Пользователи</h1>
                        <p className="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                            Поиск аккаунтов, проверка ролей, блокировка доступа и админские заметки. Пароли и служебные токены здесь не выводятся.
                        </p>
                    </div>
                    <Link href="/admin/dashboard" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                        В админ-панель
                    </Link>
                </div>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Summary title="Всего" value={summary.total ?? 0} />
                    <Summary title="Активны" value={summary.active ?? 0} />
                    <Summary title="Заблокированы" value={summary.blocked ?? 0} />
                    <Summary title="Администраторы" value={summary.admins ?? 0} />
                </div>

                <form onSubmit={submit} className="mt-8 grid gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-6">
                    <label className="lg:col-span-2">
                        <span className="text-sm font-medium text-slate-700">Поиск</span>
                        <input
                            value={form.data.q}
                            onChange={(event) => form.setData('q', event.target.value)}
                            className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                            placeholder="Имя или email"
                        />
                    </label>
                    <Select label="Роль" value={form.data.role} onChange={(value) => form.setData('role', value)}>
                        <option value="all">Все роли</option>
                        {Object.entries(labels.roles ?? {}).map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                        ))}
                    </Select>
                    <Select label="Статус" value={form.data.status} onChange={(value) => form.setData('status', value)}>
                        <option value="all">Все статусы</option>
                        {Object.entries(labels.statuses ?? {}).map(([value, label]) => (
                            <option key={value} value={value}>{label}</option>
                        ))}
                    </Select>
                    <Select label="Профиль" value={form.data.has_performer_profile} onChange={(value) => form.setData('has_performer_profile', value)}>
                        <option value="all">Любой</option>
                        <option value="yes">Есть профиль исполнителя</option>
                    </Select>
                    <Select label="Сортировка" value={form.data.sort} onChange={(value) => form.setData('sort', value)}>
                        <option value="newest">Новые</option>
                        <option value="last_login">Последние входы</option>
                        <option value="email">По email</option>
                        <option value="role">По роли</option>
                    </Select>
                    <div className="flex items-end gap-3 lg:col-span-6">
                        <Select label="Заказы" value={form.data.has_orders} onChange={(value) => form.setData('has_orders', value)}>
                            <option value="all">Любые аккаунты</option>
                            <option value="yes">Есть заказы</option>
                        </Select>
                        <button type="submit" className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                            Применить
                        </button>
                    </div>
                </form>

                <div className="mt-8 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    {rows.length > 0 ? (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-sm">
                                <thead className="bg-slate-50 text-left text-xs font-semibold uppercase text-slate-500">
                                    <tr>
                                        <th className="px-4 py-3">Пользователь</th>
                                        <th className="px-4 py-3">Роль</th>
                                        <th className="px-4 py-3">Статус</th>
                                        <th className="px-4 py-3">Регистрация</th>
                                        <th className="px-4 py-3">Последний вход</th>
                                        <th className="px-4 py-3">Заказы</th>
                                        <th className="px-4 py-3">Рейтинг</th>
                                        <th className="px-4 py-3" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rows.map((user) => (
                                        <tr key={user.id}>
                                            <td className="px-4 py-4">
                                                <p className="font-semibold text-slate-950">{user.name}</p>
                                                <p className="mt-1 text-slate-500">{user.email}</p>
                                            </td>
                                            <td className="px-4 py-4 text-slate-700">{user.role_label}</td>
                                            <td className="px-4 py-4">
                                                <Badge tone={statusTone[user.status]}>{user.status_label}</Badge>
                                            </td>
                                            <td className="px-4 py-4 text-slate-600">{user.created_at ?? '—'}</td>
                                            <td className="px-4 py-4 text-slate-600">{user.last_login_at ?? '—'}</td>
                                            <td className="px-4 py-4 text-slate-600">
                                                <span>Заказчик: {user.customer_orders_count}</span>
                                                <span className="block">Исполнитель: {user.performer_orders_count}</span>
                                            </td>
                                            <td className="px-4 py-4 text-slate-600">
                                                {user.has_performer_profile ? (user.performer_rating ?? 'нет отзывов') : '—'}
                                            </td>
                                            <td className="px-4 py-4 text-right">
                                                <Link href={user.show_url} className="rounded-md bg-slate-950 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                                    Открыть
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    ) : (
                        <div className="p-10 text-center">
                            <h2 className="text-2xl font-semibold text-slate-950">Пользователи не найдены</h2>
                            <p className="mt-3 text-sm leading-6 text-slate-600">Измените поиск или фильтры, чтобы увидеть аккаунты.</p>
                        </div>
                    )}
                </div>
            </section>
        </DashboardLayout>
    );
}

function Summary({ title, value }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-slate-500">{title}</p>
            <p className="mt-3 text-2xl font-semibold text-slate-950">{value}</p>
        </article>
    );
}

function Select({ label, value, onChange, children }) {
    return (
        <label>
            <span className="text-sm font-medium text-slate-700">{label}</span>
            <select
                value={value}
                onChange={(event) => onChange(event.target.value)}
                className="mt-2 w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
            >
                {children}
            </select>
        </label>
    );
}

function Badge({ children, tone = 'bg-slate-100 text-slate-700 ring-slate-200' }) {
    return <span className={`rounded-md px-3 py-1 text-xs font-semibold ring-1 ${tone}`}>{children}</span>;
}
