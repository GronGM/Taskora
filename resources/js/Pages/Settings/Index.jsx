import { Head, useForm, usePage } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const inputClass = 'w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';

export default function SettingsIndex({ account }) {
    const { flash } = usePage().props;

    const profileForm = useForm({
        name: account.name,
        email: account.email,
    });

    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submitProfile = (event) => {
        event.preventDefault();
        profileForm.patch('/settings');
    };

    const submitPassword = (event) => {
        event.preventDefault();
        passwordForm.patch('/settings/password', {
            onSuccess: () => passwordForm.reset(),
        });
    };

    return (
        <DashboardLayout>
            <Head title="Настройки аккаунта" />
            <section className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
                <p className="text-sm font-semibold uppercase text-blue-700">Аккаунт</p>
                <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950 dark:text-slate-100">Настройки аккаунта</h1>
                <p className="mt-3 text-sm text-slate-600 dark:text-slate-400">Роль: {account.role_label}. Роль аккаунта изменить нельзя — для второй роли зарегистрируйте отдельный аккаунт.</p>

                {flash?.success && (
                    <p className="mt-4 rounded-md border border-emerald-200 bg-emerald-50 p-3 text-sm font-semibold text-emerald-800">{flash.success}</p>
                )}

                <form onSubmit={submitProfile} className="mt-8 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 className="text-lg font-semibold text-slate-950 dark:text-slate-100">Основные данные</h2>
                    <div className="mt-5 grid gap-5 sm:grid-cols-2">
                        <div>
                            <label htmlFor="name" className="text-sm font-semibold text-slate-900 dark:text-slate-200">Имя</label>
                            <input id="name" type="text" value={profileForm.data.name} onChange={(event) => profileForm.setData('name', event.target.value)} className={`mt-2 ${inputClass}`} />
                            {profileForm.errors.name && <p className="mt-2 text-sm text-red-600">{profileForm.errors.name}</p>}
                        </div>
                        <div>
                            <label htmlFor="email" className="text-sm font-semibold text-slate-900 dark:text-slate-200">Email</label>
                            <input id="email" type="email" value={profileForm.data.email} onChange={(event) => profileForm.setData('email', event.target.value)} className={`mt-2 ${inputClass}`} />
                            {profileForm.errors.email && <p className="mt-2 text-sm text-red-600">{profileForm.errors.email}</p>}
                        </div>
                    </div>
                    <button type="submit" disabled={profileForm.processing} className="mt-6 rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                        Сохранить
                    </button>
                </form>

                <form onSubmit={submitPassword} className="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 className="text-lg font-semibold text-slate-950 dark:text-slate-100">Смена пароля</h2>
                    <div className="mt-5 grid gap-5">
                        <div>
                            <label htmlFor="current_password" className="text-sm font-semibold text-slate-900 dark:text-slate-200">Текущий пароль</label>
                            <input id="current_password" type="password" autoComplete="current-password" value={passwordForm.data.current_password} onChange={(event) => passwordForm.setData('current_password', event.target.value)} className={`mt-2 ${inputClass}`} />
                            {passwordForm.errors.current_password && <p className="mt-2 text-sm text-red-600">{passwordForm.errors.current_password}</p>}
                        </div>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label htmlFor="password" className="text-sm font-semibold text-slate-900 dark:text-slate-200">Новый пароль</label>
                                <input id="password" type="password" autoComplete="new-password" value={passwordForm.data.password} onChange={(event) => passwordForm.setData('password', event.target.value)} className={`mt-2 ${inputClass}`} />
                                {passwordForm.errors.password && <p className="mt-2 text-sm text-red-600">{passwordForm.errors.password}</p>}
                            </div>
                            <div>
                                <label htmlFor="password_confirmation" className="text-sm font-semibold text-slate-900 dark:text-slate-200">Повторите пароль</label>
                                <input id="password_confirmation" type="password" autoComplete="new-password" value={passwordForm.data.password_confirmation} onChange={(event) => passwordForm.setData('password_confirmation', event.target.value)} className={`mt-2 ${inputClass}`} />
                            </div>
                        </div>
                    </div>
                    <button type="submit" disabled={passwordForm.processing} className="mt-6 rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-60">
                        Изменить пароль
                    </button>
                </form>
            </section>
        </DashboardLayout>
    );
}
