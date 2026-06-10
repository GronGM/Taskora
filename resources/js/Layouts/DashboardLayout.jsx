import { Link, usePage } from '@inertiajs/react';

const roleLabels = {
    customer: 'Заказчик',
    performer: 'Исполнитель',
    moderator: 'Модератор',
    admin: 'Администратор',
};

export default function DashboardLayout({ children }) {
    const { auth, notifications = {} } = usePage().props;
    const user = auth?.user;
    const canReview = user?.role === 'moderator' || user?.role === 'admin';
    const unreadCount = notifications?.unread_count ?? 0;

    return (
        <div className="min-h-screen bg-slate-50 text-slate-950">
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-7xl flex-col gap-4 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between gap-4">
                        <Link href="/" className="flex items-center gap-3" aria-label="Таскора">
                            <span className="grid h-10 w-10 place-items-center rounded-md bg-blue-600" aria-hidden="true">
                                <span className="h-4 w-4 rounded-sm bg-white" />
                            </span>
                            <span className="text-xl font-semibold tracking-normal">Таскора</span>
                        </Link>

                        <div className="text-right sm:hidden">
                            <p className="text-sm font-semibold text-slate-900">{user?.name}</p>
                            <p className="text-xs text-slate-500">{roleLabels[user?.role] ?? 'Пользователь'}</p>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2 sm:justify-end">
                        <div className="hidden text-right md:block">
                            <p className="text-sm font-semibold text-slate-900">{user?.name}</p>
                            <p className="text-xs text-slate-500">{roleLabels[user?.role] ?? 'Пользователь'}</p>
                        </div>
                        <Link
                            href="/dashboard"
                            className="rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950"
                        >
                            Кабинет
                        </Link>
                        <Link
                            href="/notifications"
                            className="relative rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950"
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
                        {user?.role === 'customer' && (
                            <>
                                <Link
                                    href="/customer/tasks"
                                    className="hidden rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 md:inline-flex"
                                >
                                    Мои задания
                                </Link>
                                <Link
                                    href="/customer/orders"
                                    className="hidden rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:inline-flex"
                                >
                                    Мои заказы
                                </Link>
                            </>
                        )}
                        {user?.role === 'performer' && (
                            <>
                                <Link
                                    href="/performer/services"
                                    className="hidden rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 md:inline-flex"
                                >
                                    Мои услуги
                                </Link>
                                <Link
                                    href="/performer/offers"
                                    className="hidden rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:inline-flex"
                                >
                                    Мои отклики
                                </Link>
                                <Link
                                    href="/performer/orders"
                                    className="hidden rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:inline-flex"
                                >
                                    Мои заказы
                                </Link>
                            </>
                        )}
                        {canReview && (
                            <>
                                <Link
                                    href="/moderator/services"
                                    className="hidden rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 md:inline-flex"
                                >
                                    Модерация услуг
                                </Link>
                                <Link
                                    href="/moderator/moderation-flags"
                                    className="hidden rounded-md px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 hover:text-slate-950 lg:inline-flex"
                                >
                                    Флаги
                                </Link>
                            </>
                        )}
                        <Link
                            href="/logout"
                            method="post"
                            as="button"
                            className="rounded-md bg-slate-950 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                        >
                            Выйти
                        </Link>
                    </div>
                </div>
            </header>

            <main>{children}</main>
        </div>
    );
}
