import { useMemo, useState } from 'react';

const reviewHoldPresets = [
    { value: 5, label: '5 дней — быстрая выплата исполнителю' },
    { value: 10, label: '10 дней — стандартный срок (рекомендуем)' },
    { value: 20, label: '20 дней — расширенная проверка' },
    { value: 30, label: '30 дней — максимальная проверка' },
];

const inputClass = 'w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';

const steps = [
    { key: 'about', title: 'О задаче', hint: 'Что нужно сделать и в какой категории' },
    { key: 'terms', title: 'Условия', hint: 'Бюджет и срок — можно уточнить позже' },
    { key: 'review', title: 'Проверка', hint: 'Просмотрите и опубликуйте' },
];

const stepFields = {
    about: ['category_id', 'task_type_id', 'title', 'description'],
    terms: ['budget_min', 'budget_max', 'deadline_at', 'review_hold_days'],
};

export default function TaskWizard({ form, categories, onSubmit }) {
    const { data, setData, processing, errors } = form;
    const [step, setStep] = useState(0);
    const [budgetMode, setBudgetMode] = useState('negotiable');

    const changeBudgetMode = (mode) => {
        setBudgetMode(mode);

        if (mode === 'negotiable') {
            setData({ ...data, budget_min: '', budget_max: '' });
        }

        if (mode === 'exact') {
            setData({ ...data, budget_max: data.budget_min });
        }
    };

    const selectedCategory = categories.find((category) => String(category.id) === String(data.category_id));
    const taskTypes = selectedCategory?.task_types ?? [];
    const selectedTaskType = taskTypes.find((taskType) => String(taskType.id) === String(data.task_type_id));

    const aboutReady = data.category_id !== '' && data.title.trim() !== '' && data.description.trim() !== '';

    const serverErrorStep = useMemo(() => {
        if (Object.keys(errors).length === 0) {
            return null;
        }

        if (stepFields.about.some((field) => errors[field])) {
            return 0;
        }

        if (stepFields.terms.some((field) => errors[field])) {
            return 1;
        }

        return null;
    }, [errors]);

    const activeStep = serverErrorStep !== null && serverErrorStep < step ? serverErrorStep : step;

    const budgetLabel = () => {
        if (data.budget_min && data.budget_max) return `${data.budget_min} – ${data.budget_max} ₽`;
        if (data.budget_min) return `от ${data.budget_min} ₽`;
        if (data.budget_max) return `до ${data.budget_max} ₽`;
        return 'Обсуждается с исполнителем';
    };

    return (
        <div>
            <ol className="grid gap-2 sm:grid-cols-3" aria-label="Шаги создания задания">
                {steps.map((item, index) => (
                    <li
                        key={item.key}
                        aria-current={index === activeStep ? 'step' : undefined}
                        className={`rounded-lg border p-4 text-sm transition ${
                            index === activeStep
                                ? 'border-blue-300 bg-blue-50 dark:border-blue-700 dark:bg-blue-950'
                                : index < activeStep
                                  ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-950'
                                  : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900'
                        }`}
                    >
                        <p className="font-semibold text-slate-950 dark:text-slate-100">
                            {index < activeStep ? '✓ ' : `${index + 1}. `}
                            {item.title}
                        </p>
                        <p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">{item.hint}</p>
                    </li>
                ))}
            </ol>

            <form onSubmit={(event) => onSubmit(event, false)} className="mt-6">
                {activeStep === 0 && (
                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <div className="grid gap-5 lg:grid-cols-2">
                            <Field id="category_id" label="Категория" error={errors.category_id}>
                                <select
                                    id="category_id"
                                    value={data.category_id}
                                    onChange={(event) => setData({ ...data, category_id: event.target.value, task_type_id: '' })}
                                    className={inputClass}
                                >
                                    <option value="">Выберите категорию</option>
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>{category.name}</option>
                                    ))}
                                </select>
                            </Field>

                            <Field id="task_type_id" label="Вид задания (необязательно)" error={errors.task_type_id}>
                                <select
                                    id="task_type_id"
                                    value={data.task_type_id}
                                    disabled={!data.category_id || taskTypes.length === 0}
                                    onChange={(event) => setData('task_type_id', event.target.value)}
                                    className={`${inputClass} disabled:cursor-not-allowed disabled:bg-slate-100 disabled:text-slate-500`}
                                >
                                    <option value="">
                                        {!data.category_id ? 'Сначала выберите категорию' : taskTypes.length === 0 ? 'Для категории нет видов' : 'Выберите вид задания'}
                                    </option>
                                    {taskTypes.map((taskType) => (
                                        <option key={taskType.id} value={taskType.id}>{taskType.name}</option>
                                    ))}
                                </select>
                            </Field>

                            <Field id="title" label="Название задания" error={errors.title} className="lg:col-span-2">
                                <input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(event) => setData('title', event.target.value)}
                                    placeholder="Например: подготовить презентацию на 10 слайдов"
                                    className={inputClass}
                                />
                            </Field>

                            <Field id="description" label="Описание" error={errors.description} className="lg:col-span-2">
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(event) => setData('description', event.target.value)}
                                    rows={7}
                                    placeholder="Что нужно сделать, какой формат результата, какие есть материалы и требования. Контакты указывать нельзя — общение идет внутри Таскоры."
                                    className={`${inputClass} leading-6`}
                                />
                            </Field>
                        </div>
                    </section>
                )}

                {activeStep === 1 && (
                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <fieldset>
                            <legend className="text-sm font-semibold text-slate-900 dark:text-slate-200">Бюджет</legend>
                            <div className="mt-3 grid gap-2 sm:grid-cols-3">
                                {[
                                    { mode: 'negotiable', label: 'По договоренности', hint: 'исполнители предложат цену в откликах' },
                                    { mode: 'exact', label: 'Точная сумма', hint: 'вы знаете, сколько готовы заплатить' },
                                    { mode: 'range', label: 'Диапазон', hint: 'от и до' },
                                ].map(({ mode, label, hint }) => (
                                    <label
                                        key={mode}
                                        className={`cursor-pointer rounded-lg border p-3 text-sm transition ${
                                            budgetMode === mode
                                                ? 'border-blue-400 bg-blue-50 dark:border-blue-600 dark:bg-blue-950'
                                                : 'border-slate-200 bg-white hover:border-slate-300 dark:border-slate-700 dark:bg-slate-900'
                                        }`}
                                    >
                                        <input type="radio" name="budget_mode" value={mode} checked={budgetMode === mode} onChange={() => changeBudgetMode(mode)} className="sr-only" />
                                        <span className="font-semibold text-slate-950 dark:text-slate-100">{label}</span>
                                        <span className="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">{hint}</span>
                                    </label>
                                ))}
                            </div>
                        </fieldset>

                        <div className="mt-5 grid gap-5 lg:grid-cols-3">
                            {budgetMode === 'exact' && (
                                <Field id="budget_min" label="Сумма, ₽" error={errors.budget_min || errors.budget_max}>
                                    <input
                                        id="budget_min"
                                        type="number"
                                        min="0"
                                        value={data.budget_min}
                                        onChange={(event) => setData({ ...data, budget_min: event.target.value, budget_max: event.target.value })}
                                        className={inputClass}
                                    />
                                </Field>
                            )}
                            {budgetMode === 'range' && (
                                <>
                                    <Field id="budget_min" label="Бюджет от, ₽" error={errors.budget_min}>
                                        <input id="budget_min" type="number" min="0" value={data.budget_min} onChange={(event) => setData('budget_min', event.target.value)} className={inputClass} />
                                    </Field>
                                    <Field id="budget_max" label="Бюджет до, ₽" error={errors.budget_max}>
                                        <input id="budget_max" type="number" min="0" value={data.budget_max} onChange={(event) => setData('budget_max', event.target.value)} className={inputClass} />
                                    </Field>
                                </>
                            )}
                            <Field id="deadline_at" label="Срок выполнения" error={errors.deadline_at}>
                                <input id="deadline_at" type="date" value={data.deadline_at} onChange={(event) => setData('deadline_at', event.target.value)} className={inputClass} />
                            </Field>
                        </div>

                        <Field id="review_hold_days" label="Срок проверки работы после сдачи" error={errors.review_hold_days} className="mt-5">
                            <select
                                id="review_hold_days"
                                value={data.review_hold_days}
                                onChange={(event) => setData('review_hold_days', Number(event.target.value))}
                                className={inputClass}
                            >
                                {reviewHoldPresets.map(({ value, label }) => (
                                    <option key={value} value={value}>{label}</option>
                                ))}
                            </select>
                            <p className="mt-2 text-xs leading-5 text-slate-500 dark:text-slate-400">
                                Столько дней у вас будет на проверку сданной работы: деньги заморожены, можно запросить доработку или открыть спор.
                                Если принять работу раньше — исполнитель получит оплату сразу. Исполнители видят срок проверки в задании.
                            </p>
                        </Field>

                        <p className="mt-5 rounded-md bg-slate-50 p-4 text-sm leading-6 text-slate-600 dark:bg-slate-950 dark:text-slate-400">
                            Бюджет и срок выполнения необязательны — их можно согласовать с исполнителем.
                        </p>
                    </section>
                )}

                {activeStep === 2 && (
                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                        <h2 className="text-lg font-semibold text-slate-950 dark:text-slate-100">Проверьте задание</h2>
                        <dl className="mt-4 space-y-3 text-sm">
                            <SummaryRow label="Категория" value={selectedCategory?.name ?? '—'} />
                            <SummaryRow label="Вид задания" value={selectedTaskType?.name ?? 'Не указан'} />
                            <SummaryRow label="Название" value={data.title || '—'} />
                            <SummaryRow label="Бюджет" value={budgetLabel()} />
                            <SummaryRow label="Срок" value={data.deadline_at || 'Не указан'} />
                            <SummaryRow label="Проверка работы" value={`${data.review_hold_days} дн.`} />
                        </dl>
                        <p className="mt-4 whitespace-pre-line border-t border-slate-100 pt-4 text-sm leading-6 text-slate-600 dark:border-slate-800 dark:text-slate-400">{data.description}</p>
                    </section>
                )}

                <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        {activeStep > 0 && (
                            <button
                                type="button"
                                onClick={() => setStep(activeStep - 1)}
                                className="rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                            >
                                Назад
                            </button>
                        )}
                    </div>
                    <div className="flex flex-col gap-3 sm:flex-row">
                        {activeStep < 2 && (
                            <button
                                type="button"
                                disabled={activeStep === 0 && !aboutReady}
                                onClick={() => setStep(activeStep + 1)}
                                className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                Далее
                            </button>
                        )}
                        {activeStep === 2 && (
                            <>
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800"
                                >
                                    Сохранить черновик
                                </button>
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={(event) => onSubmit(event, true)}
                                    className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Опубликовать задание
                                </button>
                            </>
                        )}
                    </div>
                </div>
            </form>
        </div>
    );
}

function SummaryRow({ label, value }) {
    return (
        <div className="flex flex-wrap items-baseline justify-between gap-3">
            <dt className="text-slate-500 dark:text-slate-400">{label}</dt>
            <dd className="font-semibold text-slate-950 dark:text-slate-100">{value}</dd>
        </div>
    );
}

function Field({ id, label, error, className = '', children }) {
    return (
        <div className={`block ${className}`}>
            <label htmlFor={id} className="text-sm font-semibold text-slate-900 dark:text-slate-200">{label}</label>
            <div className="mt-2">{children}</div>
            {error && <p className="mt-2 text-sm text-red-600">{error}</p>}
        </div>
    );
}
