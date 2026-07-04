import { Head, Link, router } from '@inertiajs/react';
import DashboardLayout from '../../../Layouts/DashboardLayout';

const statusOptions = [
    { value: 'all', label: 'Все' },
    { value: 'active', label: 'Активные' },
    { value: 'inactive', label: 'Скрытые' },
];

export default function Index({ taskTypes = [], filters = {}, categoryOptions = [], summary = {} }) {
    const submitFilters = (event) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);
        const params = Object.fromEntries(formData.entries());

        router.get('/admin/task-types', params, { preserveState: true, preserveScroll: true });
    };

    return (
        <DashboardLayout>
            <Head title="Виды заданий" />

            <section className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
                    <div>
                        <p className="text-sm font-semibold uppercase text-blue-700">Администрирование</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950">Виды заданий</h1>
                        <p className="mt-3 max-w-2xl text-sm leading-6 text-slate-600">
                            Управление типами работ для биржи заданий и избранных направлений исполнителей.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <Link href="/admin/dashboard" className="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            В админ-панель
                        </Link>
                        <Link href="/admin/task-types/create" className="rounded-md bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                            Создать вид
                        </Link>
                    </div>
                </div>

                <div className="mt-6 grid gap-4 sm:grid-cols-3">
                    <SummaryCard title="Всего" value={summary.total ?? 0} />
                    <SummaryCard title="Активны" value={summary.active ?? 0} />
                    <SummaryCard title="Скрыты" value={summary.inactive ?? 0} />
                </div>

                <div className="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-900">
                    Физическое удаление справочников в MVP отключено. Скрытые виды не предлагаются в новых заданиях и публичных фильтрах.
                </div>

                <form onSubmit={submitFilters} className="mt-6 grid gap-3 rounded-lg border border-slate-200 bg-white p-4 shadow-sm lg:grid-cols-[1fr_220px_180px_auto]">
                    <input
                        name="q"
                        type="search"
                        defaultValue={filters.q ?? ''}
                        placeholder="Поиск по названию или slug"
                        className="rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    />
                    <select
                        name="category"
                        defaultValue={filters.category ?? ''}
                        className="rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                        <option value="">Все категории</option>
                        {categoryOptions.map((category) => (
                            <option key={category.id} value={category.id}>
                                {category.name}{category.is_active ? '' : ' (скрыта)'}
                            </option>
                        ))}
                    </select>
                    <select
                        name="status"
                        defaultValue={filters.status ?? 'all'}
                        className="rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    >
                        {statusOptions.map((option) => (
                            <option key={option.value} value={option.value}>{option.label}</option>
                        ))}
                    </select>
                    <button type="submit" className="rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Применить
                    </button>
                </form>

                <div className="mt-6 overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                    {taskTypes.length === 0 ? (
                        <div className="p-10 text-center">
                            <h2 className="text-2xl font-semibold text-slate-950">Виды заданий не найдены</h2>
                            <p className="mt-3 text-sm leading-6 text-slate-600">
                                Измените фильтр или создайте новый вид задания для активной категории.
                            </p>
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-slate-200 text-left text-sm">
                                <thead className="bg-slate-50 text-xs uppercase text-slate-500">
                                    <tr>
                                        <th className="px-4 py-3 font-semibold">Вид задания</th>
                                        <th className="px-4 py-3 font-semibold">Категория</th>
                                        <th className="px-4 py-3 font-semibold">Статус</th>
                                        <th className="px-4 py-3 text-right font-semibold">Порядок</th>
                                        <th className="px-4 py-3 text-right font-semibold">Задания</th>
                                        <th className="px-4 py-3 text-right font-semibold">Избранное</th>
                                        <th className="px-4 py-3 font-semibold">Действия</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {taskTypes.map((taskType) => (
                                        <tr key={taskType.id} className="align-top">
                                            <td className="px-4 py-4">
                                                <p className="font-semibold text-slate-950">{taskType.name}</p>
                                                <p className="mt-1 text-xs text-slate-500">{taskType.slug}</p>
                                                {taskType.description && <p className="mt-2 line-clamp-2 max-w-md text-xs leading-5 text-slate-500">{taskType.description}</p>}
                                            </td>
                                            <td className="px-4 py-4 text-slate-600">{taskType.category ?? '—'}</td>
                                            <td className="px-4 py-4">
                                                <StatusBadge active={taskType.is_active}>{taskType.status_label}</StatusBadge>
                                            </td>
                                            <td className="px-4 py-4 text-right text-slate-700">{taskType.sort_order}</td>
                                            <td className="px-4 py-4 text-right text-slate-700">{taskType.tasks_count}</td>
                                            <td className="px-4 py-4 text-right text-slate-700">{taskType.favorites_count}</td>
                                            <td className="px-4 py-4">
                                                <div className="flex flex-wrap gap-2">
                                                    <Link href={taskType.edit_url} className="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50">
                                                        Изменить
                                                    </Link>
                                                    <Link href={taskType.toggle_active_url} method="post" as="button" preserveScroll className="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50">
                                                        {taskType.is_active ? 'Скрыть' : 'Включить'}
                                                    </Link>
                                                    <Link href={taskType.move_up_url} method="post" as="button" preserveScroll className="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50" aria-label={`Поднять ${taskType.name}`}>
                                                        ↑
                                                    </Link>
                                                    <Link href={taskType.move_down_url} method="post" as="button" preserveScroll className="rounded-md border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-800 hover:bg-slate-50" aria-label={`Опустить ${taskType.name}`}>
                                                        ↓
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </section>
        </DashboardLayout>
    );
}

function SummaryCard({ title, value }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-sm text-slate-500">{title}</p>
            <p className="mt-3 text-2xl font-semibold text-slate-950">{value}</p>
        </article>
    );
}

function StatusBadge({ active, children }) {
    const tone = active ? 'bg-emerald-50 text-emerald-700 ring-emerald-200' : 'bg-slate-100 text-slate-700 ring-slate-200';

    return <span className={`rounded-md px-3 py-1 text-xs font-semibold ring-1 ${tone}`}>{children}</span>;
}
