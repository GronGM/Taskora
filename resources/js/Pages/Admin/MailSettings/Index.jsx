import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

export default function MailSettings({ settings, warning, hint }) {
    const rows = [
        ['APP_ENV', settings.app_env],
        ['MAIL_MAILER', settings.mail_mailer],
        ['MAIL_HOST', settings.mail_host],
        ['MAIL_PORT', settings.mail_port],
        ['MAIL_USERNAME', settings.mail_username],
        ['MAIL_PASSWORD', settings.mail_password],
        ['MAIL_FROM_ADDRESS', settings.mail_from_address],
        ['MAIL_FROM_NAME', settings.mail_from_name],
    ];

    return (
        <DashboardLayout>
            <Head title="Настройки почты" />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700 dark:text-blue-300">Администрирование</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950 dark:text-white">Настройки почты</h1>
                        <p className="mt-4 max-w-3xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Read-only сводка почтового режима для staging и локального тестирования. Страница показывает только безопасные
                            значения и признаки наличия секретов.
                        </p>
                    </div>
                    <Link href="/admin/dashboard" className="text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                        Вернуться в админ-панель
                    </Link>
                </div>

                <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm font-semibold leading-6 text-amber-900 dark:border-amber-800 dark:bg-amber-950/50 dark:text-amber-100">
                    {warning}
                </div>

                <section className="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <h2 className="text-xl font-semibold text-slate-950 dark:text-white">Текущая конфигурация</h2>
                    <div className="mt-5 overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-left text-sm dark:divide-slate-800">
                            <thead className="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-slate-950 dark:text-slate-400">
                                <tr>
                                    <th className="px-4 py-3 font-semibold">Параметр</th>
                                    <th className="px-4 py-3 font-semibold">Значение</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {rows.map(([label, value]) => (
                                    <tr key={label}>
                                        <td className="whitespace-nowrap px-4 py-4 font-semibold text-slate-800 dark:text-slate-100">{label}</td>
                                        <td className="px-4 py-4 text-slate-700 dark:text-slate-300">{value ?? 'Не задан'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <div className="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-300">
                    {hint}
                </div>
            </section>
        </DashboardLayout>
    );
}
