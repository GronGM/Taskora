import { Link, usePage } from '@inertiajs/react';

const roleLabels = {
    customer: 'Заказчик',
    performer: 'Исполнитель',
    moderator: 'Модератор',
    admin: 'Администратор',
};

export default function DashboardLayout({ children }) {
    const { auth } = usePage().props;
    const user = auth?.user;

    return (
        <div className="min-h-screen bg-slate-50 text-slate-950">
            <header className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <Link href="/" className="flex items-center gap-3" aria-label="Таскора">
                        <span className="grid h-10 w-10 place-items-center rounded-md bg-blue-600" aria-hidden="true">
                            <span className="h-4 w-4 rounded-sm bg-white" />
                        </span>
                        <span className="text-xl font-semibold tracking-normal">Таскора</span>
                    </Link>

                    <div className="flex items-center gap-3">
                        <div className="hidden text-right sm:block">
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
