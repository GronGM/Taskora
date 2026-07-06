import { Link } from '@inertiajs/react';

export default function OnboardingChecklist({ title, description, steps }) {
    const remaining = steps.filter((step) => !step.done);

    if (remaining.length === 0) {
        return null;
    }

    return (
        <div className="mt-8 rounded-lg border border-blue-200 bg-blue-50 p-6 dark:border-blue-800 dark:bg-blue-950">
            <h2 className="text-xl font-semibold text-slate-950 dark:text-slate-100">{title}</h2>
            {description && <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-700 dark:text-slate-300">{description}</p>}
            <ol className="mt-4 grid gap-3 sm:grid-cols-3">
                {steps.map((step) => (
                    <li
                        key={step.label}
                        className={`rounded-md p-4 shadow-sm ${
                            step.done
                                ? 'bg-emerald-50 dark:bg-emerald-950'
                                : 'bg-white dark:bg-slate-900'
                        }`}
                    >
                        <p className={`flex items-center gap-2 text-sm font-semibold ${step.done ? 'text-emerald-700 dark:text-emerald-300' : 'text-slate-900 dark:text-slate-100'}`}>
                            <span aria-hidden="true">{step.done ? '✓' : '○'}</span>
                            {step.label}
                        </p>
                        {!step.done && step.href && (
                            <Link href={step.href} className="mt-2 inline-flex text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300">
                                {step.action}
                            </Link>
                        )}
                    </li>
                ))}
            </ol>
        </div>
    );
}
