import { Link, usePage } from '@inertiajs/react';
import { Wallet } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';

const currency = new Intl.NumberFormat('ru-RU');

const toneClasses = {
    emerald: 'text-emerald-600 dark:text-emerald-400',
    amber: 'text-amber-600 dark:text-amber-400',
};

export function WalletBadge() {
    const { account } = usePage().props;
    const [open, setOpen] = useState(false);
    const containerRef = useRef(null);

    useEffect(() => {
        const close = (event) => {
            if (containerRef.current && !containerRef.current.contains(event.target)) {
                setOpen(false);
            }
        };

        document.addEventListener('click', close);

        return () => document.removeEventListener('click', close);
    }, []);

    const wallet = account?.wallet;

    if (!wallet) {
        return null;
    }

    return (
        <div ref={containerRef} className="relative">
            <button
                type="button"
                onClick={() => setOpen((value) => !value)}
                aria-expanded={open}
                aria-label={`Баланс: ${currency.format(wallet.total)} рублей`}
                className="inline-flex items-center gap-1.5 rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-800 transition hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:border-blue-700 dark:hover:bg-blue-950 dark:hover:text-blue-200"
            >
                <Wallet aria-hidden="true" className="h-4 w-4" />
                <span className="whitespace-nowrap">{currency.format(wallet.total)} ₽</span>
            </button>

            {open && (
                <div className="absolute right-0 top-full z-50 mt-2 w-72 rounded-lg border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900">
                    {wallet.rows.map((row) => (
                        <div key={row.label} className="flex items-baseline justify-between gap-3 rounded-md px-3 py-2.5 text-sm">
                            <span className="text-slate-600 dark:text-slate-300">{row.label}</span>
                            <span className={`font-semibold ${toneClasses[row.tone] ?? 'text-slate-900 dark:text-slate-100'}`}>
                                {currency.format(row.amount)} ₽
                            </span>
                        </div>
                    ))}
                    <Link
                        href={wallet.url}
                        className="mt-1 block rounded-md border-t border-slate-100 px-3 py-2.5 text-sm font-semibold text-blue-700 hover:bg-blue-50 dark:border-slate-800 dark:text-blue-300 dark:hover:bg-blue-950"
                        onClick={() => setOpen(false)}
                    >
                        Подробнее
                    </Link>
                </div>
            )}
        </div>
    );
}

export function HeaderAvatar({ name }) {
    const { account } = usePage().props;

    if (!account) {
        return null;
    }

    return (
        <Link
            href="/settings"
            aria-label="Настройки аккаунта"
            title="Настройки аккаунта"
            className="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-full bg-blue-100 text-sm font-semibold text-blue-700 ring-1 ring-slate-200 transition hover:ring-blue-400 dark:bg-blue-950 dark:text-blue-300 dark:ring-slate-700"
        >
            {account.avatar_url
                ? <img src={account.avatar_url} alt="" className="h-full w-full object-cover" />
                : (name ?? '?').slice(0, 1).toUpperCase()}
        </Link>
    );
}
