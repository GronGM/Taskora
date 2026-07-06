import { Head, Link } from '@inertiajs/react';
import AttentionList from '../../Components/Dashboard/AttentionList';
import OnboardingChecklist from '../../Components/Dashboard/OnboardingChecklist';
import StatTiles from '../../Components/Dashboard/StatTiles';
import DashboardLayout from '../../Layouts/DashboardLayout';

const cards = [
    { title: 'Мои заказы', href: '/customer/orders', description: 'Следите за заказами из услуг и выбранных откликов.' },
    { title: 'Мои задания', href: '/customer/tasks', description: 'Публикуйте задачи и выбирайте исполнителей.' },
    { title: 'Создать задание', href: '/customer/tasks/create', description: 'Подготовьте новое задание для биржи.' },
    { title: 'Каталог услуг', href: '/catalog', description: 'Выберите готовую услугу и создайте заказ.' },
    { title: 'Приглашайте друзей', href: '/referrals', description: 'Персональная ссылка и список приглашенных.' },
];

export default function Customer({ onboarding = null, stats = null, attention = null }) {
    return (
        <DashboardLayout>
            <Head title="Кабинет заказчика" />
            {onboarding && (
                <section className="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
                    <OnboardingChecklist
                        title="Как получить результат"
                        description="Оплата защищена: деньги замораживаются и уходят исполнителю только после вашей приемки."
                        steps={[
                            { label: 'Разместите задание', done: onboarding.has_tasks, href: '/customer/tasks/create', action: 'Разместить' },
                            { label: 'Выберите исполнителя', done: onboarding.has_orders, href: '/customer/tasks', action: 'К заданиям' },
                            { label: 'Примите работу', done: false, href: null, action: null },
                        ].filter((step, index) => index < 2 || !onboarding.has_orders)}
                    />
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
