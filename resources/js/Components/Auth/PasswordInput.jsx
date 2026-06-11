import { useState } from 'react';

export default function PasswordInput({
    id,
    name,
    label,
    value,
    onChange,
    autoComplete,
    error,
    required = false,
}) {
    const [visible, setVisible] = useState(false);
    const buttonLabel = visible ? 'Скрыть пароль' : 'Показать пароль';
    const errorId = `${id}-error`;

    return (
        <div>
            <label className="text-sm font-semibold text-slate-900 dark:text-slate-100" htmlFor={id}>
                {label}
            </label>
            <div className="relative mt-2">
                <input
                    id={id}
                    name={name}
                    type={visible ? 'text' : 'password'}
                    value={value}
                    onChange={onChange}
                    className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 pr-36 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-950"
                    autoComplete={autoComplete}
                    required={required}
                    aria-invalid={Boolean(error)}
                    aria-describedby={error ? errorId : undefined}
                />
                <button
                    type="button"
                    aria-label={buttonLabel}
                    onClick={() => setVisible((current) => !current)}
                    className="absolute inset-y-1.5 right-1.5 rounded border border-slate-200 bg-white px-3 text-xs font-semibold text-slate-700 shadow-sm transition hover:border-blue-200 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-100 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100 dark:hover:border-blue-500 dark:hover:text-blue-200 dark:focus:ring-blue-950"
                >
                    {buttonLabel}
                </button>
            </div>
            {error && (
                <p id={errorId} className="mt-2 text-sm text-red-600 dark:text-red-300">
                    {error}
                </p>
            )}
        </div>
    );
}
