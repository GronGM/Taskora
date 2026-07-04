import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import Pagination from '../../Components/Pagination';
import ServiceCard from '../../Components/ServiceCard';

const categoryLinkClass = 'rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-blue-700 dark:hover:bg-slate-800 dark:hover:text-white dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950';

export default function Category({ category, children, services, pagination = null }) {
    return (
        <PublicLayout>
            <Head title={category.name} />

            <section className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <Link href="/catalog" className="text-sm font-semibold text-blue-700 hover:text-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:text-blue-300 dark:hover:text-blue-200 dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950">
                        Каталог услуг
                    </Link>
                    <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950 dark:text-white">{category.name}</h1>
                    <p className="mt-5 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">{category.description}</p>

                    {children.length > 0 && (
                        <div className="mt-8 flex flex-wrap gap-2">
                            {children.map((child) => (
                                <Link
                                    key={child.slug}
                                    href={`/catalog?category=${child.slug}`}
                                    className={categoryLinkClass}
                                >
                                    {child.name}
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                {services.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                        {services.map((service) => (
                            <ServiceCard key={service.id} service={service} />
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
                        <h2 className="text-2xl font-semibold text-slate-950 dark:text-white">В этой категории пока нет услуг</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Категория уже готова для MVP, услуги появятся после следующего этапа кабинета исполнителя.
                        </p>
                        <Link
                            href="/catalog"
                            className="mt-6 inline-flex rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950"
                        >
                            Смотреть весь каталог
                        </Link>
                    </div>
                )}

                <Pagination pagination={pagination} label="Пагинация категории" />
            </section>
        </PublicLayout>
    );
}
