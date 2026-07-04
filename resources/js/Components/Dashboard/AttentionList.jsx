import { Link } from '@inertiajs/react';

export default function AttentionList({ title, description, items, actionLabel }) {
    if (!items || items.length === 0) {
        return null;
    }

    return (
        <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-5 dark:border-amber-800 dark:bg-amber-950">
            <h2 className="text-base font-semibold text-slate-950 dark:text-slate-100">{title}</h2>
            {description && <p className="mt-1 text-sm text-slate-700 dark:text-slate-300">{description}</p>}
            <ul className="mt-3 divide-y divide-amber-200/70 dark:divide-amber-900">
                {items.map((item) => (
                    <li key={item.id} className="flex flex-wrap items-center justify-between gap-2 py-2.5">
                        <span className="min-w-0 flex-1 truncate text-sm font-medium text-slate-900 dark:text-slate-200">
                            {item.title}
                            {item.offers_count ? <span className="ml-2 rounded-md bg-white px-2 py-0.5 text-xs font-semibold text-amber-700 dark:bg-amber-900 dark:text-amber-200">{item.offers_count} откл.</span> : null}
                            {item.review_hold_until ? <span className="ml-2 text-xs text-slate-500 dark:text-slate-400">автоприемка {item.review_hold_until}</span> : null}
                        </span>
                        <Link href={item.url} className="text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200">
                            {actionLabel}
                        </Link>
                    </li>
                ))}
            </ul>
        </div>
    );
}
