import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

export default function Show({ profile, reviews, statusLabels }) {
    const rejectForm = useForm({ reason: '' });

    const reject = (event) => {
        event.preventDefault();
        rejectForm.post(profile.reject_url);
    };

    return (
        <DashboardLayout>
            <Head title={`Проверка профиля: ${profile.display_name}`} />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8 flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Проверка профиля</p>
                        <h1 className="mt-2 max-w-4xl text-4xl font-semibold tracking-normal text-slate-950">{profile.display_name}</h1>
                        <p className="mt-4 text-sm text-slate-600">
                            Статус: <span className="font-semibold text-slate-950">{statusLabels[profile.verification_status]}</span>
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link href={profile.public_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            Публичный вид
                        </Link>
                        <Link href="/moderator/performer-profiles" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            К очереди
                        </Link>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-[1fr_360px]">
                    <div className="space-y-6">
                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-2xl font-semibold text-slate-950">Публичные данные</h2>
                            {profile.cover_url && <img src={profile.cover_url} alt="" className="mt-5 h-40 w-full rounded-md object-cover" />}
                            <div className="mt-5 grid gap-5 sm:grid-cols-[96px_1fr]">
                                <div className="h-24 w-24 rounded-md bg-slate-100">
                                    {profile.avatar_url && <img src={profile.avatar_url} alt="" className="h-full w-full rounded-md object-cover" />}
                                </div>
                                <div>
                                    <h3 className="text-xl font-semibold text-slate-950">{profile.headline || 'Заголовок не заполнен'}</h3>
                                    <p className="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{profile.bio || 'Описание не заполнено'}</p>
                                </div>
                            </div>
                            {profile.portfolio_summary && <p className="mt-5 text-sm leading-6 text-slate-600">{profile.portfolio_summary}</p>}
                            <div className="mt-5 flex flex-wrap gap-2">
                                {profile.specializations.map((category) => (
                                    <span key={category.id} className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{category.name}</span>
                                ))}
                            </div>
                        </section>

                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-2xl font-semibold text-slate-950">Портфолио</h2>
                            {profile.portfolio.length > 0 ? (
                                <div className="mt-5 grid gap-4 md:grid-cols-2">
                                    {profile.portfolio.map((item) => (
                                        <article key={item.id} className="rounded-lg border border-slate-200 bg-slate-50 p-4">
                                            <div className="flex flex-wrap gap-2">
                                                <span className="rounded-md bg-white px-2 py-1 text-xs font-semibold text-slate-700">{item.status}</span>
                                                {item.category && <span className="rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700">{item.category}</span>}
                                            </div>
                                            <h3 className="mt-3 text-lg font-semibold text-slate-950">{item.title}</h3>
                                            {item.description && <p className="mt-2 text-sm leading-6 text-slate-600">{item.description}</p>}
                                            {item.external_url && <a href={item.external_url} className="mt-3 block break-all text-sm font-semibold text-blue-700">{item.external_url}</a>}
                                        </article>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-3 text-sm text-slate-600">Работы портфолио не добавлены.</p>
                            )}
                        </section>

                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-2xl font-semibold text-slate-950">Услуги и отзывы</h2>
                            {profile.services.length > 0 ? (
                                <div className="mt-5 grid gap-3">
                                    {profile.services.map((service) => (
                                        <article key={service.id} className="rounded-md border border-slate-200 bg-slate-50 p-4">
                                            <h3 className="font-semibold text-slate-950">{service.title}</h3>
                                            <p className="mt-2 text-sm text-slate-600">
                                                {service.category}, от {currency.format(service.price_from)} ₽, {service.delivery_days} дн.
                                            </p>
                                        </article>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-3 text-sm text-slate-600">Опубликованных услуг нет.</p>
                            )}

                            {reviews.length > 0 ? (
                                <div className="mt-6 grid gap-3">
                                    {reviews.map((review) => (
                                        <article key={review.id} className="rounded-md border border-slate-200 bg-white p-4">
                                            <p className="text-sm font-semibold text-blue-700">{review.rating} / 5</p>
                                            <p className="mt-2 text-sm leading-6 text-slate-600">{review.comment || 'Без текста отзыва'}</p>
                                        </article>
                                    ))}
                                </div>
                            ) : (
                                <p className="mt-6 text-sm text-slate-600">Публичных отзывов нет.</p>
                            )}
                        </section>
                    </div>

                    <aside className="space-y-6">
                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-xl font-semibold text-slate-950">Исполнитель</h2>
                            <dl className="mt-4 space-y-3 text-sm">
                                <div>
                                    <dt className="text-slate-500">Аккаунт</dt>
                                    <dd className="font-semibold text-slate-950">{profile.performer.name}</dd>
                                </div>
                                <div>
                                    <dt className="text-slate-500">Email для служебной проверки</dt>
                                    <dd className="break-all font-semibold text-slate-950">{profile.performer.email}</dd>
                                </div>
                                <div>
                                    <dt className="text-slate-500">Заказы</dt>
                                    <dd className="font-semibold text-slate-950">{profile.performer.completed_orders_count}</dd>
                                </div>
                                <div>
                                    <dt className="text-slate-500">Отзывы</dt>
                                    <dd className="font-semibold text-slate-950">{profile.performer.reviews_count}</dd>
                                </div>
                            </dl>
                        </section>

                        {profile.verification_status === 'pending_review' && (
                            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <h2 className="text-xl font-semibold text-slate-950">Решение</h2>
                                <Link
                                    href={profile.approve_url}
                                    method="post"
                                    as="button"
                                    className="mt-5 w-full rounded-md bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700"
                                >
                                    Подтвердить профиль
                                </Link>
                                <form onSubmit={reject} className="mt-6 border-t border-slate-200 pt-6">
                                    <label htmlFor="reason" className="text-sm font-semibold text-slate-900">Отклонить с причиной</label>
                                    <textarea
                                        id="reason"
                                        value={rejectForm.data.reason}
                                        onChange={(event) => rejectForm.setData('reason', event.target.value)}
                                        rows={5}
                                        className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                    {rejectForm.errors.reason && <p className="mt-2 text-sm text-red-600">{rejectForm.errors.reason}</p>}
                                    <button
                                        type="submit"
                                        disabled={rejectForm.processing}
                                        className="mt-3 w-full rounded-md border border-red-200 bg-red-50 px-5 py-3 text-sm font-semibold text-red-700 hover:bg-red-100 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        Отклонить
                                    </button>
                                </form>
                            </section>
                        )}
                    </aside>
                </div>
            </section>
        </DashboardLayout>
    );
}
