import { useMemo, useState } from 'react';
import PackageEditor from './PackageEditor';

const inputClass = 'w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100';

const steps = [
    { key: 'about', title: 'Об услуге', hint: 'Что вы предлагаете и для кого' },
    { key: 'pricing', title: 'Цена и пакеты', hint: 'Стоимость, сроки и варианты' },
    { key: 'review', title: 'Проверка', hint: 'Просмотрите и отправьте на модерацию' },
];

const aboutFields = ['category_id', 'title', 'short_description', 'description'];
const pricingFields = ['price_from', 'delivery_days', 'packages'];

export default function ServiceWizard({ form, categories, onSubmit }) {
    const { data, setData, processing, errors } = form;
    const [step, setStep] = useState(0);

    const selectedCategory = categories.find((category) => String(category.id) === String(data.category_id));
    const aboutReady = data.category_id !== '' && data.title.trim() !== '' && data.short_description.trim() !== '';

    const serverErrorStep = useMemo(() => {
        const keys = Object.keys(errors);

        if (keys.length === 0) return null;
        if (aboutFields.some((field) => errors[field])) return 0;
        if (keys.some((key) => pricingFields.some((field) => key === field || key.startsWith('packages.')))) return 1;

        return null;
    }, [errors]);

    const activeStep = serverErrorStep !== null && serverErrorStep < step ? serverErrorStep : step;

    return (
        <div>
            <ol className="grid gap-2 sm:grid-cols-3" aria-label="Шаги создания услуги">
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
                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="grid gap-5 lg:grid-cols-2">
                            <Field id="category_id" label="Категория" error={errors.category_id}>
                                <select id="category_id" value={data.category_id} onChange={(event) => setData('category_id', event.target.value)} className={inputClass}>
                                    <option value="">Выберите категорию</option>
                                    {categories.map((category) => (
                                        <option key={category.id} value={category.id}>{category.name}</option>
                                    ))}
                                </select>
                            </Field>

                            <Field id="title" label="Название услуги" error={errors.title}>
                                <input
                                    id="title"
                                    type="text"
                                    value={data.title}
                                    onChange={(event) => setData('title', event.target.value)}
                                    placeholder="Например: сделаю презентацию с современным дизайном"
                                    className={inputClass}
                                />
                            </Field>

                            <Field id="short_description" label="Краткое описание" error={errors.short_description} className="lg:col-span-2">
                                <textarea
                                    id="short_description"
                                    value={data.short_description}
                                    onChange={(event) => setData('short_description', event.target.value)}
                                    rows={3}
                                    placeholder="Одно-два предложения: что получит заказчик. Показывается в каталоге."
                                    className={`${inputClass} leading-6`}
                                />
                            </Field>

                            <Field id="description" label="Полное описание" error={errors.description} className="lg:col-span-2">
                                <textarea
                                    id="description"
                                    value={data.description ?? ''}
                                    onChange={(event) => setData('description', event.target.value)}
                                    rows={7}
                                    placeholder="Что входит в услугу, какие материалы нужны от заказчика, что не входит. Контакты указывать нельзя."
                                    className={`${inputClass} leading-6`}
                                />
                            </Field>

                            <Field id="cover" label="Обложка (необязательно)" error={errors.cover} className="lg:col-span-2">
                                <input
                                    id="cover"
                                    type="file"
                                    accept="image/jpeg,image/png,image/webp"
                                    onChange={(event) => setData('cover', event.target.files[0] ?? null)}
                                    className="w-full text-sm text-slate-600 file:mr-4 file:rounded-md file:border-0 file:bg-blue-50 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100"
                                />
                                <p className="mt-2 text-xs leading-5 text-slate-500">
                                    JPG, PNG или WebP до 5 МБ. Карточки с обложкой заметнее в каталоге; рекомендуемое соотношение 2:1.
                                </p>
                                {data.cover && (
                                    <img src={URL.createObjectURL(data.cover)} alt="Превью обложки" className="mt-3 aspect-[2/1] w-full max-w-md rounded-lg object-cover" />
                                )}
                            </Field>
                        </div>
                    </section>
                )}

                {activeStep === 1 && (
                    <div className="space-y-6">
                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <div className="grid gap-5 lg:grid-cols-2">
                                <Field id="price_from" label="Цена от, ₽" error={errors.price_from}>
                                    <input id="price_from" type="number" min="100" value={data.price_from} onChange={(event) => setData('price_from', event.target.value)} className={inputClass} />
                                </Field>
                                <Field id="delivery_days" label="Срок выполнения, дней" error={errors.delivery_days}>
                                    <input id="delivery_days" type="number" min="1" value={data.delivery_days} onChange={(event) => setData('delivery_days', event.target.value)} className={inputClass} />
                                </Field>

                                <Field id="max_review_hold_days" label="Максимальный срок проверки" error={errors.max_review_hold_days} className="lg:col-span-2">
                                    <select
                                        id="max_review_hold_days"
                                        value={data.max_review_hold_days ?? ''}
                                        onChange={(event) => setData('max_review_hold_days', event.target.value === '' ? null : Number(event.target.value))}
                                        className={inputClass}
                                    >
                                        <option value="">Без ограничения (до 40 дней)</option>
                                        <option value="5">До 5 дней</option>
                                        <option value="10">До 10 дней</option>
                                        <option value="20">До 20 дней</option>
                                        <option value="30">До 30 дней</option>
                                    </select>
                                    <p className="mt-2 text-xs leading-5 text-slate-500">
                                        Заказчик выбирает срок проверки работы при оформлении заказа, но не больше вашего максимума. Короткий максимум — быстрее выплаты вам.
                                    </p>
                                </Field>
                            </div>
                        </section>

                        <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                            <h2 className="text-lg font-semibold text-slate-950">Пакеты услуги</h2>
                            <div className="mt-4">
                                <PackageEditor packages={data.packages} errors={errors} onChange={(packages) => setData('packages', packages)} />
                            </div>
                        </section>
                    </div>
                )}

                {activeStep === 2 && (
                    <section className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-lg font-semibold text-slate-950">Проверьте услугу</h2>
                        <dl className="mt-4 space-y-3 text-sm">
                            <SummaryRow label="Категория" value={selectedCategory?.name ?? '—'} />
                            <SummaryRow label="Название" value={data.title || '—'} />
                            <SummaryRow label="Цена от" value={`${data.price_from} ₽`} />
                            <SummaryRow label="Срок" value={`${data.delivery_days} дн.`} />
                            <SummaryRow label="Пакеты" value={data.packages.map((pack) => pack.name || 'Без названия').join(', ')} />
                            <SummaryRow label="Обложка" value={data.cover ? data.cover.name : 'Без обложки'} />
                        </dl>
                        <p className="mt-4 whitespace-pre-line border-t border-slate-100 pt-4 text-sm leading-6 text-slate-600">{data.short_description}</p>
                        <p className="mt-4 rounded-md bg-slate-50 p-4 text-xs leading-5 text-slate-500">
                            После отправки услугу проверит модератор. Обычно это занимает немного времени; статус виден в «Моих услугах».
                        </p>
                    </section>
                )}

                <div className="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        {activeStep > 0 && (
                            <button
                                type="button"
                                onClick={() => setStep(activeStep - 1)}
                                className="rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
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
                                    className="rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Сохранить черновик
                                </button>
                                <button
                                    type="button"
                                    disabled={processing}
                                    onClick={(event) => onSubmit(event, true)}
                                    className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                                >
                                    Отправить на модерацию
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
            <dt className="text-slate-500">{label}</dt>
            <dd className="font-semibold text-slate-950">{value}</dd>
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
