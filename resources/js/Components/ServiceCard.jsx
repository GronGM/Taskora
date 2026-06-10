import { Link } from '@inertiajs/react';

const currency = new Intl.NumberFormat('ru-RU');

const pluralReviews = (count) => {
    const mod10 = count % 10;
    const mod100 = count % 100;

    if (mod10 === 1 && mod100 !== 11) {
        return 'отзыв';
    }

    if ([2, 3, 4].includes(mod10) && ![12, 13, 14].includes(mod100)) {
        return 'отзыва';
    }

    return 'отзывов';
};

export default function ServiceCard({ service }) {
    const hasReviews = service.reviews_count > 0;

    return (
        <article className="flex h-full flex-col rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div className="flex items-start justify-between gap-4">
                <Link
                    href={service.category.url}
                    className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100"
                >
                    {service.category.name}
                </Link>
                <span className="text-sm font-semibold text-slate-600">
                    {hasReviews ? `${Number(service.rating).toFixed(2)} / 5` : 'Нет отзывов'}
                </span>
            </div>

            <h3 className="mt-4 text-xl font-semibold leading-7 text-slate-950">
                <Link href={service.url} className="hover:text-blue-700">
                    {service.title}
                </Link>
            </h3>

            <p className="mt-3 line-clamp-3 text-sm leading-6 text-slate-600">{service.short_description}</p>

            <div className="mt-6 grid gap-3 border-t border-slate-100 pt-5 text-sm text-slate-600 sm:grid-cols-2">
                <div>
                    <p className="text-xs font-medium uppercase text-slate-400">Цена от</p>
                    <p className="mt-1 text-base font-semibold text-slate-950">{currency.format(service.price_from)} ₽</p>
                </div>
                <div>
                    <p className="text-xs font-medium uppercase text-slate-400">Срок</p>
                    <p className="mt-1 text-base font-semibold text-slate-950">{service.delivery_days} дн.</p>
                </div>
            </div>

            <div className="mt-5 flex items-center justify-between gap-4 text-sm">
                <div>
                    <p className="text-slate-500">Исполнитель</p>
                    <Link href={service.performer.reviews_url} className="font-semibold text-slate-900 hover:text-blue-700">
                        {service.performer.name}
                    </Link>
                </div>
                <div className="text-right">
                    <p className="text-slate-500">{hasReviews ? `${service.reviews_count} ${pluralReviews(service.reviews_count)}` : 'Нет отзывов'}</p>
                    <p className="mt-1 text-xs text-slate-400">{service.orders_count} завершено</p>
                </div>
            </div>

            <Link
                href={service.url}
                className="mt-6 inline-flex justify-center rounded-md bg-slate-950 px-4 py-2.5 text-sm font-semibold text-white hover:bg-slate-800"
            >
                Подробнее
            </Link>
        </article>
    );
}
