import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const safeMatchedValue = (flag) => {
    if (!flag.matched_value) {
        return 'Не указано';
    }

    if (flag.matched_type === 'bank_card') {
        return 'Скрыто: банковские данные';
    }

    return flag.matched_value;
};

export default function Index({ flags }) {
    return (
        <DashboardLayout>
            <Head title="Флаги модерации" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Модерация</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950">Флаги модерации</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Открытые срабатывания ContactGuard по услугам и другим пользовательским материалам.
                        </p>
                    </div>
                    <Link
                        href="/moderator/services"
                        className="inline-flex rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                    >
                        Модерация услуг
                    </Link>
                </div>

                {flags.length > 0 ? (
                    <div className="mt-8 grid gap-4">
                        {flags.map((flag) => (
                            <article key={flag.id} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="grid gap-5 lg:grid-cols-[1fr_auto] lg:items-start">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="rounded-md bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">Открыт</span>
                                            <span className="rounded-md bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">
                                                {flag.matched_type || 'тип не указан'}
                                            </span>
                                        </div>
                                        <p className="mt-4 text-sm leading-6 text-slate-700">{flag.reason}</p>
                                        <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                            <div>
                                                <dt className="text-slate-500">Совпадение</dt>
                                                <dd className="mt-1 break-all font-semibold text-slate-950">{safeMatchedValue(flag)}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-slate-500">Пользователь</dt>
                                                <dd className="mt-1 font-semibold text-slate-950">{flag.user || 'Не указан'}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-slate-500">Сущность</dt>
                                                <dd className="mt-1 font-semibold text-slate-950">{flag.entity_type || 'Entity'} #{flag.entity_id || '-'}</dd>
                                            </div>
                                            <div>
                                                <dt className="text-slate-500">Дата</dt>
                                                <dd className="mt-1 font-semibold text-slate-950">{flag.created_at}</dd>
                                            </div>
                                        </dl>
                                    </div>
                                    <Link
                                        href={flag.resolve_url}
                                        method="post"
                                        as="button"
                                        className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                                    >
                                        Отметить обработанным
                                    </Link>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Открытых флагов нет</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Новые срабатывания появятся здесь после блокировки контактов или обхода платформы.
                        </p>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}
