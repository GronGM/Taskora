import { Head, Link, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';
import TaskTypeForm from './Form';

export default function Edit({ taskType, categoryOptions = [] }) {
    const form = useForm({
        category_id: taskType.category_id ?? '',
        name: taskType.name ?? '',
        slug: taskType.slug ?? '',
        description: taskType.description ?? '',
        sort_order: taskType.sort_order ?? 0,
        is_active: Boolean(taskType.is_active),
    });

    const submit = (event) => {
        event.preventDefault();
        form.patch(taskType.update_url);
    };

    return (
        <DashboardLayout>
            <Head title={`Редактирование: ${taskType.name}`} />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Администрирование</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950">Редактирование вида задания</h1>
                        <p className="mt-3 text-sm text-slate-600">{taskType.name}</p>
                    </div>
                    <Link href="/admin/task-types" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        К списку
                    </Link>
                </div>

                <TaskTypeForm form={form} categoryOptions={categoryOptions} onSubmit={submit} submitLabel="Сохранить изменения" mode="edit" />
            </section>
        </DashboardLayout>
    );
}
