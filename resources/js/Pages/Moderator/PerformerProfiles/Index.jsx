import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const tabs = [
    { value: 'pending_review', label: 'На проверке' },
    { value: 'verified', label: 'Проверены' },
    { value: 'rejected', label: 'Отклонены' },
];

export default function Index({ profiles, filters, statusLabels }) {
    return (
        <DashboardLayout>
            <Head title="Проверка профилей исполнителей" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Модерация</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950">Проверка профилей исполнителей</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Ручная проверка публичных данных, специализаций, портфолио, услуг и истории заказов без KYC и паспортных данных.
                        </p>
                    </div>
                    <Link
                        href="/moderator/services"
                        className="rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                    >
                        Модерация услуг
                    </Link>
                </div>

                <div className="mt-6 flex flex-wrap gap-2">
                    {tabs.map((tab) => (
                        <Link
                            key={tab.value}
                            href={`/moderator/performer-profiles?status=${tab.value}`}
                            className={`rounded-md px-4 py-2 text-sm font-semibold ${
                                filters.status === tab.value
                                    ? 'bg-blue-600 text-white'
                                    : 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50'
                            }`}
                        >
                            {tab.label}
                        </Link>
                    ))}
                </div>

                {profiles.length > 0 ? (
                    <div className="mt-8 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                        <div className="divide-y divide-slate-200">
                            {profiles.map((profile) => (
                                <article key={profile.id} className="grid gap-5 p-6 lg:grid-cols-[1fr_auto] lg:items-center">
                                    <div>
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="rounded-md bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700">
                                                {statusLabels[profile.status]}
                                            </span>
                                            {profile.specializations.map((category) => (
                                                <span key={category.id} className="rounded-md bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">
                                                    {category.name}
                                                </span>
                                            ))}
                                        </div>
                                        <h2 className="mt-4 text-2xl font-semibold text-slate-950">{profile.display_name}</h2>
                                        <p className="mt-2 text-sm leading-6 text-slate-600">{profile.headline || 'Заголовок не заполнен'}</p>
                                        <p className="mt-3 text-sm text-slate-500">Отправлен: {profile.submitted_at || 'дата не указана'}</p>
                                    </div>
                                    <Link
                                        href={profile.review_url}
                                        className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                                    >
                                        Проверить
                                    </Link>
                                </article>
                            ))}
                        </div>
                    </div>
                ) : (
                    <div className="mt-8 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
                        <h2 className="text-2xl font-semibold text-slate-950">Профилей в этом статусе нет</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">Новые заявки появятся здесь после отправки профиля исполнителем.</p>
                    </div>
                )}
            </section>
        </DashboardLayout>
    );
}
