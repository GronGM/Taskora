import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

export default function Index({ reviews = [] }) {
    return (
        <DashboardLayout>
            <Head title="Мои отзывы" />

            <section className="mx-auto max-w-6xl px-4 py-10 sm:px-6 lg:px-8">
                <div>
                    <p className="text-sm font-semibold uppercase text-blue-700">Отзывы</p>
                    <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Мои отзывы</h1>
                    <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                        Здесь собраны отзывы, которые вы оставили исполнителям после завершенных заказов.
                    </p>
                </div>

                {reviews.length > 0 ? (
                    <div className="mt-8 space-y-4">
                        {reviews.map((review) => (
                            <article key={review.id} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                                    <div>
                                        <p className="text-sm font-semibold text-blue-700">{review.rating} / 5</p>
                                        <h2 className="mt-2 text-xl font-semibold text-slate-950">{review.order.title}</h2>
                                        <p className="mt-1 text-sm text-slate-500">Исполнитель: {review.performer.name}</p>
                                    </div>
                                    <span className="text-sm text-slate-500">{review.published_at}</span>
                                </div>
                                {review.comment && <p className="mt-4 whitespace-pre-line text-sm leading-6 text-slate-700">{review.comment}</p>}
                                <div className="mt-5 flex flex-wrap gap-3">
                                    <Link href={review.show_url} className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                        Открыть отзыв
                                    </Link>
                                    {review.order.show_url && (
                                        <Link href={review.order.show_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                            К заказу
                                        </Link>
                                    )}
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Отзывов пока нет</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Кнопка появится в завершенном заказе после разблокировки оплаты исполнителю.
                        </p>
                        <Link href="/customer/orders" className="mt-6 inline-flex rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                            Перейти к заказам
                        </Link>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}
