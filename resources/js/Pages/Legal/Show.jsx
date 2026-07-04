import { Head } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function LegalShow({ title, updatedAt, sections = [] }) {
    return (
        <PublicLayout>
            <Head title={title}>
                <meta name="description" content={`${title} сервиса Таскора: условия использования платформы, безопасная сделка и защита пользователей.`} />
            </Head>

            <section className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div className="mx-auto max-w-4xl px-4 py-12 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Документы</p>
                    <h1 className="mt-3 text-3xl font-semibold tracking-tight text-slate-950 dark:text-white sm:text-4xl">{title}</h1>
                    <p className="mt-3 text-sm text-slate-500 dark:text-slate-400">Редакция от {updatedAt}</p>
                </div>
            </section>

            <section className="mx-auto max-w-4xl px-4 py-10 sm:px-6 lg:px-8">
                <ol className="space-y-8">
                    {sections.map((section, index) => (
                        <li key={section.title}>
                            <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">
                                {index + 1}. {section.title}
                            </h2>
                            <div className="mt-3 space-y-3">
                                {section.items.map((paragraph, paragraphIndex) => (
                                    <p key={paragraphIndex} className="text-sm leading-7 text-slate-700 dark:text-slate-300">
                                        {index + 1}.{paragraphIndex + 1}. {paragraph}
                                    </p>
                                ))}
                            </div>
                        </li>
                    ))}
                </ol>
            </section>
        </PublicLayout>
    );
}
