import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function Faq({ items = [] }) {
    const jsonLd = {
        '@context': 'https://schema.org',
        '@type': 'FAQPage',
        mainEntity: items.map((item) => ({
            '@type': 'Question',
            name: item.question,
            acceptedAnswer: { '@type': 'Answer', text: item.answer },
        })),
    };

    return (
        <PublicLayout>
            <Head title="Вопросы и ответы">
                <meta name="description" content="Ответы на частые вопросы о Таскоре: комиссия, оплата и ее удержание, споры, уровни исполнителей и отзывы." />
                <script type="application/ld+json">{JSON.stringify(jsonLd)}</script>
            </Head>

            <section className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 py-14 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Справка</p>
                    <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-5xl">Вопросы и ответы</h1>
                    <p className="mt-5 max-w-2xl text-base leading-7 text-slate-600 dark:text-slate-300">
                        Коротко о деньгах, безопасности и правилах платформы. Не нашли ответ — напишите через форму обратной связи.
                    </p>
                </div>
            </section>

            <section className="mx-auto max-w-3xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="space-y-4">
                    {items.map((item) => (
                        <details key={item.question} className="group rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            <summary className="cursor-pointer list-none text-base font-semibold text-slate-950 marker:hidden dark:text-slate-100">
                                {item.question}
                            </summary>
                            <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-400">{item.answer}</p>
                        </details>
                    ))}
                </div>

                <p className="mt-10 text-sm leading-6 text-slate-600 dark:text-slate-400">
                    Подробнее о механике платформы — на странице{' '}
                    <Link href="/how-it-works" className="font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300">Как это работает</Link>.
                </p>
            </section>
        </PublicLayout>
    );
}
