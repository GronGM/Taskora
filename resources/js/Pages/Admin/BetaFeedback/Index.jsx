import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const statusTone = {
    open: 'bg-amber-50 text-amber-700 ring-amber-200',
    in_review: 'bg-blue-50 text-blue-700 ring-blue-200',
    resolved: 'bg-emerald-50 text-emerald-700 ring-emerald-200',
    rejected: 'bg-slate-100 text-slate-700 ring-slate-200',
};

export default function Index({ feedback = [], summary = {}, labels = {} }) {
    const statuses = labels.statuses ?? {};
    const types = labels.types ?? {};
    const severities = labels.severities ?? {};
    const cards = [
        { title: 'Всего', value: summary.total ?? 0 },
        { title: 'Открыты', value: summary.open ?? 0 },
        { title: 'В работе', value: summary.in_review ?? 0 },
        { title: 'Решены', value: summary.resolved ?? 0 },
        { title: 'Отклонены', value: summary.rejected ?? 0 },
    ];

    return (
        <DashboardLayout>
            <Head title="Beta-отзывы" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Администрирование</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Beta-отзывы</h1>
                        <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                            Внутренняя очередь обращений от друзей и первых тестировщиков без внешних сервисов.
                        </p>
                    </div>
                    <Link href="/admin/dashboard" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                        Вернуться в админ-панель
                    </Link>
                </div>

                <div className="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {cards.map((card) => (
                        <article key={card.title} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                            <p className="text-sm text-slate-500">{card.title}</p>
                            <p className="mt-3 text-2xl font-semibold text-slate-950">{card.value}</p>
                        </article>
                    ))}
                </div>

                <div className="mt-8 rounded-lg border border-slate-200 bg-white shadow-sm">
                    {feedback.length > 0 ? (
                        <div className="divide-y divide-slate-200">
                            {feedback.map((item) => (
                                <article key={item.id} className="grid gap-4 p-5 lg:grid-cols-[1fr_160px] lg:items-center">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge tone={statusTone[item.status]}>{statuses[item.status] ?? item.status}</Badge>
                                            <Badge>{types[item.type] ?? item.type}</Badge>
                                            <Badge>{severities[item.severity] ?? item.severity}</Badge>
                                        </div>
                                        <h2 className="mt-3 text-lg font-semibold text-slate-950">{item.title}</h2>
                                        <p className="mt-2 text-sm leading-6 text-slate-600">
                                            {item.role ?? 'роль не указана'} · {item.scenario ?? 'сценарий не указан'} · {item.user ?? 'гость'} · {item.created_at}
                                        </p>
                                        {item.page_url && <p className="mt-1 break-all text-sm text-slate-500">{item.page_url}</p>}
                                    </div>
                                    <Link href={item.show_url} className="rounded-md bg-slate-950 px-4 py-3 text-center text-sm font-semibold text-white hover:bg-slate-800">
                                        Открыть
                                    </Link>
                                </article>
                            ))}
                        </div>
                    ) : (
                        <div className="p-10 text-center">
                            <h2 className="text-2xl font-semibold text-slate-950">Обращений пока нет</h2>
                            <p className="mt-3 text-sm leading-6 text-slate-600">
                                После отправки формы `/beta-feedback/create` записи появятся в этой очереди.
                            </p>
                        </div>
                    )}
                </div>
            </section>
        </DashboardLayout>
    );
}

function Badge({ children, tone = 'bg-slate-100 text-slate-700 ring-slate-200' }) {
    return (
        <span className={`rounded-md px-3 py-1 text-xs font-semibold ring-1 ${tone}`}>
            {children}
        </span>
    );
}
