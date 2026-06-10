import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import ServiceForm from '../../../Components/Performer/ServiceForm';

const statusClasses = {
    draft: 'bg-slate-100 text-slate-700',
    pending_review: 'bg-amber-50 text-amber-700',
    published: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-red-50 text-red-700',
    archived: 'bg-slate-200 text-slate-600',
};

const canSubmitReview = (status) => ['draft', 'rejected'].includes(status);

export default function Edit({ service, categories, statusLabels }) {
    const form = useForm({
        category_id: String(service.category_id),
        title: service.title,
        short_description: service.short_description,
        description: service.description ?? '',
        price_from: service.price_from,
        delivery_days: service.delivery_days,
        packages: service.packages.length > 0 ? service.packages : [],
    });

    const submit = (event) => {
        event.preventDefault();
        form.put(`/performer/services/${service.id}`);
    };

    return (
        <DashboardLayout>
            <Head title="Редактировать услугу" />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Мои услуги</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-normal text-slate-950">Редактировать услугу</h1>
                        <div className="mt-4 flex flex-wrap items-center gap-2">
                            <span className={`rounded-md px-3 py-1 text-sm font-semibold ${statusClasses[service.status] ?? statusClasses.draft}`}>
                                {statusLabels[service.status]}
                            </span>
                            {service.status === 'published' && (
                                <span className="text-sm text-slate-600">
                                    Изменение важных полей вернет услугу на модерацию.
                                </span>
                            )}
                            {service.status === 'pending_review' && (
                                <span className="text-sm text-slate-600">
                                    Услуга уже на проверке. Редактирование откроется после решения модератора.
                                </span>
                            )}
                        </div>
                        {service.status === 'rejected' && service.rejection_reason && (
                            <div className="mt-5 rounded-lg border border-red-200 bg-red-50 p-4">
                                <p className="text-sm font-semibold text-red-800">Причина отклонения</p>
                                <p className="mt-2 text-sm leading-6 text-red-700">{service.rejection_reason}</p>
                            </div>
                        )}
                    </div>
                    <Link
                        href="/performer/services"
                        className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                    >
                        К списку услуг
                    </Link>
                </div>

                <ServiceForm
                    form={form}
                    categories={categories}
                    onSubmit={submit}
                    submitLabel="Сохранить изменения"
                    disabled={service.is_locked}
                >
                    {canSubmitReview(service.status) && (
                        <Link
                            href={service.submit_review_url}
                            method="post"
                            as="button"
                            className="rounded-md border border-blue-200 bg-blue-50 px-5 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-100"
                        >
                            Отправить на модерацию
                        </Link>
                    )}
                    {service.status !== 'archived' && (
                        <Link
                            href={service.archive_url}
                            method="post"
                            as="button"
                            className="rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                        >
                            Архивировать
                        </Link>
                    )}
                </ServiceForm>
            </section>
        </DashboardLayout>
    );
}
