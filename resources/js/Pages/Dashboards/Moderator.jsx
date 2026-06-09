import { Head } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const cards = ['Флаги модерации', 'Услуги на проверке', 'Задания на проверке', 'Споры'];

export default function Moderator() {
    return (
        <DashboardLayout>
            <Head title="Панель модератора" />
            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <p className="text-sm font-semibold uppercase text-blue-700">Модерация</p>
                <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Панель модератора</h1>
                <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {cards.map((card) => (
                        <article key={card} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">{card}</h2>
                            <p className="mt-3 text-sm leading-6 text-slate-600">Раздел будет реализован на следующих этапах MVP.</p>
                        </article>
                    ))}
                </div>
            </section>
        </DashboardLayout>
    );
}
