import { Head, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import PortfolioForm from '../../../Components/Performer/PortfolioForm';

export default function Edit({ item, categories }) {
    const form = useForm({
        _method: 'patch',
        title: item.title ?? '',
        description: item.description ?? '',
        category_id: item.category_id ?? '',
        external_url: item.external_url ?? '',
        sort_order: item.sort_order ?? 0,
        status: item.status === 'hidden' ? 'draft' : item.status,
        image: null,
        file: null,
    });

    const submit = (event) => {
        event.preventDefault();
        form.post(item.update_url, { forceFormData: true });
    };

    return (
        <DashboardLayout>
            <Head title={`Редактировать: ${item.title}`} />
            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Портфолио</p>
                    <h1 className="mt-2 text-4xl font-semibold tracking-normal text-slate-950">Редактировать работу</h1>
                    {(item.image_url || item.file_url) && (
                        <div className="mt-4 flex flex-wrap gap-2 text-sm">
                            {item.image_url && <a href={item.image_url} className="font-semibold text-blue-700 hover:text-blue-800">Текущее изображение</a>}
                            {item.file_url && <a href={item.file_url} className="font-semibold text-blue-700 hover:text-blue-800">Текущий файл</a>}
                        </div>
                    )}
                </div>
                <PortfolioForm form={form} categories={categories} onSubmit={submit} submitLabel="Сохранить изменения" />
            </section>
        </DashboardLayout>
    );
}
