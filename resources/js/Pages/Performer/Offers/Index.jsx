import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const currency = new Intl.NumberFormat('ru-RU');

const statusClasses = {
    submitted: 'bg-emerald-50 text-emerald-700',
    withdrawn: 'bg-slate-100 text-slate-700',
    rejected: 'bg-red-50 text-red-700',
};

export default function Index({ offers, statusLabels }) {
    return (
        <DashboardLayout>
            <Head title="Мои отклики" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Исполнитель</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-normal text-slate-950">Мои отклики</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Следите за предложениями, которые отправили заказчикам на бирже заданий.
                        </p>
                    </div>
                    <Link href="/tasks" className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                        Найти задания
                    </Link>
                </div>

                {offers.length > 0 ? (
                    <div className="mt-8 grid gap-4">
                        {offers.map((offer) => (
                            <article key={offer.id} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className={`rounded-md px-3 py-1 text-xs font-semibold ${statusClasses[offer.status] ?? statusClasses.submitted}`}>
                                                {statusLabels[offer.status]}
                                            </span>
                                            <span className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                                {offer.task.category}
                                            </span>
                                        </div>
                                        <h2 className="mt-4 text-2xl font-semibold text-slate-950">{offer.task.title}</h2>
                                        <div className="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-4">
                                            <p>Цена: <span className="font-semibold text-slate-950">{currency.format(offer.price)} ₽</span></p>
                                            <p>Срок: <span className="font-semibold text-slate-950">{offer.delivery_days} дн.</span></p>
                                            <p>Дедлайн задания: <span className="font-semibold text-slate-950">{offer.task.deadline_at ?? 'Не указан'}</span></p>
                                            <p>Заказчик: <span className="font-semibold text-slate-950">{offer.task.customer}</span></p>
                                        </div>
                                        <p className="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{offer.message}</p>
                                    </div>
                                    <div className="flex flex-wrap gap-2 lg:justify-end">
                                        {offer.task.url && (
                                            <Link href={offer.task.url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                                Задание
                                            </Link>
                                        )}
                                        {offer.status === 'submitted' && (
                                            <Link
                                                href={offer.withdraw_url}
                                                method="post"
                                                as="button"
                                                className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                            >
                                                Отозвать
                                            </Link>
                                        )}
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Откликов пока нет</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">
                            Откройте биржу заданий и отправьте первое предложение заказчику.
                        </p>
                        <Link href="/tasks" className="mt-6 inline-flex rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                            Перейти к заданиям
                        </Link>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}
