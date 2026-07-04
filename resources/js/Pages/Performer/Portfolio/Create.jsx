import { Head, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortfolioForm from '../../../Components/Performer/PortfolioForm';

export default function Create({ categories }) {
    const form = useForm({
        title: '',
        description: '',
        category_id: '',
        external_url: '',
        sort_order: 0,
        status: 'published',
        image: null,
        file: null,
    });

    const submit = (event) => {
        event.preventDefault();
        form.post('/performer/portfolio', { forceFormData: true });
    };

    return (
        <DashboardLayout>
            <Head title="Добавить работу" />
            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Портфолио</p>
                    <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950">Добавить работу</h1>
                </div>
                <PortfolioForm form={form} categories={categories} onSubmit={submit} submitLabel="Сохранить работу" />
            </section>
        </DashboardLayout>
    );
}
