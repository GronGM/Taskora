import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import ServiceCard from '../../Components/ServiceCard';

const focusClass = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950';

export default function CatalogIndex({ categories, services, filters, activeCategory }) {
    return (
        <PublicLayout>
            <Head title="Каталог услуг" />

            <section className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Каталог</p>
                    <div className="mt-3 flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                        <div>
                            <h1 className="text-4xl font-semibold tracking-normal text-slate-950 dark:text-white">Каталог услуг</h1>
                            <p className="mt-4 max-w-2xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                                Выбирайте опубликованные услуги исполнителей Таскоры по категориям, срокам и цене.
                            </p>
                        </div>
                        <form action="/catalog" method="get" className="flex w-full gap-2 lg:max-w-md">
                            {filters.category && <input type="hidden" name="category" value={filters.category} />}
                            <input
                                type="search"
                                name="search"
                                defaultValue={filters.search}
                                placeholder="Поиск по услугам"
                                className="min-w-0 flex-1 rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:placeholder:text-slate-400 dark:focus:border-blue-400 dark:focus:ring-blue-950"
                            />
                            <button
                                type="submit"
                                className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                Найти
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <section className="mx-auto grid max-w-7xl gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[280px_1fr] lg:px-8">
                <aside>
                    <div className="sticky top-4 space-y-3">
                        <Link
                            href="/catalog"
                            className={`block rounded-md border px-4 py-3 text-sm font-semibold ${
                                !activeCategory
                                    ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-200'
                                    : 'border-slate-200 bg-white text-slate-700 hover:border-blue-200 hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-slate-800 dark:hover:text-white'
                            } ${focusClass}`}
                        >
                            Все категории
                        </Link>
                        {categories.map((category) => (
                            <div key={category.slug} className="rounded-lg border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                                <Link
                                    href={category.url}
                                    className={`block rounded-md px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-slate-50 hover:text-blue-700 dark:text-slate-100 dark:hover:bg-slate-800 dark:hover:text-white ${focusClass}`}
                                >
                                    {category.name}
                                </Link>
                                {category.children.length > 0 && (
                                    <div className="mt-1 space-y-1">
                                        {category.children.map((child) => (
                                            <Link
                                                key={child.slug}
                                                href={`/catalog?category=${child.slug}`}
                                                className={`block rounded-md px-3 py-2 text-sm ${
                                                    activeCategory?.slug === child.slug
                                                        ? 'bg-blue-50 font-semibold text-blue-700 dark:bg-blue-950 dark:text-blue-200'
                                                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white'
                                                } ${focusClass}`}
                                            >
                                                {child.name}
                                            </Link>
                                        ))}
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                </aside>

                <div>
                    {activeCategory && (
                        <div className="mb-6 rounded-lg border border-blue-100 bg-blue-50 p-5 dark:border-blue-800 dark:bg-blue-950/70">
                            <p className="text-sm font-semibold text-blue-700">Фильтр</p>
                            <h2 className="mt-1 text-2xl font-semibold text-slate-950 dark:text-white">{activeCategory.name}</h2>
                            <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{activeCategory.description}</p>
                        </div>
                    )}

                    {services.length > 0 ? (
                        <div className="grid gap-4 xl:grid-cols-2">
                            {services.map((service) => (
                                <ServiceCard key={service.id} service={service} />
                            ))}
                        </div>
                    ) : (
                        <EmptyCatalog />
                    )}
                </div>
            </section>
        </PublicLayout>
    );
}

function EmptyCatalog() {
    return (
        <div className="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
            <h2 className="text-2xl font-semibold text-slate-950 dark:text-white">Услуги не найдены</h2>
            <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Попробуйте выбрать другую категорию или изменить поисковый запрос.
            </p>
            <Link
                href="/catalog"
                className="mt-6 inline-flex rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950"
            >
                Сбросить фильтры
            </Link>
        </div>
    );
}
