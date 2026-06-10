import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

const rules = [
    'Тестируйте только локальные сценарии и demo-аккаунты.',
    'Фиксируйте роль, страницу, сценарий, ожидаемый и фактический результат.',
    'Если нужен скриншот, отправьте его владельцу проекта отдельным сообщением.',
    'Не проверяйте реальные платежи, выплаты, KYC и production-сценарии.',
];

const forbidden = [
    'Реальные банковские карты и платежные реквизиты.',
    'Паспорта, ИНН, СНИЛС, договоры и личные документы.',
    'Личные пароли, рабочие аккаунты и приватные контакты.',
    'Персональные данные третьих лиц и реальные клиентские материалы.',
];

export default function BetaTesting({ accounts = [], roleChecklists = [], feedbackUrl = '/beta-feedback/create' }) {
    return (
        <PublicLayout>
            <Head title="Beta-тестирование Таскоры" />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <div className="grid gap-8 lg:grid-cols-[1fr_360px] lg:items-start">
                        <div>
                            <p className="text-sm font-semibold uppercase text-blue-700">Закрытая проверка MVP</p>
                            <h1 className="mt-3 max-w-4xl text-4xl font-semibold leading-tight tracking-normal text-slate-950 sm:text-5xl">
                                Beta-тестирование Таскоры
                            </h1>
                            <p className="mt-5 max-w-3xl text-base leading-8 text-slate-600">
                                Это тестовый режим для друзей и первых проверяющих. Реальные платежи, выплаты,
                                документы и персональные данные здесь не используются.
                            </p>
                            <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                <Link
                                    href={feedbackUrl}
                                    className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                                >
                                    Сообщить о проблеме
                                </Link>
                                <Link
                                    href="/catalog"
                                    className="inline-flex justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-100"
                                >
                                    Начать с каталога
                                </Link>
                            </div>
                        </div>

                        <aside className="rounded-lg border border-amber-200 bg-amber-50 p-5 text-sm leading-6 text-amber-950">
                            <p className="font-semibold">Важное ограничение</p>
                            <p className="mt-2">
                                Таскора работает локально и с платежной заглушкой. Не вводите реальные карты,
                                документы, пароли и персональные данные.
                            </p>
                        </aside>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Аккаунты</p>
                        <h2 className="mt-2 text-3xl font-semibold text-slate-950">Тестовые роли</h2>
                    </div>
                    <p className="max-w-xl text-sm leading-6 text-slate-600">
                        Пароль у demo-аккаунтов одинаковый и нужен только для закрытого тестирования.
                    </p>
                </div>

                <div className="mt-6 overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                            <tr>
                                <th className="px-4 py-3 font-semibold">Роль</th>
                                <th className="px-4 py-3 font-semibold">Email</th>
                                <th className="px-4 py-3 font-semibold">Пароль</th>
                                <th className="px-4 py-3 font-semibold">Кабинет</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {accounts.map((account) => (
                                <tr key={account.email}>
                                    <td className="whitespace-nowrap px-4 py-4 font-semibold text-slate-950">{account.role}</td>
                                    <td className="whitespace-nowrap px-4 py-4 font-mono text-slate-700">{account.email}</td>
                                    <td className="whitespace-nowrap px-4 py-4 font-mono text-slate-700">{account.password}</td>
                                    <td className="whitespace-nowrap px-4 py-4">
                                        <Link href={account.dashboard} className="font-semibold text-blue-700 hover:text-blue-800">
                                            {account.dashboard}
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </section>

            <section className="border-y border-slate-200 bg-white">
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                    <div className="mb-8 max-w-2xl">
                        <p className="text-sm font-semibold uppercase text-blue-700">Сценарии</p>
                        <h2 className="mt-2 text-3xl font-semibold text-slate-950">Чек-листы по ролям</h2>
                    </div>

                    <div className="grid gap-4 lg:grid-cols-2">
                        {roleChecklists.map((checklist) => (
                            <article key={checklist.role} className="rounded-lg border border-slate-200 bg-slate-50 p-6">
                                <div className="flex flex-wrap items-center justify-between gap-3">
                                    <h3 className="text-xl font-semibold text-slate-950">{checklist.role}</h3>
                                    <span className="rounded-md bg-white px-3 py-1 text-xs font-semibold text-slate-600 ring-1 ring-slate-200">
                                        beta-сценарий
                                    </span>
                                </div>
                                <p className="mt-3 text-sm leading-6 text-slate-600">{checklist.goal}</p>
                                <ol className="mt-5 space-y-3">
                                    {checklist.steps.map((step, index) => (
                                        <li key={step} className="flex gap-3 text-sm leading-6 text-slate-700">
                                            <span className="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-slate-950 text-xs font-semibold text-white">
                                                {index + 1}
                                            </span>
                                            <span>{step}</span>
                                        </li>
                                    ))}
                                </ol>
                                <p className="mt-5 rounded-md bg-white p-4 text-sm font-medium leading-6 text-slate-800 ring-1 ring-slate-200">
                                    Ожидаемый результат: {checklist.expected}
                                </p>
                            </article>
                        ))}
                    </div>
                </div>
            </section>

            <section className="mx-auto grid max-w-7xl gap-6 px-4 py-12 sm:px-6 lg:grid-cols-2 lg:px-8">
                <InfoBlock title="Правила тестирования" items={rules} />
                <InfoBlock title="Что нельзя делать" items={forbidden} tone="danger" />
            </section>
        </PublicLayout>
    );
}

function InfoBlock({ title, items, tone = 'default' }) {
    const toneClass = tone === 'danger' ? 'border-red-200 bg-red-50 text-red-950' : 'border-slate-200 bg-white text-slate-950';

    return (
        <section className={`rounded-lg border p-6 shadow-sm ${toneClass}`}>
            <h2 className="text-2xl font-semibold">{title}</h2>
            <ul className="mt-5 space-y-3 text-sm leading-6">
                {items.map((item) => (
                    <li key={item} className="flex gap-3">
                        <span className="mt-2 h-2 w-2 shrink-0 rounded-full bg-current" aria-hidden="true" />
                        <span>{item}</span>
                    </li>
                ))}
            </ul>
        </section>
    );
}
