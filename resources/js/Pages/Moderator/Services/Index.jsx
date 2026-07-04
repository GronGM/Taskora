import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

export default function Index({ services }) {
    return (
        <DashboardLayout>
            <Head title="Модерация услуг" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Модерация</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950">Модерация услуг</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Проверяйте услуги со статусом «На модерации» перед публикацией в каталоге.
                        </p>
                    </div>
                    <Link
                        href="/moderator/moderation-flags"
                        className="inline-flex rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                    >
                        Флаги модерации
                    </Link>
                </div>

                {services.length > 0 ? (
                    <div className="mt-8 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div className="grid gap-0 divide-y divide-slate-200">
                            {services.map((service) => (
                                <article key={service.id} className="grid gap-5 p-6 lg:grid-cols-[1fr_auto] lg:items-center">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="rounded-md bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                                На модерации
                                            </span>
                                            <span className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                                {service.category}
                                            </span>
                                        </div>
                                        <h2 className="mt-4 text-2xl font-semibold text-slate-950">{service.title}</h2>
                                        <div className="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2 lg:grid-cols-5">
                                            <p>Исполнитель: <span className="font-semibold text-slate-950">{service.performer}</span></p>
                                            <p>Цена от: <span className="font-semibold text-slate-950">{currency.format(service.price_from)} ₽</span></p>
                                            <p>Срок: <span className="font-semibold text-slate-950">{service.delivery_days} дн.</span></p>
                                            <p>Пакетов: <span className="font-semibold text-slate-950">{service.packages_count}</span></p>
                                            <p>Отправлена: <span className="font-semibold text-slate-950">{service.submitted_at}</span></p>
                                        </div>
                                    </div>
                                    <Link
                                        href={service.review_url}
                                        className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                                    >
                                        Проверить
                                    </Link>
                                </article>
                            ))}
                        </div>
                    </div>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Очередь модерации пуста</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Новые услуги появятся здесь после отправки исполнителем на проверку.
                        </p>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}
