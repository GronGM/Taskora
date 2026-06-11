import { Link, usePage } from '@inertiajs/react';
import ThemeToggle from '../Components/Theme/ThemeToggle';
import TestModeBanner from '../Components/TestModeBanner';

const navigation = [
    { label: 'Главная', href: '/' },
    { label: 'Каталог', href: '/catalog' },
    { label: 'Задания', href: '/tasks' },
    { label: 'Исполнители', href: '/performers' },
];

export default function PublicLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth?.user;
    const dashboardUrl = auth?.dashboard_url ?? '/dashboard';

    return (
        <div className="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-slate-100">
            <TestModeBanner />
            <header className="border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
                <div className="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between gap-4">
                        <Link href="/" className="flex items-center gap-3" aria-label="Таскора">
                            <span className="grid h-10 w-10 place-items-center rounded-md bg-blue-600" aria-hidden="true">
                                <span className="taskora-logo-mark h-4 w-4 rounded-sm bg-white" />
                            </span>
                            <span className="text-xl font-semibold tracking-normal text-slate-950">Таскора</span>
                        </Link>

                        <nav className="hidden items-center gap-1 md:flex">
                            {navigation.map((item) => (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950"
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </nav>

                        <ThemeToggle className="hidden lg:inline-flex" />

                        {user ? (
                            <div className="flex shrink-0 items-center gap-2">
                                <Link
                                    href={dashboardUrl}
                                    className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950"
                                >
                                    Кабинет
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                >
                                    Выйти
                                </Link>
                            </div>
                        ) : (
                            <div className="flex shrink-0 items-center gap-2">
                                <Link
                                    href="/login"
                                    className="hidden rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 sm:inline-flex"
                                >
                                    Войти
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                                >
                                    Регистрация
                                </Link>
                            </div>
                        )}
                    </div>

                    <div className="mt-4 flex items-center gap-3 md:hidden">
                        <nav className="flex min-w-0 flex-1 gap-2 overflow-x-auto pb-1" aria-label="Основная навигация">
                            {navigation.map((item) => (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className="shrink-0 rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-blue-950 dark:hover:text-blue-200"
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </nav>
                        <ThemeToggle className="shrink-0" />
                    </div>
                </div>
            </header>

            <main>{children}</main>
        </div>
    );
}
