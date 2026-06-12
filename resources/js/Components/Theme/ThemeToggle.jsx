import { Moon, Sun } from 'lucide-react';
import { useTheme } from './ThemeProvider';

export default function ThemeToggle({ className = '' }) {
    const { resolvedTheme, setTheme } = useTheme();
    const isDark = resolvedTheme === 'dark';
    const label = isDark ? 'Включить светлую тему' : 'Включить темную тему';

    return (
        <span
            data-testid="theme-toggle"
            className={`inline-flex min-w-0 items-center ${className}`}
        >
            <button
                type="button"
                role="switch"
                aria-checked={isDark}
                aria-label={label}
                data-testid="theme-toggle-button"
                data-theme-toggle="switch"
                onClick={() => setTheme(isDark ? 'light' : 'dark')}
                className="group relative inline-flex h-10 w-[4.5rem] shrink-0 items-center justify-center rounded-full text-slate-500 transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:text-slate-300 dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950"
            >
                <span className="sr-only">{label}</span>
                <span
                    aria-hidden="true"
                    className="absolute inset-x-1 top-1/2 h-9 -translate-y-1/2 rounded-full border border-slate-300 bg-slate-100 shadow-sm transition group-hover:border-blue-300 group-hover:bg-slate-200 dark:border-slate-700 dark:bg-slate-900 dark:group-hover:border-blue-600 dark:group-hover:bg-slate-800"
                />
                <Sun
                    aria-hidden="true"
                    className="absolute left-3 h-4 w-4 text-amber-500 transition dark:text-slate-500"
                    strokeWidth={2.2}
                />
                <Moon
                    aria-hidden="true"
                    className="absolute right-3 h-4 w-4 text-slate-400 transition dark:text-blue-200"
                    strokeWidth={2.2}
                />
                <span
                    aria-hidden="true"
                    className={`absolute left-1.5 grid h-7 w-7 place-items-center rounded-full bg-white text-amber-500 shadow-sm transition-transform duration-200 dark:bg-blue-500 dark:text-white ${
                        isDark ? 'translate-x-8' : 'translate-x-0'
                    }`}
                >
                    {isDark ? <Moon className="h-4 w-4" strokeWidth={2.2} /> : <Sun className="h-4 w-4" strokeWidth={2.2} />}
                </span>
            </button>
        </span>
    );
}
