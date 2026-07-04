import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function Reviews({ performer, reviews = [] }) {
    const hasReviews = performer.reviews_count > 0;

    return (
        <PublicLayout>
            <Head title={`Отзывы: ${performer.name}`} />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <Link href="/performers" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                        Исполнители
                    </Link>
                    <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{performer.name}</h1>
                    <p className="mt-4 max-w-3xl text-lg leading-8 text-slate-600">
                        Публичные отзывы по завершенным заказам внутри Таскоры.
                    </p>
                    <div className="mt-8 grid gap-3 sm:grid-cols-4">
                        <Metric label="Рейтинг" value={hasReviews ? `${Number(performer.rating).toFixed(2)} / 5` : 'Нет отзывов'} />
                        <Metric label="Отзывы" value={performer.reviews_count} />
                        <Metric label="Завершено" value={performer.completed_orders_count} />
                        <Metric label="Услуги" value={performer.services_count} />
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                {reviews.length > 0 ? (
                    <div className="space-y-4">
                        {reviews.map((review) => (
                            <article key={review.id} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="flex flex-col justify-between gap-3 sm:flex-row sm:items-start">
                                    <div>
                                        <p className="text-sm font-semibold text-blue-700">{review.rating} / 5</p>
                                        <h2 className="mt-2 text-xl font-semibold text-slate-950">{review.source.title}</h2>
                                        <p className="mt-1 text-sm text-slate-500">Заказчик: {review.customer.name}</p>
                                    </div>
                                    <span className="text-sm text-slate-500">{review.published_at}</span>
                                </div>
                                {review.comment && <p className="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{review.comment}</p>}
                                {review.source.url && (
                                    <Link href={review.source.url} className="mt-5 inline-flex rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                        Открыть услугу
                                    </Link>
                                )}
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Публичных отзывов пока нет</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Здесь появятся отзывы заказчиков после завершенных заказов.
                        </p>
                    </div>
                )}
            </section>
        </PublicLayout>
    );
}

function Metric({ label, value }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50 p-4">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-2 text-xl font-semibold text-slate-950">{value}</p>
        </div>
    );
}
