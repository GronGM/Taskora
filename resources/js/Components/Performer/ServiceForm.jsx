import PackageEditor from './PackageEditor';

export default function ServiceForm({ form, categories, onSubmit, submitLabel, children, disabled = false }) {
    const { data, setData, processing, errors } = form;

    return (
        <form
            onSubmit={(event) => {
                if (disabled) {
                    event.preventDefault();

                    return;
                }

                onSubmit(event);
            }}
            className="space-y-8"
        >
            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div>
                    <p className="text-sm font-semibold uppercase text-blue-700">Основное</p>
                    <h2 className="mt-2 text-2xl font-semibold text-slate-950">Описание услуги</h2>
                </div>

                <div className="mt-6 grid gap-5 lg:grid-cols-2">
                    <Field id="category_id" label="Категория" error={errors.category_id}>
                        <select
                            id="category_id"
                            name="category_id"
                            disabled={disabled}
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

                    <Field id="title" label="Название услуги" error={errors.title}>
                        <input
                            id="title"
                            name="title"
                            type="text"
                            disabled={disabled}
                            value={data.title}
                            onChange={(event) => setData('title', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="short_description" label="Краткое описание" error={errors.short_description} className="lg:col-span-2">
                        <textarea
                            id="short_description"
                            name="short_description"
                            disabled={disabled}
                            value={data.short_description}
                            onChange={(event) => setData('short_description', event.target.value)}
                            rows={3}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="description" label="Полное описание" error={errors.description} className="lg:col-span-2">
                        <textarea
                            id="description"
                            name="description"
                            disabled={disabled}
                            value={data.description ?? ''}
                            onChange={(event) => setData('description', event.target.value)}
                            rows={7}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="price_from" label="Цена от, ₽" error={errors.price_from}>
                        <input
                            id="price_from"
                            name="price_from"
                            type="number"
                            min="100"
                            disabled={disabled}
                            value={data.price_from}
                            onChange={(event) => setData('price_from', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="max_review_hold_days" label="Максимальный срок проверки" error={errors.max_review_hold_days}>
                        <select
                            id="max_review_hold_days"
                            disabled={disabled}
                            value={data.max_review_hold_days ?? ''}
                            onChange={(event) => setData('max_review_hold_days', event.target.value === '' ? null : Number(event.target.value))}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Без ограничения (до 40 дней)</option>
                            <option value="5">До 5 дней</option>
                            <option value="10">До 10 дней</option>
                            <option value="20">До 20 дней</option>
                            <option value="30">До 30 дней</option>
                        </select>
                    </Field>

                    <Field id="cover" label="Обложка услуги" error={errors.cover} className="lg:col-span-2">
                        <input
                            id="cover"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            disabled={disabled}
                            onChange={(event) => setData('cover', event.target.files[0] ?? null)}
                            className="w-full text-sm text-slate-600 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                        />
                        <p className="mt-2 text-xs leading-5 text-slate-500">
                            JPG, PNG или WebP до 5 МБ, соотношение 2:1. Замена обложки опубликованной услуги вернет ее на модерацию.
                        </p>
                        {data.cover instanceof File && (
                            <img src={URL.createObjectURL(data.cover)} alt="Превью новой обложки" className="mt-3 aspect-[2/1] w-full max-w-md rounded-lg object-cover" />
                        )}
                        {!data.cover && data.current_cover_url && (
                            <img src={data.current_cover_url} alt="Текущая обложка" className="mt-3 aspect-[2/1] w-full max-w-md rounded-lg object-cover opacity-80" />
                        )}
                    </Field>

                    <Field id="delivery_days" label="Срок выполнения, дней" error={errors.delivery_days}>
                        <input
                            id="delivery_days"
                            name="delivery_days"
                            type="number"
                            min="1"
                            disabled={disabled}
                            value={data.delivery_days}
                            onChange={(event) => setData('delivery_days', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>
                </div>
            </section>

            <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Пакеты</p>
                        <h2 className="mt-2 text-2xl font-semibold text-slate-950">Варианты услуги</h2>
                        <p className="mt-2 text-sm leading-6 text-slate-600">Добавьте от одного до трех пакетов.</p>
                    </div>
                </div>

                <div className="mt-4">
                    <PackageEditor
                        packages={data.packages}
                        errors={errors}
                        disabled={disabled}
                        onChange={(packages) => setData('packages', packages)}
                    />
                </div>
            </section>

            <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                {children}
                <button
                    type="submit"
                    disabled={processing || disabled}
                    className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                >
                    {submitLabel}
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
