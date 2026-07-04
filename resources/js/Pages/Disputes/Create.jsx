import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

export default function Create({ order, reasonOptions, storeUrl }) {
    const form = useForm({
        reason: reasonOptions[0]?.value ?? 'other',
        description: '',
    });

    const submit = (event) => {
        event.preventDefault();
        form.post(storeUrl);
    };

    return (
        <DashboardLayout>
            <Head title="Открыть спор" />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-red-700">Арбитраж</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Открыть спор</h1>
                        <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                            Спор остановит автоматическую разблокировку оплаты до решения модератора.
                        </p>
                    </div>
                    <Link href={order.show_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        К заказу
                    </Link>
                </div>

                <article className="mt-8 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <h2 className="text-2xl font-semibold text-slate-950">{order.title}</h2>
                    <div className="mt-5 grid gap-4 text-sm sm:grid-cols-3">
                        <Info label="Статус заказа" value={order.status_label} />
                        <Info label="Статус оплаты" value={order.payment_status_label} />
                        <Info label="Сумма" value={`${currency.format(order.price)} ₽`} />
                    </div>
                </article>

                <form onSubmit={submit} className="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div>
                        <label htmlFor="reason" className="text-sm font-semibold text-slate-900">Причина спора</label>
                        <select
                            id="reason"
                            name="reason"
                            value={form.data.reason}
                            onChange={(event) => form.setData('reason', event.target.value)}
                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            {reasonOptions.map((option) => (
                                <option key={option.value} value={option.value}>{option.label}</option>
                            ))}
                        </select>
                        {form.errors.reason && <p className="mt-2 text-sm text-red-600">{form.errors.reason}</p>}
                    </div>

                    <div className="mt-5">
                        <label htmlFor="description" className="text-sm font-semibold text-slate-900">Описание проблемы</label>
                        <textarea
                            id="description"
                            name="description"
                            rows={8}
                            value={form.data.description}
                            onChange={(event) => form.setData('description', event.target.value)}
                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            placeholder="Опишите, что произошло, какие материалы нужно проверить и какое решение вы считаете справедливым."
                        />
                        {form.errors.description && <p className="mt-2 text-sm text-red-600">{form.errors.description}</p>}
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="mt-5 rounded-md bg-red-600 px-5 py-3 text-sm font-semibold text-white hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Открыть спор
                    </button>
                </form>
            </section>
        </DashboardLayout>
    );
}

function Info({ label, value }) {
    return (
        <div className="rounded-lg bg-slate-50 p-4">
            <p className="text-xs font-semibold uppercase text-slate-500">{label}</p>
            <p className="mt-2 font-semibold text-slate-950">{value ?? 'Не указано'}</p>
        </div>
    );
}
