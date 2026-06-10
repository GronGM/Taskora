export default function TaskForm({ form, categories, onSubmit, submitLabel, showPublishButton = false, children }) {
    const { data, setData, processing, errors } = form;

    return (
        <form onSubmit={(event) => onSubmit(event, false)} className="space-y-8">
            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <p className="text-sm font-semibold uppercase text-blue-700">Задание</p>
                    <h2 className="mt-2 text-2xl font-semibold text-slate-950">Описание результата</h2>
                    <p className="mt-2 text-sm leading-6 text-slate-600">
                        Опишите задачу так, чтобы исполнитель понял объем, формат результата и ограничения по срокам.
                    </p>
                </div>

                <div className="mt-6 grid gap-5 lg:grid-cols-2">
                    <Field id="category_id" label="Категория" error={errors.category_id}>
                        <select
                            id="category_id"
                            name="category_id"
                            value={data.category_id}
                            onChange={(event) => setData('category_id', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Выберите категорию</option>
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field id="title" label="Название задания" error={errors.title}>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            value={data.title}
                            onChange={(event) => setData('title', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="description" label="Описание" error={errors.description} className="lg:col-span-2">
                        <textarea
                            id="description"
                            name="description"
                            value={data.description}
                            onChange={(event) => setData('description', event.target.value)}
                            rows={8}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="budget_min" label="Бюджет от, ₽" error={errors.budget_min}>
                        <input
                            id="budget_min"
                            name="budget_min"
                            type="number"
                            min="0"
                            value={data.budget_min}
                            onChange={(event) => setData('budget_min', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="budget_max" label="Бюджет до, ₽" error={errors.budget_max}>
                        <input
                            id="budget_max"
                            name="budget_max"
                            type="number"
                            min="0"
                            value={data.budget_max}
                            onChange={(event) => setData('budget_max', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="deadline_at" label="Срок выполнения" error={errors.deadline_at}>
                        <input
                            id="deadline_at"
                            name="deadline_at"
                            type="date"
                            value={data.deadline_at}
                            onChange={(event) => setData('deadline_at', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <div className="rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4">
                        <p className="text-sm font-semibold text-slate-950">Файлы</p>
                        <p className="mt-2 text-sm leading-6 text-slate-600">
                            Загрузка файлов будет добавлена отдельным этапом. Сейчас можно описать материалы в тексте задания.
                        </p>
                    </div>
                </div>
            </section>

            <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                {children}
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {submitLabel}
                </button>
                {showPublishButton && (
                    <button
                        type="button"
                        disabled={processing}
                        onClick={(event) => onSubmit(event, true)}
                        className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Опубликовать
                    </button>
                )}
            </div>
        </form>
    );
}

function Field({ id, label, error, className = '', children }) {
    return (
        <div className={`block ${className}`}>
            <label htmlFor={id} className="text-sm font-semibold text-slate-900">{label}</label>
            <div className="mt-2">{children}</div>
            {error && <p className="mt-2 text-sm text-red-600">{error}</p>}
        </div>
    );
}
