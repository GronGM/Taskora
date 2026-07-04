import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

const statusClasses = {
    draft: 'bg-slate-100 text-slate-700',
    pending_review: 'bg-amber-50 text-amber-700',
    published: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-red-50 text-red-700',
    archived: 'bg-slate-200 text-slate-600',
};

const canSubmitReview = (status) => ['draft', 'rejected'].includes(status);

export default function Index({ services, statusLabels }) {
    return (
        <DashboardLayout>
            <Head title="Мои услуги" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Исполнитель</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950">Мои услуги</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Управляйте черновиками, отправляйте услуги на модерацию и архивируйте неактуальные предложения.
                        </p>
                    </div>
                    <Link
                        href="/performer/services/create"
                        className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        Создать услугу
                    </Link>
                </div>

                {services.length > 0 ? (
                    <div className="mt-8 grid gap-4">
                        {services.map((service) => (
                            <article key={service.id} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className={`rounded-md px-3 py-1 text-xs font-semibold ${statusClasses[service.status] ?? statusClasses.draft}`}>
                                                {statusLabels[service.status]}
                                            </span>
                                            <span className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                                {service.category}
                                            </span>
                                        </div>
                                        <h2 className="mt-4 text-2xl font-semibold text-slate-950">{service.title}</h2>
                                        {service.status === 'rejected' && service.rejection_reason && (
                                            <div className="mt-4 rounded-lg border border-red-200 bg-red-50 p-4">
                                                <p className="text-sm font-semibold text-red-800">Причина отклонения</p>
                                                <p className="mt-2 text-sm leading-6 text-red-700">{service.rejection_reason}</p>
                                            </div>
                                        )}
                                        <div className="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-3">
                                            <p>Цена от: <span className="font-semibold text-slate-950">{currency.format(service.price_from)} ₽</span></p>
                                            <p>Срок: <span className="font-semibold text-slate-950">{service.delivery_days} дн.</span></p>
                                            <p>Пакетов: <span className="font-semibold text-slate-950">{service.packages_count}</span></p>
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap gap-2 lg:justify-end">
                                        {service.public_url && (
                                            <Link
                                                href={service.public_url}
                                                className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                            >
                                                Открыть
                                            </Link>
                                        )}
                                        <Link
                                            href={service.edit_url}
                                            className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                        >
                                            Редактировать
                                        </Link>
                                        {canSubmitReview(service.status) && (
                                            <Link
                                                href={service.submit_review_url}
                                                method="post"
                                                as="button"
                                                className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                            >
                                                Отправить на модерацию
                                            </Link>
                                        )}
                                        {service.status !== 'archived' && (
                                            <Link
                                                href={service.archive_url}
                                                method="post"
                                                as="button"
                                                className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                            >
                                                Архивировать
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Услуг пока нет</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Создайте первую услугу, сохраните черновик и отправьте его на модерацию, когда описание будет готово.
                        </p>
                        <Link
                            href="/performer/services/create"
                            className="mt-6 inline-flex rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Создать услугу
                        </Link>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}
