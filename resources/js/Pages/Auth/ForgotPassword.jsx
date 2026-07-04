import { Head, Link, useForm } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function ForgotPassword({ mailLogNotice = false, flash = {} }) {
    const form = useForm({
        email: '',
    });
    const status = flash.status || flash.success;

    const submit = (event) => {
        event.preventDefault();
        form.post('/forgot-password', { preserveScroll: true });
    };

    return (
        <PublicLayout>
            <Head title="Восстановление пароля" />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[0.9fr_1.1fr] lg:px-8 lg:py-20">
                    <div className="flex flex-col justify-center">
                        <p className="text-sm font-semibold uppercase text-blue-700">Доступ к аккаунту</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">
                            Восстановление пароля
                        </h1>
                        <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-600">
                            Введите почту, и мы отправим ссылку для сброса пароля.
                        </p>
                    </div>

                    <form onSubmit={submit} className="rounded-lg border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        {status && (
                            <div
                                role="status"
                                className="mb-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
                            >
                                {status}
                            </div>
                        )}

                        {mailLogNotice && (
                            <div className="mb-5 rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                В тестовом режиме письма не отправляются на реальную почту, ссылка появляется в логах
                                приложения.
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

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="mt-6 w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            {form.processing ? 'Отправляем...' : 'Отправить ссылку'}
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
