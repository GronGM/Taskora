import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

export default function Edit({ user = {}, roleOptions = [] }) {
    const form = useForm({
        name: user.name ?? '',
        email: user.email ?? '',
        role: user.role ?? 'customer',
        admin_note: user.admin_note ?? '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.patch(user.update_url);
    };

    return (
        <DashboardLayout>
            <Head title={`Редактировать ${user.email}`} />

            <section className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Пользователь</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Редактирование аккаунта</h1>
                        <p className="mt-3 text-sm text-slate-600">
                            Пароль и служебные токены здесь не отображаются и не изменяются.
                        </p>
                        </div>

                        <p className="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-600">
                            Статус аккаунта меняется только через отдельные действия блокировки и разблокировки в карточке пользователя.
                        </p>
                    <Link href={user.show_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        К пользователю
                    </Link>
                </div>

                <form onSubmit={submit} className="mt-8 space-y-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                        Статус аккаунта меняется отдельными действиями блокировки и разблокировки. Текущий статус: {user.status_label}.
                    </div>

                    <Field label="Имя" error={form.errors.name}>
                        <input
                            value={form.data.name}
                            onChange={(event) => form.setData('name', event.target.value)}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field label="Email" error={form.errors.email}>
                        <input
                            type="email"
                            value={form.data.email}
                            onChange={(event) => form.setData('email', event.target.value)}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field label="Роль" error={form.errors.role}>
                        <select
                            value={form.data.role}
                            onChange={(event) => form.setData('role', event.target.value)}
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        >
                            {roleOptions.map((option) => (
                                <option key={option.value} value={option.value}>{option.label}</option>
                            ))}
                        </select>
                    </Field>

                    <Field label="Админская заметка" error={form.errors.admin_note}>
                        <textarea
                            value={form.data.admin_note}
                            onChange={(event) => form.setData('admin_note', event.target.value)}
                            rows="7"
                            className="w-full rounded-md border border-slate-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <div className="flex flex-wrap items-center gap-3">
                        <button type="submit" disabled={form.processing} className="rounded-md bg-slate-950 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-60">
                            {form.processing ? 'Сохраняем...' : 'Сохранить'}
                        </button>
                        <Link href={user.show_url} className="text-sm font-semibold text-slate-600 hover:text-slate-950">
                            Отмена
                        </Link>
                    </div>
                </form>
            </section>
        </DashboardLayout>
    );
}

function Field({ label, error, children }) {
    return (
        <label className="block">
            <span className="text-sm font-medium text-slate-700">{label}</span>
            <div className="mt-2">{children}</div>
            {error && <p className="mt-2 text-sm text-red-600">{error}</p>}
        </label>
    );
}
