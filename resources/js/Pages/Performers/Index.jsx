import { Head, Link } from '@inertiajs/react';
import Pagination from '../../Components/Pagination';
import PublicLayout from '../../Layouts/PublicLayout';

const activeCategoryClass = 'bg-blue-600 text-white';
const categoryClass = 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50';

export default function PerformersIndex({ performers, categories = [], filters = {}, pagination = null }) {
    return (
        <PublicLayout>
            <Head title="Исполнители" />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Исполнители</p>
                    <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Исполнители Таскоры</h1>
                    <p className="mt-5 max-w-3xl text-lg leading-8 text-slate-600">
                        Публичные профили исполнителей с услугами, специализациями, отзывами, портфолио и ручной проверкой качества оформления.
                    </p>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-6 flex gap-2 overflow-x-auto pb-1">
                    <Link
                        href="/performers"
                        className={`shrink-0 rounded-md px-4 py-2 text-sm font-semibold ${filters.category ? categoryClass : activeCategoryClass}`}
                    >
                        Все
                    </Link>
                    {categories.map((category) => (
                        <Link
                            key={category.id}
                            href={`/performers?category=${category.slug}`}
                            className={`shrink-0 rounded-md px-4 py-2 text-sm font-semibold ${
                                filters.category === category.slug ? activeCategoryClass : categoryClass
                            }`}
                        >
                            {category.name}
                        </Link>
                    ))}
                </div>

                {performers.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {performers.map((performer) => (
                            <article key={performer.id} className="flex h-full flex-col rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="flex items-start gap-4">
                                    <div className="grid h-14 w-14 shrink-0 place-items-center rounded-md bg-blue-50 text-xl font-semibold text-blue-700">
                                        {performer.avatar_url ? <img src={performer.avatar_url} alt="" className="h-full w-full rounded-md object-cover" /> : performer.name.slice(0, 1)}
                                    </div>
                                    <div className="min-w-0">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <p className="text-sm font-semibold uppercase text-blue-700">{performer.role}</p>
                                            {performer.level_label && (
                                                <span className="rounded-md bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700">{performer.level_label}</span>
                                            )}
                                            {performer.is_verified && (
                                                <span className="rounded-md bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700">Проверен</span>
                                            )}
                                        </div>
                                        <h2 className="mt-2 text-2xl font-semibold text-slate-950">{performer.name}</h2>
                                        <p className="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{performer.headline || 'Исполнитель Таскоры'}</p>
                                    </div>
                                </div>

                                {performer.specializations.length > 0 && (
                                    <div className="mt-5 flex flex-wrap gap-2">
                                        {performer.specializations.slice(0, 5).map((category) => (
                                            <Link key={category.id} href={category.url} className="rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                                {category.name}
                                            </Link>
                                        ))}
                                    </div>
                                )}

                                <div className="mt-6 grid grid-cols-3 gap-3 text-sm">
                                    <div className="rounded-md bg-slate-50 p-3">
                                        <p className="text-slate-500">Услуги</p>
                                        <p className="mt-1 text-xl font-semibold text-slate-950">{performer.services_count}</p>
                                    </div>
                                    <div className="rounded-md bg-slate-50 p-3">
                                        <p className="text-slate-500">Отзывы</p>
                                        <p className="mt-1 text-xl font-semibold text-slate-950">{performer.reviews_count}</p>
                                    </div>
                                    <div className="rounded-md bg-slate-50 p-3">
                                        <p className="text-slate-500">Заказы</p>
                                        <p className="mt-1 text-xl font-semibold text-slate-950">{performer.completed_orders_count}</p>
                                    </div>
                                </div>

                                <Link
                                    href={performer.profile_url}
                                    className="mt-6 inline-flex w-full justify-center rounded-md bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
                                >
                                    Открыть профиль
                                </Link>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Исполнители пока не опубликованы</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Витрина заполнится после появления опубликованных услуг или публичных профилей исполнителей.
                        </p>
                    </div>
                )}

                <Pagination pagination={pagination} label="Пагинация исполнителей" />
            </section>
        </PublicLayout>
    );
}
