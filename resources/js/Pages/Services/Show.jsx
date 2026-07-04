import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import ServiceCard from '../../Components/ServiceCard';
import PublicLayout from '../../Layouts/PublicLayout';

const currency = new Intl.NumberFormat('ru-RU');

const reviewHoldOptions = [5, 10, 20, 30, 40];

export default function Show({ service, similarServices = [] }) {
    const hasReviews = service.reviews_count > 0;
    const maxReviewHold = service.max_review_hold_days ?? 40;
    const availableReviewHolds = reviewHoldOptions.filter((days) => days <= maxReviewHold);
    const [reviewHoldDays, setReviewHoldDays] = useState(availableReviewHolds.includes(10) ? 10 : availableReviewHolds[0]);

    return (
        <PublicLayout>
            <Head title={service.title} />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
                    <div>
                        {service.cover_url && (
                            <img src={service.cover_url} alt="" className="mb-6 aspect-[2/1] w-full rounded-lg object-cover shadow-sm" />
                        )}
                        <Link href={service.category.url} className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                            {service.category.name}
                        </Link>
                        <h1 className="mt-3 text-4xl font-semibold leading-tight tracking-tight text-slate-950">
                            {service.title}
                        </h1>
                        <p className="mt-5 max-w-3xl text-lg leading-8 text-slate-600">{service.short_description}</p>
                    </div>

                    <aside className="rounded-lg border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <p className="text-sm text-slate-500">Цена от</p>
                        <p className="mt-1 text-3xl font-semibold text-slate-950">{currency.format(service.price_from)} ₽</p>
                        <div className="mt-5 grid grid-cols-2 gap-3 text-sm">
                            <div className="rounded-md bg-white p-3">
                                <p className="text-slate-500">Срок</p>
                                <p className="mt-1 font-semibold text-slate-950">{service.delivery_days} дн.</p>
                            </div>
                            <div className="rounded-md bg-white p-3">
                                <p className="text-slate-500">Отзывы</p>
                                <p className="mt-1 font-semibold text-slate-950">{hasReviews ? service.reviews_count : 'Нет'}</p>
                            </div>
                        </div>
                        {service.packages.length === 0 ? (
                            <Link
                                href={service.order_url}
                                method="post"
                                as="button"
                                className="mt-6 w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                Заказать услугу
                            </Link>
                        ) : (
                            <a
                                href="#service-packages"
                                className="mt-6 inline-flex w-full justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                            >
                                Выбрать пакет
                            </a>
                        )}
                        <p className="mt-3 text-xs leading-5 text-slate-500">
                            Оплата пока работает как локальная заглушка без реального платежного шлюза.
                        </p>
                    </aside>
                </div>
            </section>

            <section className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[1fr_320px] lg:px-8">
                <div className="space-y-8">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Описание</p>
                        <p className="mt-4 whitespace-pre-line text-base leading-8 text-slate-700">{service.description}</p>
                    </div>

                    <div id="service-packages">
                        <p className="text-sm font-semibold uppercase text-blue-700">Пакеты услуги</p>

                        <div className="mt-4 max-w-md">
                            <label htmlFor="review_hold_days" className="text-sm font-semibold text-slate-900 dark:text-slate-200">Срок проверки работы</label>
                            <select
                                id="review_hold_days"
                                value={reviewHoldDays}
                                onChange={(event) => setReviewHoldDays(Number(event.target.value))}
                                className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            >
                                {availableReviewHolds.map((days) => (
                                    <option key={days} value={days}>{days} дней</option>
                                ))}
                            </select>
                            <p className="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                Столько дней после сдачи работы деньги остаются замороженными: можно проверить результат, запросить доработку или открыть спор. Приняли раньше — исполнитель получит оплату сразу.
                            </p>
                        </div>

                        <div className="mt-5 grid gap-4 lg:grid-cols-3">
                            {service.packages.map((pack) => (
                                <article key={pack.id} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                    <h2 className="text-lg font-semibold text-slate-950">{pack.name}</h2>
                                    <p className="mt-3 text-sm leading-6 text-slate-600">{pack.description}</p>
                                    <p className="mt-5 text-2xl font-semibold text-slate-950">{currency.format(pack.price)} ₽</p>
                                    <div className="mt-4 space-y-2 text-sm text-slate-600">
                                        <p>Срок: {pack.delivery_days} дн.</p>
                                        <p>Правки: {pack.revisions_count}</p>
                                    </div>
                                    <Link
                                        href={service.order_url}
                                        method="post"
                                        data={{ package_id: pack.id, review_hold_days: reviewHoldDays }}
                                        as="button"
                                        className="mt-5 w-full rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700"
                                    >
                                        Заказать пакет
                                    </Link>
                                </article>
                            ))}
                        </div>
                    </div>

                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Отзывы заказчиков</p>
                        {service.reviews.length > 0 ? (
                            <div className="mt-5 space-y-4">
                                {service.reviews.map((review) => (
                                    <article key={review.id} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                        <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                            <div>
                                                <p className="text-sm font-semibold text-blue-700">{review.rating} / 5</p>
                                                <p className="mt-1 text-sm font-semibold text-slate-950">{review.customer.name}</p>
                                            </div>
                                            <span className="text-sm text-slate-500">{review.published_at}</span>
                                        </div>
                                        {review.comment && <p className="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{review.comment}</p>}
                                    </article>
                                ))}
                            </div>
                        ) : (
                            <div className="mt-5 rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
                                <h2 className="text-xl font-semibold text-slate-950">Отзывов пока нет</h2>
                                <p className="mt-2 text-sm leading-6 text-slate-600">
                                    Первый отзыв появится после завершенного заказа и публикации оценки заказчиком.
                                </p>
                            </div>
                        )}
                    </div>
                </div>

                <aside className="space-y-4">
                    <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <p className="text-sm font-semibold text-slate-500">Исполнитель</p>
                        <Link href={service.performer.profile_url ?? service.performer.reviews_url} className="mt-2 block text-xl font-semibold text-slate-950 hover:text-blue-700">
                            {service.performer.name}
                        </Link>
                        {service.performer.is_verified && (
                            <span className="mt-3 inline-flex rounded-md bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                Проверен
                            </span>
                        )}
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            {service.performer.reviews_count > 0
                                ? `${service.performer.reviews_count} отзывов, ${service.performer.completed_orders_count} завершенных заказов.`
                                : 'У исполнителя пока нет публичных отзывов.'}
                        </p>
                    </div>
                    <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <p className="text-sm font-semibold uppercase text-blue-700 dark:text-blue-300">Доверие</p>
                        <dl className="mt-4 space-y-3 text-sm">
                            <div className="flex items-baseline justify-between gap-3">
                                <dt className="text-slate-500 dark:text-slate-400">Рейтинг</dt>
                                <dd className="font-semibold text-slate-950 dark:text-slate-100">{hasReviews ? `★ ${Number(service.rating).toFixed(2)}` : 'Нет отзывов'}</dd>
                            </div>
                            <div className="flex items-baseline justify-between gap-3">
                                <dt className="text-slate-500 dark:text-slate-400">Отзывы</dt>
                                <dd className="font-semibold text-slate-950 dark:text-slate-100">{service.reviews_count}</dd>
                            </div>
                            <div className="flex items-baseline justify-between gap-3">
                                <dt className="text-slate-500 dark:text-slate-400">Выполнено заказов</dt>
                                <dd className="font-semibold text-slate-950 dark:text-slate-100">{service.orders_count}</dd>
                            </div>
                        </dl>
                        <p className="mt-4 border-t border-slate-100 pt-3 text-xs leading-5 text-slate-500 dark:border-slate-800 dark:text-slate-400">
                            Обсуждение, файлы и оплата остаются внутри Таскоры — так сделка под защитой.
                        </p>
                    </div>
                </aside>
            </section>

            {similarServices.length > 0 && (
                <section className="border-t border-slate-200 bg-slate-50 dark:border-slate-800 dark:bg-slate-950">
                    <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                        <h2 className="text-2xl font-semibold text-slate-950 dark:text-slate-100">Похожие услуги</h2>
                        <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
                            Другие опубликованные услуги в категории «{service.category.name}».
                        </p>
                        <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            {similarServices.map((similar) => (
                                <ServiceCard key={similar.id} service={similar} />
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </PublicLayout>
    );
}
