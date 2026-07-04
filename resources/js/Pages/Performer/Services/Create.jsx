import { Head, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import ServiceForm from '../../../Components/Performer/ServiceForm';

export default function Create({ categories, defaultPackages }) {
    const form = useForm({
        category_id: '',
        title: '',
        short_description: '',
        description: '',
        price_from: 1500,
        delivery_days: 3,
        packages: defaultPackages,
        submit_for_review: false,
    });

    const submit = (event, submitForReview = false) => {
        event.preventDefault();
        form.transform((data) => ({ ...data, submit_for_review: submitForReview }));
        form.post('/performer/services');
    };

    return (
        <DashboardLayout>
            <Head title="Создать услугу" />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Мои услуги</p>
                    <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950">Создать услугу</h1>
                    <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                        Сохраните черновик или сразу отправьте услугу на модерацию. Контакты и предложения перейти вне Таскоры будут заблокированы.
                    </p>
                </div>

                <ServiceForm
                    form={form}
                    categories={categories}
                    onSubmit={(event) => submit(event, false)}
                    submitLabel="Сохранить черновик"
                >
                    <button
                        type="button"
                        disabled={form.processing}
                        onClick={(event) => submit(event, true)}
                        className="rounded-md border border-blue-200 bg-blue-50 px-5 py-3 text-sm font-semibold text-blue-700 hover:bg-blue-100 disabled:cursor-not-allowed disabled:opacity-60"
                    >
                        Сохранить и отправить на модерацию
                    </button>
                </ServiceForm>
            </section>
        </DashboardLayout>
    );
}
