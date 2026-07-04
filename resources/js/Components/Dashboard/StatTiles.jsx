import { Link } from '@inertiajs/react';

export default function StatTiles({ tiles }) {
    const visible = tiles.filter((tile) => tile !== null);

    if (visible.length === 0) {
        return null;
    }

    return (
        <div className="mt-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
            {visible.map((tile) => (
                <Link
                    key={tile.label}
                    href={tile.href}
                    className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm transition hover:border-slate-300 hover:shadow-md dark:border-slate-800 dark:bg-slate-900 dark:hover:border-slate-700"
                >
                    <p className="text-3xl font-semibold tracking-tight text-slate-950 dark:text-slate-100">{tile.value}</p>
                    <p className="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-400">{tile.label}</p>
                    {tile.hint && <p className="mt-1 text-xs text-slate-400 dark:text-slate-500">{tile.hint}</p>}
                </Link>
            ))}
        </div>
    );
}
