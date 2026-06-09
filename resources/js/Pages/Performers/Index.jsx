import { Head } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function PerformersIndex({ performers }) {
    return (
        <PublicLayout>
            <Head title="Исполнители" />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Исполнители</p>
                    <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Исполнители Таскоры</h1>
                    <p className="mt-5 max-w-3xl text-lg leading-8 text-slate-600">
                        Публичная витрина исполнителей, у которых уже есть опубликованные услуги в каталоге.
                    </p>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                {performers.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {performers.map((performer) => (
                            <article key={performer.id} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <p className="text-sm font-semibold uppercase text-blue-700">{performer.role}</p>
                                        <h2 className="mt-2 text-2xl font-semibold text-slate-950">{performer.name}</h2>
                                    </div>
                                    <span className="rounded-md bg-slate-100 px-3 py-1 text-sm font-semibold text-slate-700">
                                        {performer.rating} / 5
                                    </span>
                                </div>
                                <div className="mt-6 grid grid-cols-2 gap-3 text-sm">
                                    <div className="rounded-md bg-slate-50 p-4">
                                        <p className="text-slate-500">Услуг</p>
                                        <p className="mt-1 text-xl font-semibold text-slate-950">{performer.services_count}</p>
                                    </div>
                                    <div className="rounded-md bg-slate-50 p-4">
                                        <p className="text-slate-500">Рейтинг</p>
                                        <p className="mt-1 text-xl font-semibold text-slate-950">заглушка</p>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Исполнители пока не опубликованы</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Витрина заполнится после появления опубликованных услуг.
                        </p>
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
