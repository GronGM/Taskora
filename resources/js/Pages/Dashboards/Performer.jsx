import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const cards = [
    { title: 'Мои услуги', href: '/performer/services' },
    { title: 'Доступные задания' },
    { title: 'Мои отклики' },
    { title: 'Мои заказы' },
];

export default function Performer() {
    return (
        <DashboardLayout>
            <Head title="Кабинет исполнителя" />
            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <p className="text-sm font-semibold uppercase text-blue-700">Рабочая область</p>
                <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Кабинет исполнителя</h1>
                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {cards.map((card) => (
                        <article key={card.title} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">{card.title}</h2>
                            <p className="mt-3 text-sm leading-6 text-slate-600">Раздел будет реализован на следующих этапах MVP.</p>
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
