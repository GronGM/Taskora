import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import CategoryForm from './Form';

export default function Edit({ category, parentOptions = [] }) {
    const form = useForm({
        name: category.name ?? '',
        slug: category.slug ?? '',
        parent_id: category.parent_id ?? '',
        description: category.description ?? '',
        icon: category.icon ?? '',
        sort_order: category.sort_order ?? 0,
        is_active: Boolean(category.is_active),
    });

    const submit = (event) => {
        event.preventDefault();
        form.patch(category.update_url);
    };

    return (
        <DashboardLayout>
            <Head title={`Редактирование: ${category.name}`} />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Администрирование</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-normal text-slate-950">Редактирование категории</h1>
                        <p className="mt-3 text-sm text-slate-600">{category.name}</p>
                    </div>
                    <Link href="/admin/categories" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        К списку
                    </Link>
                </div>

                <CategoryForm form={form} parentOptions={parentOptions} onSubmit={submit} submitLabel="Сохранить изменения" mode="edit" />
            </section>
        </DashboardLayout>
    );
}
