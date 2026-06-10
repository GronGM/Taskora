import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const statusClasses = {
    draft: 'bg-slate-100 text-slate-700',
    published: 'bg-emerald-50 text-emerald-700',
    closed: 'bg-blue-50 text-blue-700',
    archived: 'bg-slate-200 text-slate-600',
};

export default function Index({ tasks, statusLabels }) {
    return (
        <DashboardLayout>
            <Head title="Мои задания" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Заказчик</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-normal text-slate-950">Мои задания</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Публикуйте задачи на бирже, собирайте отклики исполнителей и держите черновики отдельно от публичной витрины.
                        </p>
                    </div>
                    <Link
                        href="/customer/tasks/create"
                        className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        Создать задание
                    </Link>
                </div>

                {tasks.length > 0 ? (
                    <div className="mt-8 grid gap-4">
                        {tasks.map((task) => (
                            <article key={task.id} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className={`rounded-md px-3 py-1 text-xs font-semibold ${statusClasses[task.status] ?? statusClasses.draft}`}>
                                                {statusLabels[task.status]}
                                            </span>
                                            <span className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                                {task.category}
                                            </span>
                                        </div>
                                        <h2 className="mt-4 text-2xl font-semibold text-slate-950">{task.title}</h2>
                                        <div className="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-3">
                                            <p>Бюджет: <span className="font-semibold text-slate-950">{task.budget_label}</span></p>
                                            <p>Срок: <span className="font-semibold text-slate-950">{task.deadline_at ?? 'Не указан'}</span></p>
                                            <p>Откликов: <span className="font-semibold text-slate-950">{task.offers_count}</span></p>
                                        </div>
                                    </div>

                                    <div className="flex flex-wrap gap-2 lg:justify-end">
                                        {task.public_url && (
                                            <Link
                                                href={task.public_url}
                                                className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                            >
                                                Публичная
                                            </Link>
                                        )}
                                        <Link
                                            href={task.show_url}
                                            className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                        >
                                            Открыть
                                        </Link>
                                        {task.status !== 'archived' && (
                                            <Link
                                                href={task.edit_url}
                                                className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                            >
                                                Редактировать
                                            </Link>
                                        )}
                                        {task.status === 'draft' && (
                                            <Link
                                                href={task.publish_url}
                                                method="post"
                                                as="button"
                                                className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                            >
                                                Опубликовать
                                            </Link>
                                        )}
                                        {task.status !== 'archived' && (
                                            <Link
                                                href={task.archive_url}
                                                method="post"
                                                as="button"
                                                className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                            >
                                                В архив
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Заданий пока нет</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Создайте первое задание, сохраните черновик или сразу опубликуйте его на бирже.
                        </p>
                        <Link
                            href="/customer/tasks/create"
                            className="mt-6 inline-flex rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Создать задание
                        </Link>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}
