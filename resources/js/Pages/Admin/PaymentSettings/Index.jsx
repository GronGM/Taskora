import { Head, Link } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const boolLabel = (value) => (value ? 'Задан' : 'Не задан');
const enabledLabel = (value) => (value ? 'Включено' : 'Отключено');

export default function PaymentSettings({ settings, warning }) {
    const rows = [
        ['Текущий provider', settings.current_provider],
        ['Provider mode', settings.provider_mode],
        ['TASKORA_PAYMENTS_MODE', settings.taskora_payments_mode],
        ['PAYMENT_PROVIDER', settings.payment_provider],
        ['PAYMENT_PROVIDER_MODE', settings.payment_provider_mode],
        ['yookassa.enabled', enabledLabel(settings.yookassa.enabled)],
        ['yookassa.safe_deal_enabled', enabledLabel(settings.yookassa.safe_deal_enabled)],
        ['YOOKASSA_SHOP_ID', boolLabel(settings.yookassa.shop_id_present)],
        ['YOOKASSA_SECRET_KEY', boolLabel(settings.yookassa.secret_key_present)],
    ];

    return (
        <DashboardLayout>
            <Head title="Настройки платежей" />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Администрирование</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Настройки платежей</h1>
                        <p className="mt-4 max-w-3xl text-sm leading-6 text-slate-600">
                            Read-only сводка платежного режима для локального beta-тестирования. Значения секретов не выводятся.
                        </p>
                    </div>
                    <Link href="/admin/dashboard" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                        Вернуться в админ-панель
                    </Link>
                </div>

                <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm font-semibold leading-6 text-amber-900">
                    {warning}
                </div>

                <section className="mt-6 rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-xl font-semibold text-slate-950">Текущая конфигурация</h2>
                    <div className="mt-5 overflow-x-auto">
                        <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                            <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th className="px-4 py-3 font-semibold">Параметр</th>
                                    <th className="px-4 py-3 font-semibold">Значение</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {rows.map(([label, value]) => (
                                    <tr key={label}>
                                        <td className="whitespace-nowrap px-4 py-4 font-semibold text-slate-800">{label}</td>
                                        <td className="px-4 py-4 text-slate-700">{value ?? 'Не задан'}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </section>

                <div className="mt-6 rounded-lg border border-slate-200 bg-slate-50 p-5 text-sm leading-6 text-slate-700">
                    Страница не подключает платежный шлюз, не хранит банковские данные и не раскрывает секретные ключи.
                </div>
            </section>
        </DashboardLayout>
    );
}
