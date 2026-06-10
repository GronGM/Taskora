import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

export default function Create({ order, ratingOptions = [1, 2, 3, 4, 5] }) {
    const form = useForm({
        rating: 5,
        comment: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.post(order.store_url);
    };

    return (
        <DashboardLayout>
            <Head title="Оставить отзыв" />

            <section className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Отзывы</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Оставить отзыв исполнителю</h1>
                        <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                            Отзыв публикуется после завершенного заказа и помогает другим заказчикам оценить опыт работы внутри Таскоры.
                        </p>
                    </div>
                    <Link href={order.show_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        К заказу
                    </Link>
                </div>

                <div className="mt-8 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="rounded-lg bg-slate-50 p-5">
                        <p className="text-xs font-semibold uppercase text-slate-500">{order.source.label}</p>
                        <h2 className="mt-2 text-2xl font-semibold text-slate-950">{order.title}</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Исполнитель: <span className="font-semibold text-slate-900">{order.performer.name}</span>
                        </p>
                        {order.source.title && <p className="mt-1 text-sm text-slate-500">Источник: {order.source.title}</p>}
                    </div>

                    <form onSubmit={submit} className="mt-6 space-y-6">
                        <div>
                            <label className="text-sm font-semibold text-slate-950">Оценка</label>
                            <div className="mt-3 grid grid-cols-5 gap-2">
                                {ratingOptions.map((rating) => (
                                    <button
                                        key={rating}
                                        type="button"
                                        onClick={() => form.setData('rating', rating)}
                                        className={`rounded-md border px-4 py-3 text-center text-sm font-semibold ${
                                            Number(form.data.rating) === rating
                                                ? 'border-blue-600 bg-blue-600 text-white'
                                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                                        }`}
                                    >
                                        {rating}
                                    </button>
                                ))}
                            </div>
                            {form.errors.rating && <p className="mt-2 text-sm text-red-600">{form.errors.rating}</p>}
                        </div>

                        <div>
                            <label htmlFor="comment" className="text-sm font-semibold text-slate-950">
                                Комментарий
                            </label>
                            <textarea
                                id="comment"
                                rows={7}
                                value={form.data.comment}
                                onChange={(event) => form.setData('comment', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Опишите качество результата, коммуникацию и соблюдение сроков"
                            />
                            <div className="mt-2 flex flex-col justify-between gap-2 text-xs text-slate-500 sm:flex-row">
                                <span>До 2000 символов. Контакты и внешние способы оплаты запрещены.</span>
                                <span>{form.data.comment.length}/2000</span>
                            </div>
                            {form.errors.comment && <p className="mt-2 text-sm text-red-600">{form.errors.comment}</p>}
                        </div>

                        <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                            Оставляйте отзыв только по фактическому опыту заказа. В MVP редактирование отзыва не предусмотрено.
                        </div>

                        <div className="flex flex-col gap-3 sm:flex-row">
                            <button
                                type="submit"
                                disabled={form.processing}
                                className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Опубликовать отзыв
                            </button>
                            <Link href={order.workspace_url} className="rounded-md border border-slate-300 bg-white px-5 py-3 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                Открыть рабочую область
                            </Link>
                        </div>
                    </form>
                </div>
            </section>
        </DashboardLayout>
    );
}
