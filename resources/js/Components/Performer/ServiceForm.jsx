const emptyPackage = {
    name: '',
    description: '',
    price: 1500,
    delivery_days: 3,
    revisions_count: 1,
};

export default function ServiceForm({ form, categories, onSubmit, submitLabel, children }) {
    const { data, setData, processing, errors } = form;

    const updatePackage = (index, field, value) => {
        setData(
            'packages',
            data.packages.map((pack, currentIndex) => (currentIndex === index ? { ...pack, [field]: value } : pack)),
        );
    };

    const addPackage = () => {
        if (data.packages.length >= 3) {
            return;
        }

        setData('packages', [...data.packages, { ...emptyPackage, name: `Пакет ${data.packages.length + 1}` }]);
    };

    const removePackage = (index) => {
        if (data.packages.length <= 1) {
            return;
        }

        setData(
            'packages',
            data.packages.filter((_, currentIndex) => currentIndex !== index),
        );
    };

    return (
        <form onSubmit={onSubmit} className="space-y-8">
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
                            value={data.title}
                            onChange={(event) => setData('title', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="short_description" label="Краткое описание" error={errors.short_description} className="lg:col-span-2">
                        <textarea
                            id="short_description"
                            name="short_description"
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
                            value={data.price_from}
                            onChange={(event) => setData('price_from', event.target.value)}
                            className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                    </Field>

                    <Field id="delivery_days" label="Срок выполнения, дней" error={errors.delivery_days}>
                        <input
                            id="delivery_days"
                            name="delivery_days"
                            type="number"
                            min="1"
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
                    <button
                        type="button"
                        onClick={addPackage}
                        disabled={data.packages.length >= 3}
                        className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Добавить пакет
                    </button>
                </div>

                {errors.packages && <p className="mt-4 text-sm text-red-600">{errors.packages}</p>}

                <div className="mt-6 space-y-4">
                    {data.packages.map((pack, index) => (
                        <div key={index} className="rounded-lg border border-slate-200 bg-slate-50 p-5">
                            <div className="flex items-center justify-between gap-4">
                                <h3 className="text-lg font-semibold text-slate-950">Пакет {index + 1}</h3>
                                <button
                                    type="button"
                                    onClick={() => removePackage(index)}
                                    disabled={data.packages.length <= 1}
                                    className="rounded-md px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Удалить пакет
                                </button>
                            </div>

                            <div className="mt-5 grid gap-5 lg:grid-cols-2">
                                <Field id={`packages_${index}_name`} label="Название пакета" error={errors[`packages.${index}.name`]}>
                                    <input
                                        id={`packages_${index}_name`}
                                        name={`packages[${index}][name]`}
                                        type="text"
                                        value={pack.name}
                                        onChange={(event) => updatePackage(index, 'name', event.target.value)}
                                        className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                </Field>

                                <Field id={`packages_${index}_price`} label="Цена, ₽" error={errors[`packages.${index}.price`]}>
                                    <input
                                        id={`packages_${index}_price`}
                                        name={`packages[${index}][price]`}
                                        type="number"
                                        min="100"
                                        value={pack.price}
                                        onChange={(event) => updatePackage(index, 'price', event.target.value)}
                                        className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                </Field>

                                <Field id={`packages_${index}_description`} label="Описание пакета" error={errors[`packages.${index}.description`]} className="lg:col-span-2">
                                    <textarea
                                        id={`packages_${index}_description`}
                                        name={`packages[${index}][description]`}
                                        value={pack.description ?? ''}
                                        onChange={(event) => updatePackage(index, 'description', event.target.value)}
                                        rows={3}
                                        className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                </Field>

                                <Field id={`packages_${index}_delivery_days`} label="Срок, дней" error={errors[`packages.${index}.delivery_days`]}>
                                    <input
                                        id={`packages_${index}_delivery_days`}
                                        name={`packages[${index}][delivery_days]`}
                                        type="number"
                                        min="1"
                                        value={pack.delivery_days}
                                        onChange={(event) => updatePackage(index, 'delivery_days', event.target.value)}
                                        className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                </Field>

                                <Field id={`packages_${index}_revisions_count`} label="Количество правок" error={errors[`packages.${index}.revisions_count`]}>
                                    <input
                                        id={`packages_${index}_revisions_count`}
                                        name={`packages[${index}][revisions_count]`}
                                        type="number"
                                        min="0"
                                        value={pack.revisions_count}
                                        onChange={(event) => updatePackage(index, 'revisions_count', event.target.value)}
                                        className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    />
                                </Field>
                            </div>
                        </div>
                    ))}
                </div>
            </section>

            <div className="flex flex-col gap-3 sm:flex-row sm:justify-end">
                {children}
                <button
                    type="submit"
                    disabled={processing}
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
