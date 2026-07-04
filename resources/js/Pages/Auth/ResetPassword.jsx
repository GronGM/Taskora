import { Head, Link, useForm } from '@inertiajs/react';
import PasswordInput from '../../Components/Auth/PasswordInput';
import PublicLayout from '../../Layouts/PublicLayout';

const currentResetToken = () => {
    if (typeof window === 'undefined') {
        return '';
    }

    return decodeURIComponent(window.location.pathname.split('/reset-password/')[1] ?? '');
};

export default function ResetPassword({ email = '' }) {
    const form = useForm({
        token: currentResetToken(),
        email,
        password: '',
        password_confirmation: '',
    });
    const formError = form.errors.email || form.errors.token;

    const submit = (event) => {
        event.preventDefault();
        form.post('/reset-password', { preserveScroll: true });
    };

    return (
        <PublicLayout>
            <Head title="Новый пароль" />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-20">
                    <div className="flex flex-col justify-center">
                        <p className="text-sm font-semibold uppercase text-blue-700">Сброс пароля</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Новый пароль</h1>
                        <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-600">
                            Укажите почту аккаунта и новый пароль. После сохранения можно будет войти с новым паролем.
                        </p>
                    </div>

                    <form onSubmit={submit} className="rounded-lg border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        {formError && (
                            <div
                                role="alert"
                                className="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
                            >
                                {formError}
                            </div>
                        )}

                        <div>
                            <label className="text-sm font-semibold text-slate-900" htmlFor="email">
                                Email
                            </label>
                            <input
                                id="email"
                                name="email"
                                type="email"
                                value={form.data.email}
                                onChange={(event) => form.setData('email', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                autoComplete="email"
                                required
                            />
                            {form.errors.email && <p className="mt-2 text-sm text-red-600">{form.errors.email}</p>}
                        </div>

                        <div className="mt-5 grid gap-4 sm:grid-cols-2">
                            <PasswordInput
                                id="password"
                                name="password"
                                label="Новый пароль"
                                value={form.data.password}
                                onChange={(event) => form.setData('password', event.target.value)}
                                autoComplete="new-password"
                                error={form.errors.password}
                                required
                            />
                            <PasswordInput
                                id="password_confirmation"
                                name="password_confirmation"
                                label="Подтверждение пароля"
                                value={form.data.password_confirmation}
                                onChange={(event) => form.setData('password_confirmation', event.target.value)}
                                autoComplete="new-password"
                                error={form.errors.password_confirmation}
                                required
                            />
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="mt-6 w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {form.processing ? 'Сохраняем...' : 'Сохранить новый пароль'}
                        </button>

                        <p className="mt-5 text-center text-sm text-slate-600">
                            <Link href="/login" className="font-semibold text-blue-700 hover:text-blue-800">
                                Вернуться ко входу
                            </Link>
                        </p>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
