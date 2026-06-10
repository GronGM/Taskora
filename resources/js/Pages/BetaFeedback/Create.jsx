import { Head, Link, useForm } from '@inertiajs/react';
import { useEffect } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

export default function Create({
    roleOptions = [],
    typeOptions = [],
    severityOptions = [],
    storeUrl = '/beta-feedback',
    defaultPageUrl = '',
    flash = {},
}) {
    const initialPageUrl = defaultPageUrl || (typeof window !== 'undefined' ? window.location.href : '');

    const form = useForm({
        role: roleOptions[0]?.value ?? 'guest',
        page_url: initialPageUrl,
        scenario: '',
        type: typeOptions[0]?.value ?? 'bug',
        severity: severityOptions[1]?.value ?? 'medium',
        title: '',
        description: '',
        browser: '',
        screen_size: '',
    });

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        if (!form.data.browser && window.navigator?.userAgent) {
            form.setData('browser', window.navigator.userAgent);
        }

        if (!form.data.screen_size) {
            form.setData('screen_size', `${window.innerWidth}x${window.innerHeight}`);
        }
    }, []);

    const submit = (event) => {
        event.preventDefault();
        form.post(storeUrl, {
            preserveScroll: true,
            onSuccess: () => form.reset('scenario', 'title', 'description'),
        });
    };

    return (
        <PublicLayout>
            <Head title="Сообщить о проблеме" />

            <section className="mx-auto max-w-5xl px-4 py-12 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Beta-обратная связь</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Сообщить о проблеме</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Опишите, что проверяли, где возникла проблема и что ожидали увидеть. Скриншот можно
                            приложить в сообщении отдельно, если нужно.
                        </p>
                    </div>
                    <Link href="/beta-testing" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        К чек-листам
                    </Link>
                </div>

                {flash.success && (
                    <div className="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-900">
                        {flash.success}
                    </div>
                )}

                <form onSubmit={submit} className="mt-8 rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="grid gap-5 md:grid-cols-2">
                        <Field label="Роль тестировщика" error={form.errors.role}>
                            <select
                                value={form.data.role}
                                onChange={(event) => form.setData('role', event.target.value)}
                                className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                                {roleOptions.map((option) => (
                                    <option key={option.value} value={option.value}>{option.label}</option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Тип обращения" error={form.errors.type}>
                            <select
                                value={form.data.type}
                                onChange={(event) => form.setData('type', event.target.value)}
                                className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                                {typeOptions.map((option) => (
                                    <option key={option.value} value={option.value}>{option.label}</option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Критичность" error={form.errors.severity}>
                            <select
                                value={form.data.severity}
                                onChange={(event) => form.setData('severity', event.target.value)}
                                className="w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                            >
                                {severityOptions.map((option) => (
                                    <option key={option.value} value={option.value}>{option.label}</option>
                                ))}
                            </select>
                        </Field>

                        <Field label="Страница или URL" error={form.errors.page_url}>
                            <input
                                type="text"
                                value={form.data.page_url}
                                onChange={(event) => form.setData('page_url', event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="/catalog или полный URL"
                            />
                        </Field>

                        <Field label="Сценарий" error={form.errors.scenario}>
                            <input
                                type="text"
                                value={form.data.scenario}
                                onChange={(event) => form.setData('scenario', event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Например: заказ из услуги"
                            />
                        </Field>

                        <Field label="Размер экрана" error={form.errors.screen_size}>
                            <input
                                type="text"
                                value={form.data.screen_size}
                                onChange={(event) => form.setData('screen_size', event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="1280x720"
                            />
                        </Field>
                    </div>

                    <div className="mt-5">
                        <Field label="Браузер" error={form.errors.browser}>
                            <input
                                type="text"
                                value={form.data.browser}
                                onChange={(event) => form.setData('browser', event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Chrome, Edge, Safari, Firefox"
                            />
                        </Field>
                    </div>

                    <div className="mt-5">
                        <Field label="Короткий заголовок" error={form.errors.title}>
                            <input
                                type="text"
                                value={form.data.title}
                                onChange={(event) => form.setData('title', event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Что сломалось или вызывает вопрос"
                            />
                        </Field>
                    </div>

                    <div className="mt-5">
                        <Field label="Описание" error={form.errors.description}>
                            <textarea
                                rows={8}
                                value={form.data.description}
                                onChange={(event) => form.setData('description', event.target.value)}
                                className="w-full rounded-md border border-slate-300 px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                placeholder="Шаги, ожидаемый результат, фактический результат и любые детали ошибки."
                            />
                        </Field>
                    </div>

                    <div className="mt-6 rounded-md bg-slate-50 p-4 text-sm leading-6 text-slate-600">
                        Приложите скриншот в сообщении отдельно, если нужно. В форму не добавляйте реальные документы,
                        карты, пароли и персональные данные.
                    </div>

                    <button
                        type="submit"
                        disabled={form.processing}
                        className="mt-6 rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Отправить обращение
                    </button>
                </form>
            </section>
        </PublicLayout>
    );
}

function Field({ label, error, children }) {
    return (
        <label className="block">
            <span className="text-sm font-semibold text-slate-900">{label}</span>
            <span className="mt-2 block">{children}</span>
            {error && <span className="mt-2 block text-sm text-red-600">{error}</span>}
        </label>
    );
}
