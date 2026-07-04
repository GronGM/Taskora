const inputClass = 'w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';

export const emptyPackage = {
    name: '',
    description: '',
    price: 1500,
    delivery_days: 3,
    revisions_count: 1,
};

export default function PackageEditor({ packages, errors, disabled = false, onChange }) {
    const updatePackage = (index, field, value) => {
        if (disabled) return;
        onChange(packages.map((pack, currentIndex) => (currentIndex === index ? { ...pack, [field]: value } : pack)));
    };

    const addPackage = () => {
        if (disabled || packages.length >= 3) return;
        onChange([...packages, { ...emptyPackage, name: `Пакет ${packages.length + 1}` }]);
    };

    const removePackage = (index) => {
        if (disabled || packages.length <= 1) return;
        onChange(packages.filter((_, currentIndex) => currentIndex !== index));
    };

    return (
        <div>
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                <p className="text-sm leading-6 text-slate-600">Добавьте от одного до трех пакетов с разной ценой и объемом.</p>
                <button
                    type="button"
                    onClick={addPackage}
                    disabled={disabled || packages.length >= 3}
                    className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                >
                    Добавить пакет
                </button>
            </div>

            {errors.packages && <p className="mt-4 text-sm text-red-600">{errors.packages}</p>}

            <div className="mt-6 space-y-4">
                {packages.map((pack, index) => (
                    <div key={index} className="rounded-lg border border-slate-200 bg-slate-50 p-5">
                        <div className="flex items-center justify-between gap-4">
                            <h3 className="text-lg font-semibold text-slate-950">Пакет {index + 1}</h3>
                            <button
                                type="button"
                                onClick={() => removePackage(index)}
                                disabled={disabled || packages.length <= 1}
                                className="rounded-md px-3 py-2 text-sm font-semibold text-red-700 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Удалить пакет
                            </button>
                        </div>

                        <div className="mt-5 grid gap-5 lg:grid-cols-2">
                            <Field id={`packages_${index}_name`} label="Название пакета" error={errors[`packages.${index}.name`]}>
                                <input id={`packages_${index}_name`} type="text" disabled={disabled} value={pack.name} onChange={(event) => updatePackage(index, 'name', event.target.value)} className={inputClass} />
                            </Field>

                            <Field id={`packages_${index}_price`} label="Цена, ₽" error={errors[`packages.${index}.price`]}>
                                <input id={`packages_${index}_price`} type="number" min="100" disabled={disabled} value={pack.price} onChange={(event) => updatePackage(index, 'price', event.target.value)} className={inputClass} />
                            </Field>

                            <Field id={`packages_${index}_description`} label="Описание пакета" error={errors[`packages.${index}.description`]} className="lg:col-span-2">
                                <textarea id={`packages_${index}_description`} disabled={disabled} value={pack.description ?? ''} onChange={(event) => updatePackage(index, 'description', event.target.value)} rows={3} className={`${inputClass} leading-6`} />
                            </Field>

                            <Field id={`packages_${index}_delivery_days`} label="Срок, дней" error={errors[`packages.${index}.delivery_days`]}>
                                <input id={`packages_${index}_delivery_days`} type="number" min="1" disabled={disabled} value={pack.delivery_days} onChange={(event) => updatePackage(index, 'delivery_days', event.target.value)} className={inputClass} />
                            </Field>

                            <Field id={`packages_${index}_revisions_count`} label="Количество правок" error={errors[`packages.${index}.revisions_count`]}>
                                <input id={`packages_${index}_revisions_count`} type="number" min="0" disabled={disabled} value={pack.revisions_count} onChange={(event) => updatePackage(index, 'revisions_count', event.target.value)} className={inputClass} />
                            </Field>
                        </div>
                    </div>
                ))}
            </div>
        </div>
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
