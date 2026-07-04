import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const statusClasses = {
    draft: 'bg-slate-100 text-slate-700',
    published: 'bg-emerald-50 text-emerald-700',
    hidden: 'bg-amber-50 text-amber-700',
};

export default function Index({ items, statusLabels }) {
    return (
        <DashboardLayout>
            <Head title="Портфолио" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Исполнитель</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950">Портфолио</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Публичные работы показываются в профиле исполнителя. Не добавляйте контакты, ссылки на мессенджеры и платежные реквизиты.
                        </p>
                    </div>
                    <Link
                        href="/performer/portfolio/create"
                        className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                    >
                        Добавить работу
                    </Link>
                </div>

                {items.length > 0 ? (
                    <div className="mt-8 grid gap-4">
                        {items.map((item) => (
                            <article key={item.id} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                                <div className="grid gap-5 lg:grid-cols-[160px_1fr_auto]">
                                    <div className="h-28 rounded-md bg-slate-100">
                                        {item.image_url ? (
                                            <img src={item.image_url} alt="" className="h-full w-full rounded-md object-cover" />
                                        ) : (
                                            <div className="grid h-full place-items-center text-sm text-slate-500">Без превью</div>
                                        )}
                                    </div>
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className={`rounded-md px-3 py-1 text-xs font-semibold ${statusClasses[item.status] ?? statusClasses.draft}`}>
                                                {statusLabels[item.status]}
                                            </span>
                                            {item.category && <span className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">{item.category}</span>}
                                        </div>
                                        <h2 className="mt-3 text-2xl font-semibold text-slate-950">{item.title}</h2>
                                        {item.description && <p className="mt-2 line-clamp-2 text-sm leading-6 text-slate-600">{item.description}</p>}
                                    </div>
                                    <div className="flex flex-wrap gap-2 lg:flex-col">
                                        <Link href={item.edit_url} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                            Редактировать
                                        </Link>
                                        {item.status === 'published' ? (
                                            <Link href={item.hide_url} method="post" as="button" className="rounded-md border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-700 hover:bg-amber-100">
                                                Скрыть
                                            </Link>
                                        ) : (
                                            <Link href={item.publish_url} method="post" as="button" className="rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                                                Опубликовать
                                            </Link>
                                        )}
                                        <Link href={item.delete_url} method="delete" as="button" className="rounded-md border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-700 hover:bg-red-100">
                                            Удалить
                                        </Link>
                                    </div>
                                </div>
                            </article>
                        ))}
                    </div>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Работ пока нет</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">Добавьте первую работу, чтобы профиль можно было отправить на проверку без опубликованной услуги.</p>
                        <Link href="/performer/portfolio/create" className="mt-6 inline-flex rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                            Добавить работу
                        </Link>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}
