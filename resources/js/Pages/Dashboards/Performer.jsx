import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const cards = [
    { title: 'Профиль исполнителя', href: '/performer/profile', description: 'Заполните публичную витрину, специализации и отправьте профиль на проверку.' },
    { title: 'Портфолио', href: '/performer/portfolio', description: 'Покажите примеры работ и материалы, которые можно публиковать в профиле.' },
    { title: 'Мои заказы', href: '/performer/orders', description: 'Выполняйте заказы и отправляйте работу на проверку.' },
    { title: 'Финансы', href: '/performer/finance', description: 'Следите за удержанными и разблокированными суммами во внутреннем ledger.' },
    { title: 'Мои услуги', href: '/performer/services', description: 'Создавайте услуги и отправляйте их на модерацию.' },
    { title: 'Доступные задания', href: '/tasks', description: 'Ищите опубликованные задания заказчиков.' },
    { title: 'Мои отклики', href: '/performer/offers', description: 'Следите за предложениями по заданиям.' },
];

export default function Performer({ recommendedTasks = null }) {
    const items = recommendedTasks?.items ?? [];
    const hasFavorites = recommendedTasks?.has_favorites === true;

    return (
        <DashboardLayout>
            <Head title="Кабинет исполнителя" />
            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <p className="text-sm font-semibold uppercase text-blue-700">Рабочая область</p>
                <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Кабинет исполнителя</h1>

                {recommendedTasks && (
                    <div className="mt-8 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-semibold text-slate-950 dark:text-slate-100">Подходящие задания</h2>
                                <p className="mt-1 text-sm text-slate-600 dark:text-slate-400">
                                    {hasFavorites
                                        ? 'Свежие задания по вашим избранным направлениям без вашего отклика.'
                                        : 'Свежие задания на бирже. Добавьте избранные направления, чтобы видеть здесь только подходящие.'}
                                </p>
                            </div>
                            <div className="flex flex-wrap gap-3">
                                {!hasFavorites && (
                                    <Link
                                        href={recommendedTasks.favorites_url}
                                        className="inline-flex rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                                    >
                                        Настроить направления
                                    </Link>
                                )}
                                <Link
                                    href={recommendedTasks.board_url}
                                    className="inline-flex rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700"
                                >
                                    Все задания
                                </Link>
                            </div>
                        </div>

                        {items.length > 0 ? (
                            <ul className="mt-5 divide-y divide-slate-200 dark:divide-slate-800">
                                {items.map((task) => (
                                    <li key={task.id} className="py-3">
                                        <div className="flex flex-wrap items-baseline justify-between gap-2">
                                            <Link href={task.url} className="text-sm font-semibold text-slate-950 hover:text-blue-700 dark:text-slate-100 dark:hover:text-blue-300">
                                                {task.title}
                                            </Link>
                                            <span className="text-sm font-semibold text-slate-800 dark:text-slate-200">{task.budget_label}</span>
                                        </div>
                                        <p className="mt-1 text-xs text-slate-500 dark:text-slate-400">
                                            {[task.category, task.task_type, task.deadline_at ? `до ${task.deadline_at}` : null, `откликов: ${task.offers_count}`]
                                                .filter(Boolean)
                                                .join(' · ')}
                                        </p>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="mt-5 rounded-md bg-slate-50 p-4 text-sm text-slate-600 dark:bg-slate-950 dark:text-slate-400">
                                {hasFavorites
                                    ? 'Сейчас нет новых заданий по избранным направлениям. Загляните на биржу — там есть другие заказы.'
                                    : 'На бирже пока нет опубликованных заданий.'}
                            </p>
                        )}
                    </div>
                )}

                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {cards.map((card) => (
                        <article key={card.title} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">{card.title}</h2>
                            <p className="mt-3 text-sm leading-6 text-slate-600">{card.description}</p>
                            <Link href={card.href} className="mt-5 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800">
                                Открыть
                            </Link>
                        </article>
                    ))}
                </div>
            </section>
        </DashboardLayout>
    );
}
