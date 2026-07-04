import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';
import ServiceCard from '../../Components/ServiceCard';

const badgeClasses = {
    emerald: 'bg-emerald-50 text-emerald-700',
    blue: 'bg-blue-50 text-blue-700',
    slate: 'bg-slate-100 text-slate-700',
    amber: 'bg-amber-50 text-amber-700',
};

export default function Show({ performer, services, portfolio, reviews }) {
    return (
        <PublicLayout>
            <Head title={performer.name} />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                    <div className="h-44 overflow-hidden rounded-lg bg-slate-100">
                        {performer.cover_url ? (
                            <img src={performer.cover_url} alt="" className="h-full w-full object-cover" />
                        ) : (
                            <div className="h-full bg-[linear-gradient(135deg,#eff6ff,#f8fafc_45%,#ecfdf5)]" />
                        )}
                    </div>

                    <div className="mt-6 grid gap-6 lg:grid-cols-[1fr_320px] lg:items-start">
                        <div className="flex flex-col gap-5 sm:flex-row sm:items-start">
                            <div className="grid h-24 w-24 shrink-0 place-items-center rounded-lg bg-blue-50 text-3xl font-semibold text-blue-700">
                                {performer.avatar_url ? <img src={performer.avatar_url} alt="" className="h-full w-full rounded-lg object-cover" /> : performer.name.slice(0, 1)}
                            </div>
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    {performer.is_verified && <span className="rounded-md bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Проверен</span>}
                                    {performer.specializations.map((category) => (
                                        <Link key={category.id} href={category.url} className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 hover:bg-blue-100">
                                            {category.name}
                                        </Link>
                                    ))}
                                </div>
                                <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{performer.name}</h1>
                                <p className="mt-3 max-w-3xl text-lg leading-8 text-slate-600">
                                    {performer.headline || 'Исполнитель Таскоры'}
                                </p>
                            </div>
                        </div>

                        <aside className="grid grid-cols-3 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 text-center text-sm">
                            <div>
                                <p className="text-slate-500">Рейтинг</p>
                                <p className="mt-1 text-lg font-semibold text-slate-950">{performer.reviews_count > 0 ? Number(performer.rating).toFixed(2) : 'Нет'}</p>
                            </div>
                            <div>
                                <p className="text-slate-500">Отзывы</p>
                                <p className="mt-1 text-lg font-semibold text-slate-950">{performer.reviews_count}</p>
                            </div>
                            <div>
                                <p className="text-slate-500">Заказы</p>
                                <p className="mt-1 text-lg font-semibold text-slate-950">{performer.completed_orders_count}</p>
                            </div>
                        </aside>
                    </div>
                </div>
            </section>

            <section className="mx-auto grid max-w-7xl gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[1fr_320px] lg:px-8">
                <div className="space-y-10">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">О профиле</p>
                        <p className="mt-4 whitespace-pre-line text-base leading-8 text-slate-700">
                            {performer.bio || 'Исполнитель пока не заполнил подробное описание профиля.'}
                        </p>
                    </div>

                    <div>
                        <div className="flex items-center justify-between gap-4">
                            <p className="text-sm font-semibold uppercase text-blue-700">Портфолио</p>
                            {performer.portfolio_summary && <p className="hidden max-w-xl text-sm text-slate-500 md:block">{performer.portfolio_summary}</p>}
                        </div>
                        {portfolio.length > 0 ? (
                            <div className="mt-5 grid gap-4 md:grid-cols-2">
                                {portfolio.map((item) => (
                                    <article key={item.id} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                        <div className="h-36 rounded-md bg-slate-100">
                                            {item.image_url ? (
                                                <img src={item.image_url} alt="" className="h-full w-full rounded-md object-cover" />
                                            ) : (
                                                <div className="grid h-full place-items-center text-sm text-slate-500">Публичная работа</div>
                                            )}
                                        </div>
                                        {item.category && <p className="mt-4 text-xs font-semibold uppercase text-blue-700">{item.category}</p>}
                                        <h2 className="mt-2 text-xl font-semibold text-slate-950">{item.title}</h2>
                                        {item.description && <p className="mt-2 text-sm leading-6 text-slate-600">{item.description}</p>}
                                        <div className="mt-4 flex flex-wrap gap-3 text-sm font-semibold">
                                            {item.file_url && <a href={item.file_url} className="text-blue-700 hover:text-blue-800">Файл</a>}
                                            {item.external_url && <a href={item.external_url} className="text-blue-700 hover:text-blue-800">Ссылка</a>}
                                        </div>
                                    </article>
                                ))}
                            </div>
                        ) : (
                            <div className="mt-5 rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
                                <h2 className="text-xl font-semibold text-slate-950">Портфолио пока пустое</h2>
                                <p className="mt-2 text-sm leading-6 text-slate-600">Опубликованные работы появятся здесь после заполнения кабинета исполнителя.</p>
                            </div>
                        )}
                    </div>

                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Услуги</p>
                        {services.length > 0 ? (
                            <div className="mt-5 grid gap-4 lg:grid-cols-2">
                                {services.map((service) => <ServiceCard key={service.id} service={service} />)}
                            </div>
                        ) : (
                            <div className="mt-5 rounded-lg border border-dashed border-slate-300 bg-white p-8 text-center">
                                <h2 className="text-xl font-semibold text-slate-950">Опубликованных услуг нет</h2>
                                <p className="mt-2 text-sm leading-6 text-slate-600">Исполнитель может работать через задания или добавить услуги позже.</p>
                            </div>
                        )}
                    </div>

                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Последние отзывы</p>
                        {reviews.length > 0 ? (
                            <div className="mt-5 space-y-4">
                                {reviews.map((review) => (
                                    <article key={review.id} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                                        <div className="flex flex-col justify-between gap-3 sm:flex-row">
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
                                <p className="mt-2 text-sm leading-6 text-slate-600">Первые отзывы появятся после завершенных заказов.</p>
                            </div>
                        )}
                    </div>
                </div>

                <aside className="space-y-4">
                    <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-xl font-semibold text-slate-950">Доверие</h2>
                        <div className="mt-4 flex flex-wrap gap-2">
                            {performer.trust_badges.length > 0 ? performer.trust_badges.map((badge) => (
                                <span key={badge.label} className={`rounded-md px-3 py-1 text-xs font-semibold ${badgeClasses[badge.tone] ?? badgeClasses.slate}`}>
                                    {badge.label}
                                </span>
                            )) : (
                                <span className="rounded-md bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">Новый профиль</span>
                            )}
                        </div>
                    </section>

                    <section className="rounded-lg border border-blue-100 bg-blue-50 p-5">
                        <h2 className="text-sm font-semibold uppercase text-blue-700">Безопасность</h2>
                        <p className="mt-3 text-sm leading-6 text-blue-900">
                            Контакты, файлы заказов и договоренности должны оставаться внутри Таскоры. Публичный профиль не раскрывает email исполнителя.
                        </p>
                    </section>
                </aside>
            </section>
        </PublicLayout>
    );
}
