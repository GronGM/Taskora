import { Head, Link, useForm } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function TaskShow({ task, canOffer, existingOffer, offerStatusLabels }) {
    const form = useForm({
        message: '',
        price: '',
        delivery_days: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.post(task.offer_url);
    };

    return (
        <PublicLayout>
            <Head title={task.title} />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                    <Link href="/tasks" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                        Биржа заданий
                    </Link>
                    <div className="mt-4 flex flex-col justify-between gap-6 lg:flex-row lg:items-start">
                        <div>
                            <div className="flex flex-wrap items-center gap-2">
                                <span className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{task.category.name}</span>
                                {task.task_type && (
                                    <span className="rounded-md bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{task.task_type.name}</span>
                                )}
                                <span className="rounded-md bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700">{task.offers_count} откл.</span>
                                {task.badges.urgent && <span className="rounded-md bg-red-50 px-3 py-1 text-xs font-semibold text-red-700">Срочно</span>}
                                {task.badges.favorited && <span className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">В избранном</span>}
                            </div>
                            <h1 className="mt-4 max-w-4xl text-4xl font-semibold tracking-normal text-slate-950">{task.title}</h1>
                            <div className="mt-5 grid gap-3 text-sm text-slate-600 sm:grid-cols-4">
                                <p>Бюджет: <span className="font-semibold text-slate-950">{task.budget_label}</span></p>
                                <p>Срок: <span className="font-semibold text-slate-950">{task.deadline_at ?? 'Не указан'}</span></p>
                                <p>Заказчик: <span className="font-semibold text-slate-950">{task.customer.name}</span></p>
                                <p>Просмотры: <span className="font-semibold text-slate-950">{task.views_count}</span></p>
                            </div>
                        </div>
                        <div className="flex flex-col gap-2 sm:flex-row lg:flex-col">
                            {canOffer && (
                                <a href="#offer-form" className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                                    Откликнуться
                                </a>
                            )}
                            {task.favorite.can_favorite && (
                                <Link
                                    href={task.favorite.is_favorited ? task.favorite.destroy_url : task.favorite.store_url}
                                    method={task.favorite.is_favorited ? 'delete' : 'post'}
                                    as="button"
                                    preserveScroll
                                    className="inline-flex justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                                >
                                    {task.favorite.is_favorited ? 'В избранном' : 'В избранное'}
                                </Link>
                            )}
                            {task.favorite.show_login_cta && (
                                <Link href={task.favorite.login_url} className="inline-flex justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                    Войти, чтобы сохранить
                                </Link>
                            )}
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_400px] lg:px-8">
                <article className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="text-2xl font-semibold text-slate-950">Детали задания</h2>
                    <p className="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{task.description}</p>
                </article>

                <aside className="space-y-4">
                    {canOffer ? (
                        <form id="offer-form" onSubmit={submit} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <p className="text-sm font-semibold uppercase text-blue-700">Отклик</p>
                            <h2 className="mt-2 text-2xl font-semibold text-slate-950">Предложить условия</h2>

                            <div className="mt-5 space-y-4">
                                <Field id="message" label="Сообщение заказчику" error={form.errors.message}>
                                    <textarea
                                        id="message"
                                        name="message"
                                        rows={6}
                                        value={form.data.message}
                                        onChange={(event) => form.setData('message', event.target.value)}
                                        className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                </Field>
                                <Field id="price" label="Цена, ₽" error={form.errors.price}>
                                    <input
                                        id="price"
                                        name="price"
                                        type="number"
                                        min="100"
                                        value={form.data.price}
                                        onChange={(event) => form.setData('price', event.target.value)}
                                        className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                </Field>
                                <Field id="delivery_days" label="Срок, дней" error={form.errors.delivery_days}>
                                    <input
                                        id="delivery_days"
                                        name="delivery_days"
                                        type="number"
                                        min="1"
                                        value={form.data.delivery_days}
                                        onChange={(event) => form.setData('delivery_days', event.target.value)}
                                        className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                </Field>
                            </div>

                            <button
                                type="submit"
                                disabled={form.processing}
                                className="mt-5 w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Отправить отклик
                            </button>
                        </form>
                    ) : (
                        <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <p className="text-sm font-semibold uppercase text-blue-700">Отклики</p>
                            <h2 className="mt-2 text-2xl font-semibold text-slate-950">Условия исполнителей</h2>
                            {existingOffer ? (
                                <p className="mt-4 text-sm leading-6 text-slate-600">
                                    Ваш отклик уже создан. Статус: <span className="font-semibold text-slate-950">{offerStatusLabels[existingOffer.status]}</span>.
                                </p>
                            ) : (
                                <p className="mt-4 text-sm leading-6 text-slate-600">
                                    Откликнуться могут только исполнители после входа в аккаунт.
                                </p>
                            )}
                            <Link
                                href={existingOffer ? '/performer/offers' : '/login'}
                                className="mt-5 inline-flex rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                            >
                                {existingOffer ? 'Мои отклики' : 'Войти'}
                            </Link>
                        </div>
                    )}

                    <div className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <p className="text-sm font-semibold uppercase text-blue-700">Параметры</p>
                        <div className="mt-4 space-y-3 text-sm text-slate-600">
                            <p>Бюджет: <span className="font-semibold text-slate-950">{task.budget_label}</span></p>
                            <p>Откликов: <span className="font-semibold text-slate-950">{task.offers_count}</span></p>
                            <p>Категория: <span className="font-semibold text-slate-950">{task.category.name}</span></p>
                            {task.task_type && <p>Вид задания: <span className="font-semibold text-slate-950">{task.task_type.name}</span></p>}
                        </div>
                    </div>
                </aside>
            </section>
        </PublicLayout>
    );
}

function Field({ id, label, error, children }) {
    return (
        <div>
            <label htmlFor={id} className="text-sm font-semibold text-slate-900">{label}</label>
            <div className="mt-2">{children}</div>
            {error && <p className="mt-2 text-sm text-red-600">{error}</p>}
        </div>
    );
}
