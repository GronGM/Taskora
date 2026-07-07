import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

const customerSteps = [
    { title: 'Опишите задачу', text: 'Мастер из трех шагов: что нужно сделать, бюджет (точный, диапазон или по договоренности) и срок проверки работы.' },
    { title: 'Получите отклики', text: 'Исполнители предложат цену и срок. Смотрите уровень, отзывы и портфолио каждого.' },
    { title: 'Выберите исполнителя', text: 'Сравните отклики по цене, срокам, уровню и отзывам. Условия заказа фиксируются в его карточке — обе стороны видят одно и то же.' },
    { title: 'Примите работу', text: 'Проверьте результат в течение выбранного срока: примите, запросите доработку или откройте спор. Приняли — исполнитель получает оплату сразу.' },
];

const performerSteps = [
    { title: 'Создайте услугу или откликайтесь', text: 'Услуга-витрина приводит заказчиков сама, отклики на бирже дают быстрые заказы. Новички получают равные шансы — продвижение не продается.' },
    { title: 'Работайте внутри платформы', text: 'Чат, файлы и статусы — в рабочей области заказа. Вся история сохраняется и защищает вас в случае спора.' },
    { title: 'Сдайте работу', text: 'Заказчик проверяет результат в заранее известный вам срок. Вся история заказа сохранена и защищает вас при споре.' },
    { title: 'Завершите заказ', text: 'После приемки заказ закрывается, заказчик оставляет отзыв. Выполненные заказы растят ваш уровень — от Новичка до Эксперта.' },
];

export default function HowItWorks({ feePercent, reviewHold }) {
    return (
        <PublicLayout>
            <Head title="Как это работает">
                <meta
                    name="description"
                    content="Как работает Таскора: прозрачная комиссия, фиксация условий заказа, споры с модератором и честные уровни исполнителей."
                />
            </Head>

            <section className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Справка</p>
                    <h1 className="mt-3 max-w-3xl text-4xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-5xl">
                        Как работает Таскора
                    </h1>
                    <p className="mt-5 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">
                        Простая идея: заказчик и исполнитель договариваются внутри платформы, условия заказа зафиксированы,
                        комиссия {feePercent}% видна до подтверждения заказа. Никаких скрытых условий.
                    </p>
                </div>
            </section>

            <section className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="grid gap-10 lg:grid-cols-2">
                    <div>
                        <h2 className="text-2xl font-semibold text-slate-950 dark:text-white">Для заказчика</h2>
                        <ol className="mt-6 space-y-4">
                            {customerSteps.map((step, index) => (
                                <li key={step.title} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                    <p className="text-sm font-semibold text-blue-700 dark:text-blue-300">Шаг {index + 1}</p>
                                    <h3 className="mt-1 text-lg font-semibold text-slate-950 dark:text-slate-100">{step.title}</h3>
                                    <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">{step.text}</p>
                                </li>
                            ))}
                        </ol>
                    </div>
                    <div>
                        <h2 className="text-2xl font-semibold text-slate-950 dark:text-white">Для исполнителя</h2>
                        <ol className="mt-6 space-y-4">
                            {performerSteps.map((step, index) => (
                                <li key={step.title} className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                    <p className="text-sm font-semibold text-blue-700 dark:text-blue-300">Шаг {index + 1}</p>
                                    <h3 className="mt-1 text-lg font-semibold text-slate-950 dark:text-slate-100">{step.title}</h3>
                                    <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-400">{step.text}</p>
                                </li>
                            ))}
                        </ol>
                    </div>
                </div>

                <div className="mt-12 rounded-lg border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950">
                    <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">Как защищен заказ</h2>
                    <div className="mt-4 grid gap-4 text-sm leading-6 text-slate-700 dark:text-slate-300 lg:grid-cols-3">
                        <p>
                            <span className="font-semibold">Фиксация условий.</span> Предмет, цена и сроки закреплены в карточке заказа. Переписка и файлы хранятся внутри платформы.
                        </p>
                        <p>
                            <span className="font-semibold">Срок проверки.</span> От {reviewHold.min} до {reviewHold.max} дней, выбирает заказчик (по умолчанию {reviewHold.default}). Исполнители видят срок заранее.
                        </p>
                        <p>
                            <span className="font-semibold">Споры.</span> Если что-то пошло не так — спор рассматривает модератор: возврат, выплата или доработка. Переписка и файлы сделки служат доказательствами.
                        </p>
                    </div>
                </div>

                <div className="mt-10 flex flex-col gap-3 sm:flex-row">
                    <Link href="/customer/tasks/create" className="inline-flex justify-center rounded-md bg-blue-600 px-6 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                        Разместить задание
                    </Link>
                    <Link href="/catalog" className="inline-flex justify-center rounded-md border border-slate-300 bg-white px-6 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800">
                        Найти услугу
                    </Link>
                    <Link href="/faq" className="inline-flex justify-center rounded-md px-6 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-50 dark:text-blue-300 dark:hover:bg-blue-950">
                        Вопросы и ответы
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
