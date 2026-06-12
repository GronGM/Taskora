import { useTheme } from './ThemeProvider';

export default function ThemeToggle({ className = '' }) {
    const { preference, setTheme, options, resolvedTheme } = useTheme();

    return (
        <label
            data-testid="theme-toggle"
            className={`inline-flex min-w-0 items-center gap-2 text-sm font-medium text-slate-600 dark:text-slate-300 ${className}`}
        >
            <span className="sr-only">Тема оформления</span>
            <span className="hidden text-xs font-semibold uppercase text-slate-500 dark:text-slate-400 sm:inline">
                Тема
            </span>
            <select
                value={preference}
                onChange={(event) => setTheme(event.target.value)}
                data-testid="theme-toggle-select"
                aria-label={`Тема оформления. Сейчас: ${options.find((option) => option.value === preference)?.label ?? 'Светлая'}, отображается ${resolvedTheme === 'dark' ? 'темная' : 'светлая'} тема`}
                className="h-9 max-w-[11.5rem] rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-semibold text-slate-800 shadow-sm outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-950 sm:text-sm"
            >
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
        </label>
    );
}
