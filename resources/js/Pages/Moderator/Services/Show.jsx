import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

export default function Show({ service, flags }) {
    const rejectForm = useForm({
        reason: '',
    });

    const reject = (event) => {
        event.preventDefault();
        rejectForm.post(service.reject_url);
    };

    return (
        <DashboardLayout>
            <Head title={`Проверка: ${service.title}`} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8 flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Модерация услуг</p>
                        <h1 className="mt-2 max-w-4xl text-4xl font-semibold tracking-normal text-slate-950">{service.title}</h1>
                        <p className="mt-4 text-sm text-slate-600">
                            Отправлена на проверку: <span className="font-semibold text-slate-950">{service.submitted_at}</span>
                        </p>
                    </div>
                    <Link
                        href="/moderator/services"
                        className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                    >
                        К очереди
                    </Link>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                    <div className="space-y-6">
                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-2xl font-semibold text-slate-950">Описание услуги</h2>
                            <dl className="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-slate-500">Категория</dt>
                                    <dd className="mt-1 font-semibold text-slate-950">{service.category.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-slate-500">Цена и срок</dt>
                                    <dd className="mt-1 font-semibold text-slate-950">
                                        от {currency.format(service.price_from)} ₽, {service.delivery_days} дн.
                                    </dd>
                                </div>
                            </dl>
                            <div className="mt-6">
                                <h3 className="text-sm font-semibold text-slate-950">Краткое описание</h3>
                                <p className="mt-2 text-sm leading-6 text-slate-700">{service.short_description}</p>
                            </div>
                            <div className="mt-6">
                                <h3 className="text-sm font-semibold text-slate-950">Полное описание</h3>
                                <p className="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700">{service.description || 'Не заполнено'}</p>
                            </div>
                        </section>

                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-2xl font-semibold text-slate-950">Пакеты</h2>
                            <div className="mt-5 grid gap-4">
                                {service.packages.map((pack, index) => (
                                    <article key={`${pack.name}-${index}`} className="rounded-lg border border-slate-200 bg-slate-50 p-5">
                                        <div className="flex flex-col justify-between gap-3 sm:flex-row">
                                            <div>
                                                <h3 className="text-lg font-semibold text-slate-950">{pack.name}</h3>
                                                <p className="mt-2 text-sm leading-6 text-slate-600">{pack.description || 'Описание не заполнено'}</p>
                                            </div>
                                            <div className="text-sm text-slate-600 sm:text-right">
                                                <p><span className="font-semibold text-slate-950">{currency.format(pack.price)} ₽</span></p>
                                                <p>{pack.delivery_days} дн., правок: {pack.revisions_count}</p>
                                            </div>
                                        </div>
                                    </article>
                                ))}
                            </div>
                        </section>

                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-2xl font-semibold text-slate-950">Флаги по услуге</h2>
                            {flags.length > 0 ? (
                                <div className="mt-5 grid gap-3">
                                    {flags.map((flag) => (
                                        <article key={flag.id} className="rounded-lg border border-amber-200 bg-amber-50 p-4">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="rounded-md bg-white px-2 py-1 text-xs font-semibold text-amber-700">{flag.status}</span>
                                                <span className="text-sm font-semibold text-slate-950">{flag.matched_type || 'тип не указан'}</span>
                                            </div>
                                            <p className="mt-2 text-sm text-slate-700">{flag.reason}</p>
                                            {flag.matched_value && (
                                                <p className="mt-2 break-all text-xs text-slate-500">Фрагмент: {flag.matched_value}</p>
                                            )}
                                        </article>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-3 text-sm text-slate-600">Флагов по этой услуге нет.</p>
                            )}
                        </section>
                    </div>

                    <aside className="space-y-6">
                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-xl font-semibold text-slate-950">Исполнитель</h2>
                            <p className="mt-4 text-sm font-semibold text-slate-950">{service.performer.name}</p>
                            <p className="mt-1 text-sm text-slate-600">{service.performer.email}</p>
                        </section>

                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-xl font-semibold text-slate-950">Решение</h2>
                            <p className="mt-3 text-sm leading-6 text-slate-600">
                                Публикация сделает услугу видимой в публичном каталоге Таскоры.
                            </p>
                            <Link
                                href={service.approve_url}
                                method="post"
                                as="button"
                                className="mt-5 w-full rounded-md bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700"
                            >
                                Опубликовать
                            </Link>

                            <form onSubmit={reject} className="mt-6 border-t border-slate-200 pt-6">
                                <label htmlFor="reason" className="text-sm font-semibold text-slate-900">Отклонить с причиной</label>
                                <textarea
                                    id="reason"
                                    value={rejectForm.data.reason}
                                    onChange={(event) => rejectForm.setData('reason', event.target.value)}
                                    rows={5}
                                    className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                                {rejectForm.errors.reason && <p className="mt-2 text-sm text-red-600">{rejectForm.errors.reason}</p>}
                                <button
                                    type="submit"
                                    disabled={rejectForm.processing}
                                    className="mt-3 w-full rounded-md border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Отклонить
                                </button>
                            </form>
                        </section>
                    </aside>
                </div>
            </section>
        </DashboardLayout>
    );
}
