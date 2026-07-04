import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const cards = [
    {
        title: 'Профили исполнителей',
        description: 'Ручная проверка публичных профилей, специализаций и портфолио без KYC.',
        href: '/moderator/performer-profiles',
    },
    {
        title: 'Услуги на проверке',
        description: 'Проверка описаний, пакетов и публикация готовых услуг.',
        href: '/moderator/services',
    },
    {
        title: 'Флаги модерации',
        description: 'Срабатывания ContactGuard и обработка открытых флагов.',
        href: '/moderator/moderation-flags',
    },
    {
        title: 'Споры',
        description: 'Арбитраж заказов, переписка сторон и решения по удержанной оплате.',
        href: '/moderator/disputes',
    },
];

export default function Moderator() {
    return (
        <DashboardLayout>
            <Head title="Панель модератора" />
            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <p className="text-sm font-semibold uppercase text-blue-700">Модерация</p>
                <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Панель модератора</h1>
                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {cards.map((card) => (
                        <article key={card.title} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">{card.title}</h2>
                            <p className="mt-3 text-sm leading-6 text-slate-600">{card.description}</p>
                            <Link
                                href={card.href}
                                className="mt-5 inline-flex rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                            >
                                Открыть
                            </Link>
                        </article>
                    ))}
                </div>
            </section>
        </DashboardLayout>
    );
}
