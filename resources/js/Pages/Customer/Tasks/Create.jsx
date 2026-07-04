import { Head, Link, useForm } from '@inertiajs/react';
import TaskWizard from '../../../Components/Customer/TaskWizard';
import DashboardLayout from '../../../Layouts/DashboardLayout';

export default function Create({ categories }) {
    const form = useForm({
        category_id: '',
        task_type_id: '',
        title: '',
        description: '',
        budget_min: '',
        budget_max: '',
        deadline_at: '',
        review_hold_days: 10,
        publish: false,
    });

    const submit = (event, publish) => {
        event.preventDefault();

        form.transform((data) => ({ ...data, publish }));
        form.post('/customer/tasks');
    };

    return (
        <DashboardLayout>
            <Head title="Новое задание" />

            <section className="mx-auto max-w-5xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="mb-8 flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Заказчик</p>
                        <h1 className="mt-2 text-4xl font-semibold tracking-tight text-slate-950">Новое задание</h1>
                        <p className="mt-4 max-w-2xl text-sm leading-6 text-slate-600">
                            Три шага: опишите задачу, укажите условия, проверьте и опубликуйте.
                        </p>
                    </div>
                    <Link href="/customer/tasks" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                        К списку
                    </Link>
                </div>

                <TaskWizard form={form} categories={categories} onSubmit={submit} />
            </section>
        </DashboardLayout>
    );
}
