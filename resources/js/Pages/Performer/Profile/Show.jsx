import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const statusClasses = {
    not_submitted: 'bg-slate-100 text-slate-700',
    pending_review: 'bg-amber-50 text-amber-700',
    verified: 'bg-emerald-50 text-emerald-700',
    rejected: 'bg-red-50 text-red-700',
};

const requirementLabels = {
    display_name: 'Публичное имя заполнено',
    headline: 'Короткий заголовок заполнен',
    bio: 'Описание не короче 100 символов',
    specializations: 'Выбрана минимум одна специализация',
    proof: 'Есть опубликованная работа портфолио или услуга',
};

export default function Show({ profile, categories, statusLabels, requirements }) {
    const form = useForm({
        display_name: profile.display_name ?? '',
        headline: profile.headline ?? '',
        bio: profile.bio ?? '',
        experience_years: profile.experience_years ?? '',
        response_time_label: profile.response_time_label ?? '',
        portfolio_summary: profile.portfolio_summary ?? '',
        specialization_ids: profile.specialization_ids ?? [],
    });

    const avatarForm = useForm({ image: null });
    const coverForm = useForm({ image: null });
    const submitForm = useForm({});

    const toggleSpecialization = (id) => {
        const current = form.data.specialization_ids.map(Number);

        form.setData(
            'specialization_ids',
            current.includes(id) ? current.filter((item) => item !== id) : [...current, id],
        );
    };

    const save = (event) => {
        event.preventDefault();
        form.patch(profile.update_url);
    };

    const uploadAvatar = (event) => {
        event.preventDefault();
        avatarForm.post(profile.avatar_url_action, { forceFormData: true });
    };

    const uploadCover = (event) => {
        event.preventDefault();
        coverForm.post(profile.cover_url_action, { forceFormData: true });
    };

    return (
        <DashboardLayout>
            <Head title="Профиль исполнителя" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 lg:flex-row lg:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Исполнитель</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950">Профиль исполнителя</h1>
                        <p className="mt-4 max-w-3xl text-sm leading-6 text-slate-600">
                            Заполните публичную витрину, добавьте специализации и отправьте профиль на ручную проверку. Контакты и предложения перейти вне Таскоры будут заблокированы.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Link
                            href="/performer/portfolio"
                            className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                        >
                            Портфолио
                        </Link>
                        <Link
                            href={profile.public_url}
                            className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Публичный профиль
                        </Link>
                    </div>
                </div>

                <div className="mt-8 grid gap-6 lg:grid-cols-[1fr_360px]">
                    <form onSubmit={save} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="flex flex-wrap items-center gap-2">
                            <span className={`rounded-md px-3 py-1 text-xs font-semibold ${statusClasses[profile.verification_status] ?? statusClasses.not_submitted}`}>
                                {statusLabels[profile.verification_status]}
                            </span>
                            {profile.verified_at && <span className="text-sm text-slate-500">Проверен: {profile.verified_at}</span>}
                        </div>

                        {profile.verification_status === 'rejected' && profile.verification_note && (
                            <div className="mt-5 rounded-lg border border-red-200 bg-red-50 p-4">
                                <p className="text-sm font-semibold text-red-800">Причина отклонения</p>
                                <p className="mt-2 text-sm leading-6 text-red-700">{profile.verification_note}</p>
                            </div>
                        )}

                        <div className="mt-6 grid gap-5">
                            <label className="block">
                                <span className="text-sm font-semibold text-slate-900">Публичное имя</span>
                                <input
                                    value={form.data.display_name}
                                    onChange={(event) => form.setData('display_name', event.target.value)}
                                    className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                                {form.errors.display_name && <p className="mt-2 text-sm text-red-600">{form.errors.display_name}</p>}
                            </label>

                            <label className="block">
                                <span className="text-sm font-semibold text-slate-900">Короткий заголовок</span>
                                <input
                                    value={form.data.headline}
                                    onChange={(event) => form.setData('headline', event.target.value)}
                                    className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                                {form.errors.headline && <p className="mt-2 text-sm text-red-600">{form.errors.headline}</p>}
                            </label>

                            <label className="block">
                                <span className="text-sm font-semibold text-slate-900">Описание</span>
                                <textarea
                                    value={form.data.bio}
                                    onChange={(event) => form.setData('bio', event.target.value)}
                                    rows={8}
                                    className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                                <span className="mt-1 block text-xs text-slate-500">{form.data.bio.length} символов</span>
                                {form.errors.bio && <p className="mt-2 text-sm text-red-600">{form.errors.bio}</p>}
                            </label>

                            <div className="grid gap-5 sm:grid-cols-2">
                                <label className="block">
                                    <span className="text-sm font-semibold text-slate-900">Опыт в годах</span>
                                    <input
                                        type="number"
                                        min="0"
                                        max="80"
                                        value={form.data.experience_years}
                                        onChange={(event) => form.setData('experience_years', event.target.value)}
                                        className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                    {form.errors.experience_years && <p className="mt-2 text-sm text-red-600">{form.errors.experience_years}</p>}
                                </label>

                                <label className="block">
                                    <span className="text-sm font-semibold text-slate-900">Скорость ответа</span>
                                    <input
                                        value={form.data.response_time_label}
                                        onChange={(event) => form.setData('response_time_label', event.target.value)}
                                        placeholder="Например: отвечает в течение дня"
                                        className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                    {form.errors.response_time_label && <p className="mt-2 text-sm text-red-600">{form.errors.response_time_label}</p>}
                                </label>
                            </div>

                            <label className="block">
                                <span className="text-sm font-semibold text-slate-900">Описание портфолио</span>
                                <textarea
                                    value={form.data.portfolio_summary}
                                    onChange={(event) => form.setData('portfolio_summary', event.target.value)}
                                    rows={4}
                                    className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                                {form.errors.portfolio_summary && <p className="mt-2 text-sm text-red-600">{form.errors.portfolio_summary}</p>}
                            </label>

                            <fieldset>
                                <legend className="text-sm font-semibold text-slate-900">Специализации</legend>
                                <p className="mt-1 text-xs text-slate-500">Можно выбрать до 7 активных категорий.</p>
                                <div className="mt-3 grid gap-2 sm:grid-cols-2">
                                    {categories.map((category) => {
                                        const checked = form.data.specialization_ids.map(Number).includes(category.id);

                                        return (
                                            <label
                                                key={category.id}
                                                className={`flex items-center gap-3 rounded-md border px-3 py-2 text-sm ${
                                                    checked ? 'border-blue-200 bg-blue-50 text-blue-900' : 'border-slate-200 bg-white text-slate-700'
                                                }`}
                                            >
                                                <input
                                                    type="checkbox"
                                                    checked={checked}
                                                    onChange={() => toggleSpecialization(category.id)}
                                                    className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                                />
                                                <span>{category.name}</span>
                                            </label>
                                        );
                                    })}
                                </div>
                                {form.errors.specialization_ids && <p className="mt-2 text-sm text-red-600">{form.errors.specialization_ids}</p>}
                            </fieldset>
                        </div>

                        <button
                            type="submit"
                            disabled={form.processing}
                            className="mt-6 rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Сохранить профиль
                        </button>
                    </form>

                    <aside className="space-y-6">
                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-xl font-semibold text-slate-950">Медиа профиля</h2>
                            {profile.cover_url && <img src={profile.cover_url} alt="" className="mt-4 h-28 w-full rounded-md object-cover" />}
                            {profile.avatar_url && <img src={profile.avatar_url} alt="" className="mt-4 h-20 w-20 rounded-md object-cover" />}

                            <form onSubmit={uploadAvatar} className="mt-5">
                                <label className="text-sm font-semibold text-slate-900">Аватар</label>
                                <input
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    onChange={(event) => avatarForm.setData('image', event.target.files[0])}
                                    className="mt-2 block w-full text-sm text-slate-600"
                                />
                                {avatarForm.errors.image && <p className="mt-2 text-sm text-red-600">{avatarForm.errors.image}</p>}
                                <button className="mt-3 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                    Загрузить аватар
                                </button>
                            </form>

                            <form onSubmit={uploadCover} className="mt-5 border-t border-slate-100 pt-5">
                                <label className="text-sm font-semibold text-slate-900">Обложка</label>
                                <input
                                    type="file"
                                    accept=".jpg,.jpeg,.png,.webp"
                                    onChange={(event) => coverForm.setData('image', event.target.files[0])}
                                    className="mt-2 block w-full text-sm text-slate-600"
                                />
                                {coverForm.errors.image && <p className="mt-2 text-sm text-red-600">{coverForm.errors.image}</p>}
                                <button className="mt-3 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                    Загрузить обложку
                                </button>
                            </form>
                        </section>

                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-xl font-semibold text-slate-950">Проверка профиля</h2>
                            <div className="mt-4 space-y-2">
                                {Object.entries(requirements).map(([key, passed]) => (
                                    <div key={key} className="flex items-center justify-between gap-3 text-sm">
                                        <span className={passed ? 'text-slate-700' : 'text-slate-500'}>{requirementLabels[key]}</span>
                                        <span className={`rounded-md px-2 py-1 text-xs font-semibold ${passed ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'}`}>
                                            {passed ? 'Готово' : 'Нужно'}
                                        </span>
                                    </div>
                                ))}
                            </div>
                            {Object.values(submitForm.errors).map((error) => (
                                <p key={error} className="mt-3 text-sm text-red-600">{error}</p>
                            ))}
                            <button
                                type="button"
                                onClick={() => submitForm.post(profile.submit_verification_url)}
                                disabled={submitForm.processing}
                                className="mt-5 w-full rounded-md bg-emerald-600 px-5 py-3 text-sm font-semibold text-white hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Отправить профиль на проверку
                            </button>
                        </section>
                    </aside>
                </div>
            </section>
        </DashboardLayout>
    );
}
