import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

const statusClasses = {
    draft: 'bg-slate-100 text-slate-700',
    published: 'bg-emerald-50 text-emerald-700',
    closed: 'bg-blue-50 text-blue-700',
    archived: 'bg-slate-200 text-slate-600',
};

const offerClasses = {
    submitted: 'bg-emerald-50 text-emerald-700',
    withdrawn: 'bg-slate-100 text-slate-700',
    rejected: 'bg-red-50 text-red-700',
    accepted: 'bg-blue-50 text-blue-700',
};

export default function Show({ task, statusLabels, offerStatusLabels }) {
    return (
        <DashboardLayout>
            <Head title={task.title} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <div className="flex flex-wrap items-center gap-2">
                            <span className={`rounded-md px-3 py-1 text-xs font-semibold ${statusClasses[task.status] ?? statusClasses.draft}`}>
                                {statusLabels[task.status]}
                            </span>
                            <span className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{task.category}</span>
                        </div>
                        <h1 className="mt-4 max-w-4xl text-4xl font-semibold tracking-normal text-slate-950">{task.title}</h1>
                        <div className="mt-5 grid gap-3 text-sm text-slate-600 sm:grid-cols-3">
                            <p>Бюджет: <span className="font-semibold text-slate-950">{task.budget_label}</span></p>
                            <p>Срок: <span className="font-semibold text-slate-950">{task.deadline_label ?? 'Не указан'}</span></p>
                            <p>Просмотры: <span className="font-semibold text-slate-950">{task.views_count}</span></p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href="/customer/tasks" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            К списку
                        </Link>
                        {task.public_url && (
                            <Link href={task.public_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                Публичная страница
                            </Link>
                        )}
                        {task.status !== 'archived' && (
                            <Link href={task.edit_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                Редактировать
                            </Link>
                        )}
                        {task.status === 'draft' && (
                            <Link href={task.publish_url} method="post" as="button" className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                Опубликовать
                            </Link>
                        )}
                        {task.status !== 'archived' && (
                            <Link href={task.archive_url} method="post" as="button" className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                В архив
                            </Link>
                        )}
                    </div>
                </div>

                <div className="mt-8 grid gap-6 lg:grid-cols-[1fr_380px]">
                    <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-2xl font-semibold text-slate-950">Описание задания</h2>
                        <p className="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{task.description}</p>
                    </article>

                    <aside className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <p className="text-sm font-semibold uppercase text-blue-700">Отклики</p>
                        <p className="mt-2 text-3xl font-semibold text-slate-950">{task.offers_count}</p>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Здесь появляются предложения исполнителей. Подходящий отклик можно выбрать, чтобы создать заказ.
                        </p>
                    </aside>
                </div>

                <section className="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
                        <div>
                            <p className="text-sm font-semibold uppercase text-blue-700">Предложения</p>
                            <h2 className="mt-2 text-2xl font-semibold text-slate-950">Отклики исполнителей</h2>
                        </div>
                    </div>

                    {task.offers.length > 0 ? (
                        <div className="mt-6 space-y-4">
                            {task.offers.map((offer) => (
                                <article key={offer.id} className="rounded-lg border border-slate-200 bg-slate-50 p-5">
                                    <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                                        <div>
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className={`rounded-md px-3 py-1 text-xs font-semibold ${offerClasses[offer.status] ?? offerClasses.submitted}`}>
                                                    {offerStatusLabels[offer.status]}
                                                </span>
                                                <span className="text-sm font-semibold text-slate-950">{offer.performer.name}</span>
                                            </div>
                                            <p className="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{offer.message}</p>
                                            <div className="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-3">
                                                <p>Цена: <span className="font-semibold text-slate-950">{currency.format(offer.price)} ₽</span></p>
                                                <p>Срок: <span className="font-semibold text-slate-950">{offer.delivery_days} дн.</span></p>
                                                <p>Отправлен: <span className="font-semibold text-slate-950">{offer.created_at}</span></p>
                                            </div>
                                        </div>
                                        {offer.status === 'submitted' && task.status !== 'closed' && task.status !== 'archived' && (
                                            <div className="flex flex-wrap gap-2">
                                                <Link
                                                    href={offer.accept_url}
                                                    method="post"
                                                    as="button"
                                                    className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                                >
                                                    Выбрать исполнителя
                                                </Link>
                                                <Link
                                                    href={offer.reject_url}
                                                    method="post"
                                                    as="button"
                                                    className="rounded-md border border-red-200 bg-white px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-50"
                                                >
                                                    Отклонить
                                                </Link>
                                            </div>
                                        )}
                                    </div>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="mt-6 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center">
                            <h3 className="text-xl font-semibold text-slate-950">Откликов пока нет</h3>
                            <p className="mt-2 text-sm leading-6 text-slate-600">После публикации исполнители смогут предложить цену и срок выполнения.</p>
                        </div>
                    )}
                </section>
            </section>
        </DashboardLayout>
    );
}
