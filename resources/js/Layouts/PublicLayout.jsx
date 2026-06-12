import { useState } from 'react';
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
    const { auth, messages = {} } = usePage().props;
    const user = auth?.user;
    const dashboardUrl = auth?.dashboard_url ?? '/dashboard';
    const unreadMessagesCount = messages?.unread_count ?? 0;
    const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false);

    return (
        <div className="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-slate-100">
            <TestModeBanner />
            <header className="border-b border-slate-200 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-950/90">
                <div className="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between gap-2 sm:gap-3">
                        <Link href="/" className="flex min-w-0 items-center gap-2 sm:gap-3" aria-label="Таскора">
                            <span className="grid h-10 w-10 place-items-center rounded-md bg-blue-600" aria-hidden="true">
                                <span className="taskora-logo-mark h-4 w-4 rounded-sm bg-white" />
                            </span>
                            <span className="truncate text-lg font-semibold tracking-normal text-slate-950 dark:text-white sm:text-xl">Таскора</span>
                        </Link>

                        <nav className="hidden items-center gap-1 lg:flex">
                            {navigation.map((item) => (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white"
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </nav>

                        <div className="flex min-w-0 shrink-0 items-center gap-1.5 sm:gap-2">
                            <ThemeToggle className="shrink-0" />

                            {user ? (
                                <div className="hidden shrink-0 items-center gap-2 lg:flex">
                                    <Link
                                        href={dashboardUrl}
                                        className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white"
                                    >
                                        Кабинет
                                    </Link>
                                    <Link
                                        href="/messages"
                                        className="relative rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white"
                                        aria-label={unreadMessagesCount > 0 ? `Сообщения: ${unreadMessagesCount} непрочитанных` : 'Сообщения'}
                                    >
                                        Сообщения
                                        {unreadMessagesCount > 0 && (
                                            <span className="absolute -right-1 -top-1 min-w-5 rounded-full bg-blue-600 px-1.5 py-0.5 text-center text-xs font-semibold text-white">
                                                {unreadMessagesCount > 99 ? '99+' : unreadMessagesCount}
                                            </span>
                                        )}
                                    </Link>
                                    <Link
                                        href="/logout"
                                        method="post"
                                        as="button"
                                        className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                                    >
                                        Выйти
                                    </Link>
                                </div>
                            ) : (
                                <div className="hidden shrink-0 items-center gap-2 lg:flex">
                                    <Link
                                        href="/login"
                                        className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white"
                                    >
                                        Войти
                                    </Link>
                                    <Link
                                        href="/register"
                                        className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                                    >
                                        Регистрация
                                    </Link>
                                </div>
                            )}

                            <button
                                type="button"
                                aria-controls="public-mobile-menu"
                                aria-expanded={isMobileMenuOpen}
                                onClick={() => setIsMobileMenuOpen((isOpen) => !isOpen)}
                                className="inline-flex h-9 items-center rounded-md border border-slate-300 bg-white px-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-blue-300 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-blue-600 dark:hover:text-blue-200 dark:focus:ring-blue-950 lg:hidden"
                            >
                                {isMobileMenuOpen ? 'Скрыть' : 'Меню'}
                            </button>
                        </div>
                    </div>

                    <div
                        id="public-mobile-menu"
                        data-testid="public-mobile-menu"
                        className={`${isMobileMenuOpen ? 'grid' : 'hidden'} mt-4 gap-3 rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900 lg:hidden`}
                    >
                        <nav className="grid gap-2" aria-label="Основная навигация">
                            {navigation.map((item) => (
                                <Link
                                    key={item.label}
                                    href={item.href}
                                    className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-blue-950 dark:hover:text-blue-200"
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </nav>
                        {user ? (
                            <div className="grid gap-2 border-t border-slate-200 pt-3 dark:border-slate-800">
                                <Link
                                    href={dashboardUrl}
                                    className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-blue-950 dark:hover:text-blue-200"
                                >
                                    Кабинет
                                </Link>
                                <Link
                                    href="/messages"
                                    className="flex items-center justify-between rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-blue-950 dark:hover:text-blue-200"
                                >
                                    <span>Сообщения</span>
                                    {unreadMessagesCount > 0 && (
                                        <span className="min-w-5 rounded-full bg-blue-600 px-1.5 py-0.5 text-center text-xs font-semibold text-white">
                                            {unreadMessagesCount > 99 ? '99+' : unreadMessagesCount}
                                        </span>
                                    )}
                                </Link>
                                <Link
                                    href="/logout"
                                    method="post"
                                    as="button"
                                    className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                                >
                                    Выйти
                                </Link>
                            </div>
                        ) : (
                            <div className="grid gap-2 border-t border-slate-200 pt-3 dark:border-slate-800">
                                <Link
                                    href="/login"
                                    className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-blue-950 dark:hover:text-blue-200"
                                >
                                    Войти
                                </Link>
                                <Link
                                    href="/register"
                                    className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                                >
                                    Регистрация
                                </Link>
                            </div>
                        )}
                    </div>
                </div>
            </header>

            <main>{children}</main>
        </div>
    );
}
