import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import ServiceCard from '../../Components/ServiceCard';

export default function CatalogIndex({ categories, services, filters, activeCategory }) {
    return (
        <PublicLayout>
            <Head title="Каталог услуг" />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Каталог</p>
                    <div className="mt-3 flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                        <div>
                            <h1 className="text-4xl font-semibold tracking-normal text-slate-950">Каталог услуг</h1>
                            <p className="mt-4 max-w-2xl text-lg leading-8 text-slate-600">
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
                                className="min-w-0 flex-1 rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
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
                                !activeCategory ? 'border-blue-200 bg-blue-50 text-blue-700' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'
                            }`}
                        >
                            Все категории
                        </Link>
                        {categories.map((category) => (
                            <div key={category.slug} className="rounded-lg border border-slate-200 bg-white p-3">
                                <Link
                                    href={category.url}
                                    className="block rounded-md px-3 py-2 text-sm font-semibold text-slate-950 hover:bg-slate-50"
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
                                                        ? 'bg-blue-50 font-semibold text-blue-700'
                                                        : 'text-slate-600 hover:bg-slate-50 hover:text-slate-950'
                                                }`}
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
                        <div className="mb-6 rounded-lg border border-blue-100 bg-blue-50 p-5">
                            <p className="text-sm font-semibold text-blue-700">Фильтр</p>
                            <h2 className="mt-1 text-2xl font-semibold text-slate-950">{activeCategory.name}</h2>
                            <p className="mt-2 text-sm leading-6 text-slate-600">{activeCategory.description}</p>
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
        <div className="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
            <h2 className="text-2xl font-semibold text-slate-950">Услуги не найдены</h2>
            <p className="mt-3 text-sm leading-6 text-slate-600">
                Попробуйте выбрать другую категорию или изменить поисковый запрос.
            </p>
            <Link
                href="/catalog"
                className="mt-6 inline-flex rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800"
            >
                Сбросить фильтры
            </Link>
        </div>
    );
}
