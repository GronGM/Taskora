import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const cards = [
    { title: 'Beta-отзывы', href: '/admin/beta-feedback', description: 'Смотреть обращения друзей и первых тестировщиков, менять статусы и фиксировать проблемы MVP.' },
    { title: 'Финансовая сводка', href: '/admin/finance', description: 'Проверить payment operations, escrow и будущие webhook-события.' },
    { title: 'Пользователи', description: 'Раздел будет реализован на следующих этапах MVP.' },
    { title: 'Категории', description: 'Раздел будет реализован на следующих этапах MVP.' },
    { title: 'Настройки платформы', description: 'Раздел будет реализован на следующих этапах MVP.' },
    { title: 'Комиссии', description: 'Раздел будет реализован на следующих этапах MVP.' },
];

export default function Admin() {
    return (
        <DashboardLayout>
            <Head title="Админ-панель" />
            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <p className="text-sm font-semibold uppercase text-blue-700">Управление</p>
                <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Админ-панель</h1>
                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    {cards.map((card) => (
                        <article key={card.title} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">{card.title}</h2>
                            <p className="mt-3 text-sm leading-6 text-slate-600">{card.description}</p>
                            {card.href && (
                                <Link href={card.href} className="mt-5 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800">
                                    Открыть
                                </Link>
                            )}
                        </article>
                    ))}
                </div>
            </section>
        </DashboardLayout>
    );
}
