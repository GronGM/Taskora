import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import ServiceCard from '../../Components/ServiceCard';

export default function Category({ category, children, services }) {
    return (
        <PublicLayout>
            <Head title={category.name} />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <Link href="/catalog" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                        Каталог услуг
                    </Link>
                    <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">{category.name}</h1>
                    <p className="mt-5 max-w-3xl text-lg leading-8 text-slate-600">{category.description}</p>

                    {children.length > 0 && (
                        <div className="mt-8 flex flex-wrap gap-2">
                            {children.map((child) => (
                                <Link
                                    key={child.slug}
                                    href={`/catalog?category=${child.slug}`}
                                    className="rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700"
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
                    <div className="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">В этой категории пока нет услуг</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Категория уже готова для MVP, услуги появятся после следующего этапа кабинета исполнителя.
                        </p>
                        <Link
                            href="/catalog"
                            className="mt-6 inline-flex rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Смотреть весь каталог
                        </Link>
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}
