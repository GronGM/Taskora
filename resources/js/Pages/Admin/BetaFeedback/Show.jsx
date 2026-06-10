import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

export default function Show({ feedback, statusOptions = [], labels = {}, flash = {} }) {
    const form = useForm({
        status: feedback.status,
    });

    const submit = (event) => {
        event.preventDefault();
        form.post(feedback.status_url, { preserveScroll: true });
    };

    const statuses = labels.statuses ?? {};
    const types = labels.types ?? {};
    const severities = labels.severities ?? {};

    return (
        <DashboardLayout>
            <Head title={`Beta-отзыв #${feedback.id}`} />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Beta-обратная связь</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">{feedback.title}</h1>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Обращение #{feedback.id} · {feedback.created_at}
                        </p>
                    </div>
                    <Link href="/admin/beta-feedback" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        К списку
                    </Link>
                </div>

                {flash.success && (
                    <div className="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">
                        {flash.success}
                    </div>
                )}

                <div className="mt-8 grid gap-6 lg:grid-cols-[1fr_280px]">
                    <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="grid gap-4 text-sm sm:grid-cols-2">
                            <Info label="Статус" value={statuses[feedback.status] ?? feedback.status} />
                            <Info label="Тип" value={types[feedback.type] ?? feedback.type} />
                            <Info label="Критичность" value={severities[feedback.severity] ?? feedback.severity} />
                            <Info label="Роль тестировщика" value={feedback.role ?? 'Не указана'} />
                            <Info label="Пользователь" value={feedback.user ?? 'Гость'} />
                            <Info label="Email пользователя" value={feedback.user_email ?? 'Не указан'} />
                            <Info label="Сценарий" value={feedback.scenario ?? 'Не указан'} />
                            <Info label="Размер экрана" value={feedback.screen_size ?? 'Не указан'} />
                        </div>

                        {feedback.page_url && (
                            <div className="mt-5 rounded-md bg-slate-50 p-4">
                                <p className="text-xs font-semibold uppercase text-slate-500">Страница</p>
                                <p className="mt-2 break-all text-sm font-semibold text-slate-950">{feedback.page_url}</p>
                            </div>
                        )}

                        {feedback.browser && (
                            <div className="mt-5 rounded-md bg-slate-50 p-4">
                                <p className="text-xs font-semibold uppercase text-slate-500">Браузер</p>
                                <p className="mt-2 break-all text-sm text-slate-700">{feedback.browser}</p>
                            </div>
                        )}

                        <div className="mt-5 rounded-md border border-slate-200 bg-white p-4">
                            <p className="text-xs font-semibold uppercase text-slate-500">Описание</p>
                            <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-slate-800">{feedback.description}</p>
                        </div>
                    </article>

                    <aside className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-lg font-semibold text-slate-950">Статус обращения</h2>
                        <form onSubmit={submit} className="mt-5">
                            <label className="block">
                                <span className="text-sm font-semibold text-slate-900">Новый статус</span>
                                <select
                                    value={form.data.status}
                                    onChange={(event) => form.setData('status', event.target.value)}
                                    className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                >
                                    {statusOptions.map((option) => (
                                        <option key={option.value} value={option.value}>{option.label}</option>
                                    ))}
                                </select>
                                {form.errors.status && <span className="mt-2 block text-sm text-red-600">{form.errors.status}</span>}
                            </label>
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="mt-4 w-full rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Обновить статус
                            </button>
                        </form>
                    </aside>
                </div>
            </section>
        </DashboardLayout>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-md bg-slate-50 p-4">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-2 font-semibold text-slate-950">{value}</p>
        </div>
    );
}
