import { Head, Link, usePage } from '@inertiajs/react';
import PublicLayout from '../Layouts/PublicLayout';

const copy = {
    403: {
        title: 'Доступ запрещен',
        text: 'У вас нет прав для просмотра этой страницы или выполнения этого действия.',
        tone: 'bg-amber-50 text-amber-800 ring-amber-200',
    },
    404: {
        title: 'Страница не найдена',
        text: 'Ссылка устарела, страница была перенесена или адрес набран с ошибкой.',
        tone: 'bg-blue-50 text-blue-800 ring-blue-200',
    },
    429: {
        title: 'Слишком много запросов',
        text: 'Действие временно ограничено, чтобы защитить сервис от перегрузки. Попробуйте повторить позже.',
        tone: 'bg-red-50 text-red-800 ring-red-200',
    },
    500: {
        title: 'Что-то пошло не так',
        text: 'Мы не показываем технические детали ошибки. Вернитесь на главную или в кабинет и попробуйте продолжить работу.',
        tone: 'bg-slate-100 text-slate-800 ring-slate-200',
    },
    503: {
        title: 'Сервис временно недоступен',
        text: 'Таскора обновляется или перегружена. Попробуйте открыть страницу немного позже.',
        tone: 'bg-slate-100 text-slate-800 ring-slate-200',
    },
};

export default function Error({ status }) {
    const { auth } = usePage().props;
    const page = copy[status] ?? copy[500];
    const dashboardUrl = auth?.dashboard_url;

    return (
        <PublicLayout>
            <Head title={page.title} />

            <section className="mx-auto flex min-h-[calc(100vh-73px)] max-w-5xl items-center px-4 py-16 sm:px-6 lg:px-8">
                <div className="w-full rounded-lg border border-slate-200 bg-white p-6 shadow-sm sm:p-8 lg:p-10">
                    <div className="flex flex-col gap-8 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <span className={`inline-flex rounded-md px-3 py-1 text-sm font-semibold ring-1 ${page.tone}`}>
                                Ошибка {status}
                            </span>
                            <h1 className="mt-6 max-w-3xl text-3xl font-semibold tracking-normal text-slate-950 sm:text-5xl">
                                {page.title}
                            </h1>
                            <p className="mt-4 max-w-2xl text-base leading-7 text-slate-600">{page.text}</p>
                        </div>

                        <div className="grid gap-3 sm:flex sm:shrink-0">
                            <Link
                                href="/"
                                className="inline-flex justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                            >
                                На главную
                            </Link>
                            {dashboardUrl && (
                                <Link
                                    href={dashboardUrl}
                                    className="inline-flex justify-center rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700"
                                >
                                    В кабинет
                                </Link>
                            )}
                        </div>
                    </div>
                </div>
            </section>
        </PublicLayout>
    );
}
