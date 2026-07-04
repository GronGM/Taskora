import { Head, Link, useForm } from '@inertiajs/react';
import PasswordInput from '../../Components/Auth/PasswordInput';
import PublicLayout from '../../Layouts/PublicLayout';

export default function Login({ flash = {} }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (event) => {
        event.preventDefault();
        post('/login', { preserveScroll: true });
    };

    const authError = errors.auth;

    return (
        <PublicLayout>
            <Head title="Войти" />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-20">
                    <div className="flex flex-col justify-center">
                        <p className="text-sm font-semibold uppercase text-blue-700">Вход</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">
                            Вернитесь в рабочее пространство Таскоры
                        </h1>
                        <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-600">
                            После входа система направит вас в кабинет, соответствующий роли аккаунта.
                        </p>
                    </div>

                    <form onSubmit={submit} className="rounded-lg border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        {flash.success && (
                            <div
                                role="status"
                                className="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
                            >
                                {flash.success}
                            </div>
                        )}

                        {authError && (
                            <div
                                role="alert"
                                className="mb-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700"
                            >
                                {authError}
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
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                onInput={(event) => setData('email', event.currentTarget.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                autoComplete="email"
                                required
                            />
                            {errors.email && <p className="mt-2 text-sm text-red-600">{errors.email}</p>}
                        </div>

                        <div className="mt-5">
                            <PasswordInput
                                id="password"
                                name="password"
                                label="Пароль"
                                value={data.password}
                                onChange={(event) => setData('password', event.target.value)}
                                autoComplete="current-password"
                                error={errors.password}
                                required
                            />
                            <div className="mt-3 text-right">
                                <Link href="/forgot-password" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                                    Забыли пароль?
                                </Link>
                            </div>
                        </div>

                        <label className="mt-5 flex items-center gap-3 text-sm text-slate-600">
                            <input
                                name="remember"
                                type="checkbox"
                                checked={data.remember}
                                onChange={(event) => setData('remember', event.target.checked)}
                                onInput={(event) => setData('remember', event.currentTarget.checked)}
                                className="h-4 w-4 rounded border-slate-300 text-blue-600"
                            />
                            Запомнить меня
                        </label>

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-6 w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {processing ? 'Входим...' : 'Войти'}
                        </button>

                        <p className="mt-5 text-center text-sm text-slate-600">
                            Нет аккаунта?{' '}
                            <Link href="/register" className="font-semibold text-blue-700 hover:text-blue-800">
                                Зарегистрироваться
                            </Link>
                        </p>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
