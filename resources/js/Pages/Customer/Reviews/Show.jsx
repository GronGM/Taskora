import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

export default function Show({ review }) {
    return (
        <DashboardLayout>
            <Head title="Отзыв по заказу" />

            <section className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Отзыв опубликован</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{review.order.title}</h1>
                        <p className="mt-3 text-sm text-slate-500">Исполнитель: {review.performer.name}</p>
                    </div>
                    <Link href="/customer/reviews" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        Все отзывы
                    </Link>
                </div>

                <article className="mt-8 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <span className="rounded-md bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700">{review.rating} / 5</span>
                        <span className="text-sm text-slate-500">{review.published_at}</span>
                    </div>
                    <p className="mt-5 whitespace-pre-line text-base leading-8 text-slate-700">
                        {review.comment || 'Комментарий не добавлен.'}
                    </p>
                    <div className="mt-6 grid gap-3 border-t border-slate-100 pt-5 text-sm sm:grid-cols-2">
                        <Info label="Источник" value={review.source.title} />
                        <Info label="Публичность" value={review.is_public ? 'Показывается публично' : 'Скрыт'} />
                    </div>
                    <div className="mt-6 flex flex-wrap gap-3">
                        {review.order.show_url && (
                            <Link href={review.order.show_url} className="rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                                К заказу
                            </Link>
                        )}
                        {review.performer.reviews_url && (
                            <Link href={review.performer.reviews_url} className="rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                Публичные отзывы исполнителя
                            </Link>
                        )}
                    </div>
                </article>
            </section>
        </DashboardLayout>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-lg bg-slate-50 p-4">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-2 font-semibold text-slate-950">{value || 'Не указано'}</p>
        </div>
    );
}
