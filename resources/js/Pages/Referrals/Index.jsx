import { Head } from '@inertiajs/react';
import { useState } from 'react';
import DashboardLayout from '../../Layouts/DashboardLayout';

export default function ReferralsIndex({ referralUrl, referrals = [], referralsCount = 0 }) {
    const [copied, setCopied] = useState(false);

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(referralUrl);
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        } catch {
            // Буфер обмена недоступен — пользователь скопирует вручную.
        }
    };

    return (
        <DashboardLayout>
            <Head title="Приглашайте друзей" />
            <section className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
                <p className="text-sm font-semibold uppercase text-blue-700">Рефералы</p>
                <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950 dark:text-slate-100">Приглашайте друзей</h1>
                <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-400">
                    Поделитесь персональной ссылкой — приглашенные зарегистрируются в Таскоре и будут закреплены за вами.
                    Бонусная программа для рефералов заработает вместе с подключением реальных платежей: все приглашенные до этого момента будут учтены.
                </p>

                <div className="mt-8 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <label htmlFor="referral-url" className="text-sm font-semibold text-slate-900 dark:text-slate-200">Ваша ссылка</label>
                    <div className="mt-2 flex flex-col gap-2 sm:flex-row">
                        <input
                            id="referral-url"
                            type="text"
                            readOnly
                            value={referralUrl}
                            onFocus={(event) => event.target.select()}
                            className="w-full rounded-md border border-slate-300 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200"
                        />
                        <button
                            type="button"
                            onClick={copyLink}
                            className="shrink-0 rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700"
                        >
                            {copied ? 'Скопировано!' : 'Скопировать'}
                        </button>
                    </div>
                </div>

                <div className="mt-6 rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 className="text-lg font-semibold text-slate-950 dark:text-slate-100">
                        Приглашено: {referralsCount}
                    </h2>
                    {referrals.length > 0 ? (
                        <ul className="mt-4 divide-y divide-slate-100 dark:divide-slate-800">
                            {referrals.map((referral) => (
                                <li key={referral.id} className="flex flex-wrap items-baseline justify-between gap-2 py-2.5 text-sm">
                                    <span className="font-medium text-slate-900 dark:text-slate-200">{referral.name}</span>
                                    <span className="text-slate-500 dark:text-slate-400">{referral.role} · {referral.registered_at}</span>
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">
                            Пока никто не зарегистрировался по вашей ссылке. Отправьте ее друзьям или коллегам, которым пригодится Таскора.
                        </p>
                    )}
                </div>
            </section>
        </DashboardLayout>
    );
}
