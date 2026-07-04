import { Head, useForm } from '@inertiajs/react';
import ThemeToggle from '../../Components/Theme/ThemeToggle';

export default function BetaAccess() {
    const form = useForm({
        password: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.post('/beta-access');
    };

    return (
        <div className="min-h-screen bg-slate-100 px-4 py-8 text-slate-950 dark:bg-slate-950 dark:text-white sm:px-6 lg:px-8">
            <Head title="Закрытое тестирование Таскоры" />

            <div className="mx-auto flex max-w-md justify-end">
                <ThemeToggle />
            </div>

            <main className="mx-auto flex min-h-[calc(100vh-6rem)] max-w-md flex-col justify-center">
                <div className="rounded-lg border border-slate-200 bg-white p-6 text-slate-950 shadow-2xl dark:border-white/10 dark:bg-slate-900 dark:text-slate-100">
                    <div className="flex items-center gap-3">
                        <span className="grid h-11 w-11 place-items-center rounded-md bg-blue-600" aria-hidden="true">
                            <span className="taskora-logo-mark h-4 w-4 rounded-sm bg-white" />
                        </span>
                        <span className="text-xl font-semibold tracking-tight">Таскора</span>
                    </div>

                    <div className="mt-8">
                        <p className="text-sm font-semibold uppercase text-blue-700 dark:text-blue-300">Beta access</p>
                        <h1 className="mt-3 text-3xl font-semibold tracking-tight text-slate-950 dark:text-slate-50">
                            Закрытое тестирование Таскоры
                        </h1>
                        <p className="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Сайт доступен только участникам тестирования
                        </p>
                    </div>

                    <form onSubmit={submit} className="mt-6">
                        <label htmlFor="password" className="text-sm font-semibold text-slate-900 dark:text-slate-100">
                            Пароль
                        </label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            value={form.data.password}
                            onChange={(event) => form.setData('password', event.target.value)}
                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-950 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-950"
                            autoComplete="current-password"
                            required
                        />
                        {form.errors.password && <p className="mt-2 text-sm text-red-600">{form.errors.password}</p>}

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="mt-6 w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Войти
                        </button>
                    </form>
                </div>
            </main>
        </div>
    );
}
