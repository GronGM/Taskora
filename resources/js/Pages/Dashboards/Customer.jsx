import { Head, Link } from '@inertiajs/react';
import AttentionList from '../../Components/Dashboard/AttentionList';
import StatTiles from '../../Components/Dashboard/StatTiles';
import DashboardLayout from '../../Layouts/DashboardLayout';

const cards = [
    { title: 'Мои заказы', href: '/customer/orders', description: 'Следите за заказами из услуг и выбранных откликов.' },
    { title: 'Мои задания', href: '/customer/tasks', description: 'Публикуйте задачи и выбирайте исполнителей.' },
    { title: 'Создать задание', href: '/customer/tasks/create', description: 'Подготовьте новое задание для биржи.' },
    { title: 'Каталог услуг', href: '/catalog', description: 'Выберите готовую услугу и создайте заказ.' },
];

export default function Customer({ onboarding = null, stats = null, attention = null }) {
    return (
        <DashboardLayout>
            <Head title="Кабинет заказчика" />
            {onboarding && !onboarding.has_tasks && !onboarding.has_orders && (
                <section className="mx-auto max-w-7xl px-4 pt-12 sm:px-6 lg:px-8">
                    <div className="rounded-lg border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950">
                        <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Добро пожаловать в Таскору!</h2>
                        <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-700 dark:text-slate-300">
                            Разместите первое задание — это три коротких шага. Исполнители откликнутся с ценой и сроком,
                            вы выберете лучшего, а оплата будет защищена до приемки работы.
                        </p>
                        <ol className="mt-4 grid gap-3 text-sm sm:grid-cols-3">
                            <li className="rounded-md bg-white p-4 font-medium text-slate-800 shadow-sm dark:bg-slate-900 dark:text-slate-200">1. Опишите задачу</li>
                            <li className="rounded-md bg-white p-4 font-medium text-slate-800 shadow-sm dark:bg-slate-900 dark:text-slate-200">2. Получите отклики</li>
                            <li className="rounded-md bg-white p-4 font-medium text-slate-800 shadow-sm dark:bg-slate-900 dark:text-slate-200">3. Выберите исполнителя</li>
                        </ol>
                        <Link
                            href="/customer/tasks/create"
                            className="mt-5 inline-flex rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                        >
                            Разместить первое задание
                        </Link>
                    </div>
                </section>
            )}
            <section className="mx-auto max-w-7xl px-4 pt-10 sm:px-6 lg:px-8">
                {stats && (
                    <StatTiles
                        tiles={[
                            { label: 'Заказы в работе', value: stats.orders_in_progress, href: '/customer/orders' },
                            { label: 'Ждут вашей приемки', value: stats.orders_to_review, href: '/customer/orders', hint: stats.orders_to_review > 0 ? 'проверьте результат' : null },
                            { label: 'Ждут оплаты', value: stats.orders_awaiting_payment, href: '/customer/orders' },
                            { label: 'Откликов на задания', value: stats.pending_offers, href: '/customer/tasks' },
                        ]}
                    />
                )}
                {attention && (
                    <>
                        <AttentionList
                            title="Работа сдана — проверьте результат"
                            description="Если не принять работу до даты автоприемки, оплата разблокируется автоматически."
                            items={attention.needs_review}
                            actionLabel="Проверить"
                        />
                        <AttentionList
                            title="Новые отклики на ваши задания"
                            items={attention.tasks_with_offers}
                            actionLabel="Посмотреть"
                        />
                    </>
                )}
            </section>
            <DashboardContent title="Кабинет заказчика" cards={cards} />
        </DashboardLayout>
    );
}

function DashboardContent({ title, cards }) {
    return (
        <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
            <p className="text-sm font-semibold uppercase text-blue-700">Рабочая область</p>
            <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">{title}</h1>
            <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                {cards.map((card) => (
                    <article key={card.title} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-lg font-semibold text-slate-950">{card.title}</h2>
                        <p className="mt-3 text-sm leading-6 text-slate-600">{card.description}</p>
                        <Link href={card.href} className="mt-5 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800">
                            Открыть
                        </Link>
                    </article>
                ))}
            </div>
        </section>
    );
}
