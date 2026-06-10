import { Head, Link, useForm } from '@inertiajs/react';
import TaskForm from '../../../Components/Customer/TaskForm';
import DashboardLayout from '../../../Layouts/DashboardLayout';

export default function Edit({ task, categories, statusLabels }) {
    const form = useForm({
        category_id: task.category_id ?? '',
        title: task.title ?? '',
        description: task.description ?? '',
        budget_min: task.budget_min ?? '',
        budget_max: task.budget_max ?? '',
        deadline_at: task.deadline_at ?? '',
        publish: false,
    });

    const submit = (event) => {
        event.preventDefault();

        form.patch(`/customer/tasks/${task.id}`);
    };

    return (
        <DashboardLayout>
            <Head title="Редактирование задания" />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Статус: {statusLabels[task.status]}</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-normal text-slate-950">Редактирование задания</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Опубликованное задание останется опубликованным, если описание проходит проверку ContactGuard.
                        </p>
                    </div>
                    <Link href={`/customer/tasks/${task.id}`} className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        Открыть
                    </Link>
                </div>

                <TaskForm form={form} categories={categories} onSubmit={submit} submitLabel="Сохранить изменения" />
            </section>
        </DashboardLayout>
    );
}
