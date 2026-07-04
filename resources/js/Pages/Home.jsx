import { Head, Link } from '@inertiajs/react';
import { MessagesSquare, Search, ShieldCheck, Star } from 'lucide-react';
import PublicLayout from '../Layouts/PublicLayout';
import ServiceCard from '../Components/ServiceCard';

const advantages = [
    {
        title: 'Работа внутри заказа',
        text: 'Чат, файлы, статусы и история действий хранятся в одном месте.',
        Icon: MessagesSquare,
    },
    {
        title: 'Понятная безопасная сделка',
        text: 'Оплата в тестовом режиме удерживается до проверки результата. Реальный платежный шлюз будет подключен отдельно.',
        Icon: ShieldCheck,
    },
    {
        title: 'Исполнители с профилем',
        text: 'Отзывы, рейтинг, портфолио и верификация помогают быстрее выбрать подходящего специалиста.',
        Icon: Search,
    },
    {
        title: 'Споры и модерация',
        text: 'Если что-то пошло не так, спор можно передать на рассмотрение модератору.',
        Icon: Star,
    },
];

const steps = [
    'Опишите задачу или выберите готовую услугу',
    'Согласуйте условия внутри Таскоры',
    'Получите результат и оставьте отзыв',
];

export default function Home({ categories = [], services = [] }) {
    return (
        <PublicLayout>
            <Head title="Главная" />

            <section className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div className="mx-auto grid max-w-7xl gap-6 px-4 py-10 sm:px-6 sm:py-14 lg:grid-cols-[1.15fr_0.85fr] lg:gap-10 lg:px-8 lg:py-20">
                    <div className="flex flex-col justify-center">
                        <div className="mb-4 inline-flex w-fit items-center rounded-md border border-blue-100 bg-blue-50 px-3 py-1 text-sm font-medium text-blue-700 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-200 sm:mb-6">
                            Маркетплейс задач и услуг
                        </div>
                        <h1 className="max-w-4xl text-3xl font-semibold leading-tight tracking-tight text-slate-950 dark:text-white sm:text-5xl lg:text-6xl">
                            Найдите исполнителя для задачи — быстро, понятно и безопасно
                        </h1>
                        <p className="mt-4 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300 sm:mt-6 sm:text-lg sm:leading-8">
                            Разместите задание или выберите готовую услугу. Таскора помогает договориться, вести заказ, обмениваться файлами и решать спорные ситуации внутри одной платформы.
                        </p>
                        <div className="mt-6 flex flex-col gap-2 sm:mt-8 sm:flex-row sm:flex-wrap sm:gap-3">
                            <Link
                                href="/customer/tasks/create"
                                className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950 sm:py-3"
                            >
                                Разместить задание
                            </Link>
                            <Link
                                href="/catalog"
                                className="inline-flex justify-center rounded-md border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 hover:bg-slate-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950 sm:py-3"
                            >
                                Найти услугу
                            </Link>
                            <Link
                                href="/register"
                                className="inline-flex justify-center rounded-md px-5 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50 hover:text-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:text-blue-200 dark:hover:bg-blue-950 dark:hover:text-blue-100 dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950 sm:py-3"
                            >
                                Стать исполнителем
                            </Link>
                        </div>
                    </div>

                    <div className="rounded-lg border border-slate-200 bg-slate-50 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="rounded-md bg-white p-5 shadow-sm dark:bg-slate-950">
                            <div className="flex items-start justify-between gap-4">
                                <div>
                                    <p className="text-sm font-medium text-slate-500 dark:text-slate-400">Заказ в работе</p>
                                    <h2 className="mt-2 text-2xl font-semibold text-slate-950 dark:text-white">Лендинг для онлайн-школы</h2>
                                </div>
                                <span className="rounded-md bg-emerald-50 px-3 py-1 text-sm font-semibold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200">
                                    В срок
                                </span>
                            </div>
                            <div className="mt-6 grid grid-cols-3 gap-3">
                                {['Бриф', 'Дизайн', 'Верстка'].map((item, index) => (
                                    <div key={item} className="rounded-md border border-slate-200 p-3 dark:border-slate-800">
                                        <p className="text-xs font-medium text-slate-500 dark:text-slate-400">Этап {index + 1}</p>
                                        <p className="mt-1 text-sm font-semibold text-slate-900 dark:text-slate-100">{item}</p>
                                    </div>
                                ))}
                            </div>
                            <div className="mt-6 rounded-md border border-blue-100 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950/70">
                                <p className="text-sm font-medium text-blue-900 dark:text-blue-100">Рабочая область</p>
                                <p className="mt-1 text-sm leading-6 text-blue-800 dark:text-blue-200">
                                    Статусы, чат, файлы и решения по заказу остаются внутри платформы.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div className="mb-8 max-w-2xl">
                    <p className="text-sm font-semibold uppercase text-blue-700">Преимущества</p>
                    <h2 className="mt-2 text-3xl font-semibold text-slate-950 dark:text-white">Что делает Таскору удобной для рынка услуг</h2>
                </div>
                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    {advantages.map(({ Icon, ...item }) => (
                        <article key={item.title} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <span className="grid h-11 w-11 place-items-center rounded-md bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200">
                                <Icon aria-hidden="true" className="h-5 w-5" strokeWidth={2} />
                            </span>
                            <h3 className="mt-5 text-lg font-semibold text-slate-950 dark:text-white">{item.title}</h3>
                            <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{item.text}</p>
                        </article>
                    ))}
                </div>
            </section>

            <section className="border-y border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                        <div>
                            <p className="text-sm font-semibold uppercase text-blue-700">Категории</p>
                            <h2 className="mt-2 text-3xl font-semibold text-slate-950 dark:text-white">Стартовые направления MVP</h2>
                        </div>
                        <Link href="/catalog" className="text-sm font-semibold text-blue-700 hover:text-blue-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:text-blue-300 dark:hover:text-blue-200 dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950">
                            Перейти в каталог
                        </Link>
                    </div>
                    <div className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        {categories.map((category) => (
                            <Link
                                key={category.slug}
                                href={category.url}
                                className="rounded-lg border border-slate-200 bg-slate-50 p-5 transition hover:border-blue-200 hover:bg-blue-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-slate-700 dark:bg-slate-900 dark:hover:border-blue-700 dark:hover:bg-slate-800 dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950"
                            >
                                <p className="text-base font-semibold text-slate-950 dark:text-slate-100 dark:hover:text-white">{category.name}</p>
                                <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">{category.description}</p>
                            </Link>
                        ))}
                    </div>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Популярные услуги</p>
                        <h2 className="mt-2 text-3xl font-semibold text-slate-950 dark:text-white">Примеры опубликованных услуг</h2>
                    </div>
                    <Link href="/catalog" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                        Смотреть все услуги
                    </Link>
                </div>
                <div className="mt-8 grid gap-4 lg:grid-cols-3">
                    {services.map((service) => (
                        <ServiceCard key={service.id} service={service} />
                    ))}
                </div>
            </section>

            <section className="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8">
                <div>
                    <p className="text-sm font-semibold uppercase text-blue-700">Как работает сервис</p>
                    <h2 className="mt-2 text-3xl font-semibold text-slate-950 dark:text-white">Простой процесс без внешних договоренностей</h2>
                    <p className="mt-4 text-sm leading-6 text-slate-600 dark:text-slate-300">
                        MVP строится вокруг понятного заказа: условия фиксируются в системе, общение идет в чате, а ключевые действия сохраняются в истории.
                    </p>
                </div>
                <div className="space-y-3">
                    {steps.map((step, index) => (
                        <div key={step} className="flex gap-4 rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-md bg-slate-950 text-sm font-semibold text-white dark:bg-blue-600">
                                {index + 1}
                            </span>
                            <p className="self-center text-base font-medium text-slate-900 dark:text-slate-100">{step}</p>
                        </div>
                    ))}
                </div>
            </section>

            <section className="bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                    <div className="grid gap-8 text-white lg:grid-cols-[0.9fr_1.1fr] lg:items-center">
                        <div>
                            <p className="text-sm font-semibold uppercase text-blue-300">Доверие</p>
                            <h2 className="mt-2 text-3xl font-semibold">Контроль качества с первого MVP</h2>
                            <p className="mt-4 text-sm leading-6 text-slate-300">
                                Таскора проектируется вокруг прозрачных правил, модерации и сохранения истории заказа внутри платформы.
                            </p>
                        </div>
                        <div className="grid gap-6 md:grid-cols-3">
                            <div>
                                <p className="text-3xl font-semibold">MVP</p>
                                <p className="mt-2 text-sm text-slate-300">Без платежного шлюза и ИИ-функций</p>
                            </div>
                            <div>
                                <p className="text-3xl font-semibold">2 сценария</p>
                                <p className="mt-2 text-sm text-slate-300">Готовая услуга и индивидуальное задание</p>
                            </div>
                            <div>
                                <p className="text-3xl font-semibold">Контроль</p>
                                <p className="mt-2 text-sm text-slate-300">Модерация и защита от передачи контактов</p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
