import { Link, usePage } from '@inertiajs/react';
import ThemeToggle from '../Components/Theme/ThemeToggle';
import TestModeBanner from '../Components/TestModeBanner';

const roleLabels = {
    customer: 'Заказчик',
    performer: 'Исполнитель',
    moderator: 'Модератор',
    admin: 'Администратор',
};

const customerLinks = [
    { label: 'Мои задания', href: '/customer/tasks' },
    { label: 'Мои заказы', href: '/customer/orders' },
    { label: 'Мои отзывы', href: '/customer/reviews' },
];

const performerLinks = [
    { label: 'Профиль', href: '/performer/profile' },
    { label: 'Портфолио', href: '/performer/portfolio' },
    { label: 'Мои услуги', href: '/performer/services' },
    { label: 'Мои отклики', href: '/performer/offers' },
    { label: 'Избранное', href: '/performer/favorites' },
    { label: 'Мои заказы', href: '/performer/orders' },
    { label: 'Финансы', href: '/performer/finance' },
];

const moderatorLinks = [
    { label: 'Профили', href: '/moderator/performer-profiles' },
    { label: 'Модерация услуг', href: '/moderator/services' },
    { label: 'Флаги', href: '/moderator/moderation-flags' },
];

const dashboardLinkClass =
    'rounded-md px-3 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white';

function getRoleNavigation(user, canReview) {
    const links = [];

    if (user?.role === 'customer') {
        links.push(...customerLinks);
    }

    if (user?.role === 'performer') {
        links.push(...performerLinks);
    }

    if (user?.role === 'admin') {
        links.push({ label: 'Финансы', href: '/admin/finance' });
    }

    if (canReview) {
        links.push(...moderatorLinks);
    }

    return links;
}

export default function DashboardLayout({ children }) {
    const { auth, notifications = {} } = usePage().props;
    const user = auth?.user;
    const canReview = user?.role === 'moderator' || user?.role === 'admin';
    const unreadCount = notifications?.unread_count ?? 0;
    const roleNavigation = getRoleNavigation(user, canReview);

    return (
        <div className="min-h-screen bg-slate-50 text-slate-950 dark:bg-slate-950 dark:text-slate-100">
            <TestModeBanner />
            <header className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <div
                        data-testid="dashboard-top-bar"
                        className="flex min-w-0 flex-col gap-3 md:flex-row md:items-center md:justify-between"
                    >
                        <div className="flex min-w-0 items-center justify-between gap-3 md:justify-start">
                            <Link href="/" className="flex min-w-0 items-center gap-3" aria-label="Таскора">
                                <span className="grid h-10 w-10 shrink-0 place-items-center rounded-md bg-blue-600" aria-hidden="true">
                                    <span className="taskora-logo-mark h-4 w-4 rounded-sm bg-white" />
                                </span>
                                <span className="truncate text-xl font-semibold tracking-normal text-slate-950 dark:text-white">Таскора</span>
                            </Link>

                            <div className="min-w-0 text-right md:hidden">
                                <p className="truncate text-sm font-semibold text-slate-900 dark:text-white">{user?.name}</p>
                                <p className="text-xs text-slate-500 dark:text-slate-400">{roleLabels[user?.role] ?? 'Пользователь'}</p>
                            </div>
                        </div>

                        <div
                            data-testid="dashboard-actions"
                            className="flex min-w-0 flex-wrap items-center gap-2 md:justify-end"
                        >
                            <div className="hidden min-w-0 text-right md:block">
                                <p className="max-w-52 truncate text-sm font-semibold text-slate-900 dark:text-white">{user?.name}</p>
                                <p className="text-xs text-slate-500 dark:text-slate-400">{roleLabels[user?.role] ?? 'Пользователь'}</p>
                            </div>
                            <ThemeToggle className="shrink-0" />
                            <Link
                                href="/dashboard"
                                className={dashboardLinkClass}
                            >
                                Кабинет
                            </Link>
                            <Link
                                href="/notifications"
                                className={`relative ${dashboardLinkClass}`}
                                aria-label={unreadCount > 0 ? `Уведомления: ${unreadCount} непрочитанных` : 'Уведомления'}
                            >
                                <span className="hidden sm:inline">Уведомления</span>
                                <span className="sm:hidden">Увед.</span>
                                {unreadCount > 0 && (
                                    <span className="absolute -right-1 -top-1 min-w-5 rounded-full bg-red-600 px-1.5 py-0.5 text-center text-xs font-semibold text-white">
                                        {unreadCount > 99 ? '99+' : unreadCount}
                                    </span>
                                )}
                            </Link>
                            <Link
                                href="/logout"
                                method="post"
                                as="button"
                                className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200"
                            >
                                Выйти
                            </Link>
                        </div>
                    </div>

                    {roleNavigation.length > 0 && (
                        <nav
                            data-testid="dashboard-role-nav"
                            aria-label="Разделы кабинета"
                            className="mt-3 hidden flex-wrap items-center gap-2 border-t border-slate-200 pt-3 dark:border-slate-800 md:flex"
                        >
                            {roleNavigation.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    className="rounded-md px-2.5 py-1.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100 hover:text-slate-950 dark:text-slate-300 dark:hover:bg-slate-900 dark:hover:text-white"
                                >
                                    {item.label}
                                </Link>
                            ))}
                        </nav>
                    )}
                </div>
            </header>

            <main>{children}</main>
        </div>
    );
}
