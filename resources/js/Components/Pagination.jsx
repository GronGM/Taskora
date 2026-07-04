import { Link } from '@inertiajs/react';

const buttonClass = 'rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800';

export default function Pagination({ pagination, label = 'Пагинация' }) {
    if (!pagination || pagination.last_page <= 1) {
        return null;
    }

    return (
        <nav aria-label={label} className="mt-8 flex items-center justify-between gap-4">
            <div>{pagination.prev_page_url && <Link href={pagination.prev_page_url} preserveScroll className={buttonClass}>Назад</Link>}</div>
            <span className="text-sm text-slate-500 dark:text-slate-400">
                Страница {pagination.current_page} из {pagination.last_page}
            </span>
            <div>{pagination.next_page_url && <Link href={pagination.next_page_url} preserveScroll className={buttonClass}>Вперед</Link>}</div>
        </nav>
    );
}
