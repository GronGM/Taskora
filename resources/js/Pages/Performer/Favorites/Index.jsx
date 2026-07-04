import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const statusLabels = {
    active: 'Активные',
    closed: 'Закрытые и архивные',
};

export default function Index({ filters, tasks, categories, taskTypes }) {
    return (
        <DashboardLayout>
            <Head title="Избранное" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700 dark:text-blue-300">Исполнитель</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950 dark:text-white">Избранное</h1>
                        <p className="mt-4 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Сохраняйте подходящие задания, категории и виды работ, чтобы быстрее возвращаться к нужным направлениям на бирже.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href="/tasks?favorite_types=1" className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400">
                            Открыть мои виды заданий
                        </Link>
                        <Link href="/tasks?favorite_categories=1" className="inline-flex justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                            Открыть мои категории
                        </Link>
                    </div>
                </div>

                <div className="mt-8 grid gap-8">
                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                            <div>
                                <h2 className="text-2xl font-semibold text-slate-950 dark:text-white">Избранные задания</h2>
                                <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">Список задач, к которым вы хотите вернуться.</p>
                            </div>
                            <div className="flex flex-wrap gap-2">
                                {Object.entries(statusLabels).map(([value, label]) => (
                                    <Link
                                        key={value}
                                        href={`/performer/favorites?status=${value}`}
                                        className={`rounded-md px-4 py-2 text-sm font-semibold ${
                                            filters.status === value
                                                ? 'bg-slate-950 text-white dark:bg-white dark:text-slate-950'
                                                : 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800'
                                        }`}
                                    >
                                        {label}
                                    </Link>
                                ))}
                            </div>
                        </div>

                        {tasks.length > 0 ? (
                            <div className="mt-6 grid gap-4">
                                {tasks.map((task) => (
                                    <TaskCard key={task.id} task={task} />
                                ))}
                            </div>
                        ) : (
                            <EmptyState
                                title={filters.status === 'closed' ? 'Закрытых избранных заданий нет' : 'Активных избранных заданий пока нет'}
                                text="Откройте биржу, сохраните интересное задание и вернитесь к нему из этого раздела."
                                href="/tasks"
                                action="Найти задания"
                            />
                        )}
                    </section>

                    <div className="grid gap-8 lg:grid-cols-2">
                        <FavoriteGrid
                            title="Избранные категории"
                            text="Направления, по которым вы чаще всего ищете задания."
                            items={categories}
                            emptyTitle="Избранных категорий пока нет"
                            emptyText="Добавьте категории на бирже, чтобы быстро фильтровать подходящие задания."
                            kind="category"
                        />

                        <FavoriteGrid
                            title="Избранные виды заданий"
                            text="Более точные типы задач внутри категорий."
                            items={taskTypes}
                            emptyTitle="Избранных видов заданий пока нет"
                            emptyText="Сохраняйте виды заданий на бирже, чтобы быстро открывать нужный поток заказов."
                            kind="taskType"
                        />
                    </div>
                </div>
            </section>
        </DashboardLayout>
    );
}

function TaskCard({ task }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-950">
            <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div>
                    <div className="flex flex-wrap items-center gap-2">
                        {task.category && <Badge>{task.category}</Badge>}
                        {task.task_type && <Badge tone="slate">{task.task_type}</Badge>}
                        <Badge tone={task.status === 'published' ? 'green' : 'slate'}>
                            {task.status === 'published' ? 'Активно' : 'Закрыто'}
                        </Badge>
                    </div>
                    <h3 className="mt-4 text-xl font-semibold text-slate-950 dark:text-white">{task.title}</h3>
                    <div className="mt-4 grid gap-3 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2 lg:grid-cols-4">
                        <p>Бюджет: <span className="font-semibold text-slate-950 dark:text-white">{task.budget_label}</span></p>
                        <p>Срок: <span className="font-semibold text-slate-950 dark:text-white">{task.deadline_at ?? 'Не указан'}</span></p>
                        <p>Отклики: <span className="font-semibold text-slate-950 dark:text-white">{task.offers_count}</span></p>
                        <p>Заказчик: <span className="font-semibold text-slate-950 dark:text-white">{task.customer ?? 'Заказчик'}</span></p>
                    </div>
                </div>
                <div className="flex shrink-0 flex-wrap gap-2 xl:justify-end">
                    {task.status === 'published' && (
                        <Link href={task.url} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                            Открыть
                        </Link>
                    )}
                    <Link
                        href={task.favorite_url}
                        method="delete"
                        as="button"
                        preserveScroll
                        className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                    >
                        Убрать
                    </Link>
                </div>
            </div>
        </article>
    );
}

function FavoriteGrid({ title, text, items, emptyTitle, emptyText, kind }) {
    return (
        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div>
                <h2 className="text-2xl font-semibold text-slate-950 dark:text-white">{title}</h2>
                <p className="mt-2 text-sm text-slate-600 dark:text-slate-300">{text}</p>
            </div>

            {items.length > 0 ? (
                <div className="mt-6 grid gap-4">
                    {items.map((item) => (
                        <article key={`${kind}-${item.id}`} className="rounded-lg border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-950">
                            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                                <div>
                                    <div className="flex flex-wrap items-center gap-2">
                                        {item.category && <Badge tone="slate">{item.category}</Badge>}
                                        <Badge>{item.task_count} заданий</Badge>
                                    </div>
                                    <h3 className="mt-3 text-xl font-semibold text-slate-950 dark:text-white">{item.name}</h3>
                                </div>
                                <div className="flex shrink-0 flex-wrap gap-2">
                                    <Link href={item.tasks_url} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200">
                                        Открыть задания
                                    </Link>
                                    <Link
                                        href={item.favorite_url}
                                        method="delete"
                                        as="button"
                                        preserveScroll
                                        className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                                    >
                                        Убрать
                                    </Link>
                                </div>
                            </div>
                        </article>
                    ))}
                </div>
            ) : (
                <EmptyState title={emptyTitle} text={emptyText} href="/tasks" action="Настроить направления" />
            )}
        </section>
    );
}

function EmptyState({ title, text, href, action }) {
    return (
        <div className="mt-6 rounded-lg border border-dashed border-slate-300 bg-slate-50 p-8 text-center dark:border-slate-700 dark:bg-slate-950">
            <h3 className="text-xl font-semibold text-slate-950 dark:text-white">{title}</h3>
            <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{text}</p>
            <Link href={href} className="mt-5 inline-flex rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                {action}
            </Link>
        </div>
    );
}

function Badge({ tone = 'blue', children }) {
    const classes = {
        blue: 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200',
        green: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
        slate: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    };

    return <span className={`rounded-md px-3 py-1 text-xs font-semibold ${classes[tone]}`}>{children}</span>;
}
