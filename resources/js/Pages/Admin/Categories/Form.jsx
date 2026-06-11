export default function CategoryForm({ form, parentOptions, onSubmit, submitLabel, mode = 'create' }) {
    const { data, setData, processing, errors } = form;

    return (
        <form onSubmit={onSubmit} className="space-y-6">
            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <p className="text-sm font-semibold uppercase text-blue-700">Справочник</p>
                    <h2 className="mt-2 text-2xl font-semibold text-slate-950">Параметры категории</h2>
                    <p className="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                        Slug используется в публичных URL. При создании его можно оставить пустым, Таскора создаст адрес автоматически.
                    </p>
                </div>

                <div className="mt-6 grid gap-5 lg:grid-cols-2">
                    <Field id="name" label="Название" error={errors.name}>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            value={data.name}
                            onChange={(event) => setData('name', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="slug" label="Slug" error={errors.slug}>
                        <input
                            id="slug"
                            name="slug"
                            type="text"
                            value={data.slug}
                            onChange={(event) => setData('slug', event.target.value)}
                            placeholder={mode === 'create' ? 'Автоматически из названия' : 'Оставьте текущий slug без изменений'}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="parent_id" label="Родительская категория" error={errors.parent_id}>
                        <select
                            id="parent_id"
                            name="parent_id"
                            value={data.parent_id ?? ''}
                            onChange={(event) => setData('parent_id', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Без родителя</option>
                            {parentOptions.map((category) => (
                                <option key={category.id} value={category.id}>
                                    {category.name}
                                </option>
                            ))}
                        </select>
                    </Field>

                    <Field id="icon" label="Иконка" error={errors.icon}>
                        <input
                            id="icon"
                            name="icon"
                            type="text"
                            value={data.icon ?? ''}
                            onChange={(event) => setData('icon', event.target.value)}
                            placeholder="Например: book-open"
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="sort_order" label="Порядок сортировки" error={errors.sort_order}>
                        <input
                            id="sort_order"
                            name="sort_order"
                            type="number"
                            min="0"
                            value={data.sort_order}
                            onChange={(event) => setData('sort_order', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <div className="flex items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">
                        <input
                            id="is_active"
                            name="is_active"
                            type="checkbox"
                            checked={data.is_active}
                            onChange={(event) => setData('is_active', event.target.checked)}
                            className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        />
                        <label htmlFor="is_active" className="text-sm font-semibold text-slate-900">
                            Показывать категорию в публичных фильтрах и новых формах
                        </label>
                    </div>

                    <Field id="description" label="Описание" error={errors.description} className="lg:col-span-2">
                        <textarea
                            id="description"
                            name="description"
                            rows={5}
                            value={data.description ?? ''}
                            onChange={(event) => setData('description', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>
                </div>
            </section>

            <div className="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                Отключение категории не удаляет существующие задания и услуги. Физическое удаление справочников в MVP отключено.
            </div>

            <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                <button
                    type="submit"
                    disabled={processing}
                    className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {processing ? 'Сохраняем...' : submitLabel}
                </button>
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
