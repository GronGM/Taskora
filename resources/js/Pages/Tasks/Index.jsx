import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

const sortLabels = {
    newest: 'Новые сначала',
    urgent: 'Сначала срочные',
    budget_high: 'Бюджет выше',
    budget_low: 'Бюджет ниже',
    offers_low: 'Меньше откликов',
};
const focusClass = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950';
const inputClass = 'w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-blue-400 dark:focus:ring-blue-950';

export default function TasksIndex({
    categories,
    taskTypes,
    popularTaskTypes,
    tasks,
    filters,
    activeCategory,
    activeTaskType,
    viewer,
    favoritesSummary,
}) {
    const [filtersOpen, setFiltersOpen] = useState(false);
    const allCategories = categories.flatMap((category) => [category, ...(category.children ?? [])]);
    const favoriteHintVisible = viewer.is_performer && favoritesSummary.category_count === 0 && favoritesSummary.task_type_count === 0;

    return (
        <PublicLayout>
            <Head title="Биржа заданий" />

            <section className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                    <div className="flex flex-col justify-between gap-8 lg:flex-row lg:items-end">
                        <div>
                            <p className="text-sm font-semibold uppercase text-blue-700">Биржа</p>
                            <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">Биржа заданий</h1>
                            <p className="mt-4 max-w-3xl text-lg leading-8 text-slate-600">
                                Живая лента заказов: документы, презентации, расчеты, дизайн, сайты и бизнес-задачи. Фильтруйте по бюджету, сроку, категории и любимым направлениям.
                            </p>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Link href="/customer/tasks/create" className="rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                                    Разместить задание
                                </Link>
                                <Link href="/tasks" className="rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                                    Смотреть все задания
                                </Link>
                            </div>
                        </div>

                        <form action="/tasks" method="get" className="w-full rounded-lg border border-slate-200 bg-slate-50 p-3 lg:max-w-lg">
                            <div className="flex gap-2">
                                <input
                                    type="search"
                                    name="q"
                                    defaultValue={filters.q}
                                    placeholder="Поиск по названию или описанию"
                                    className="min-w-0 flex-1 rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                />
                                <button type="submit" className="rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                                    Найти
                                </button>
                            </div>
                            <input type="hidden" name="category" value={filters.category ?? ''} />
                            <input type="hidden" name="type" value={filters.type ?? ''} />
                            <input type="hidden" name="sort" value={filters.sort ?? 'newest'} />
                        </form>
                    </div>

                    {popularTaskTypes.length > 0 && (
                        <div className="mt-8">
                            <p className="text-sm font-semibold text-slate-700">Популярные виды</p>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {popularTaskTypes.map((type) => (
                                    <Link
                                        key={type.id}
                                        href={tasksUrl({ type: type.slug, category: '', favorite_types: '' })}
                                        className={`rounded-full border px-4 py-2 text-sm font-semibold ${
                                            filters.type === type.slug
                                                ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-200'
                                                : 'border-slate-200 bg-white text-slate-700 hover:border-blue-200 hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-slate-800 dark:hover:text-white'
                                        } ${focusClass}`}
                                    >
                                        {type.name}
                                    </Link>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </section>

            <section className="mx-auto grid max-w-7xl gap-8 px-4 py-8 sm:px-6 lg:grid-cols-[320px_1fr] lg:px-8">
                <aside>
                    <button
                        type="button"
                        onClick={() => setFiltersOpen((value) => !value)}
                        className={`mb-4 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 lg:hidden ${focusClass}`}
                    >
                        {filtersOpen ? 'Скрыть фильтры' : 'Фильтры'}
                    </button>

                    <div className={`${filtersOpen ? 'block' : 'hidden'} space-y-5 lg:block`}>
                        <FilterForm filters={filters} taskTypes={taskTypes} viewer={viewer} />

                        {favoriteHintVisible && (
                            <div className="rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-900 dark:border-blue-800 dark:bg-blue-950/70 dark:text-blue-100">
                                Добавляйте категории и виды заданий в избранное, чтобы быстрее находить подходящие заказы.
                            </div>
                        )}

                        {viewer.is_performer && (
                            <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                                <div className="flex items-start justify-between gap-4">
                                    <div>
                                        <h2 className="text-lg font-semibold text-slate-950 dark:text-white">Мои избранные направления</h2>
                                        <p className="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                            Категории: {favoritesSummary.category_count}. Виды заданий: {favoritesSummary.task_type_count}.
                                        </p>
                                    </div>
                                    <Link href="/performer/favorites" className={`shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200 ${focusClass}`}>
                                        Открыть
                                    </Link>
                                </div>
                                <div className="mt-4 grid gap-2">
                                    <Link href={tasksUrl({ favorite_categories: '1', category: '' })} className={`rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-200 hover:bg-slate-100 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-slate-800 dark:hover:text-white ${focusClass}`}>
                                        Задания из моих категорий
                                    </Link>
                                    <Link href={tasksUrl({ favorite_types: '1', type: '' })} className={`rounded-md border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700 hover:border-blue-200 hover:bg-slate-100 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-slate-800 dark:hover:text-white ${focusClass}`}>
                                        Задания моих видов
                                    </Link>
                                </div>
                            </section>
                        )}

                        <DirectionPanel
                            title="Категории"
                            items={allCategories}
                            activeSlug={filters.category}
                            type="category"
                            viewer={viewer}
                        />

                        <DirectionPanel
                            title="Виды заданий"
                            items={taskTypes}
                            activeSlug={filters.type}
                            type="taskType"
                            viewer={viewer}
                        />
                    </div>
                </aside>

                <div className="min-w-0">
                    <ActiveSummary
                        filters={filters}
                        activeCategory={activeCategory}
                        activeTaskType={activeTaskType}
                        count={tasks.length}
                    />

                    {tasks.length > 0 ? (
                        <div className="mt-6 grid gap-4">
                            {tasks.map((task) => (
                                <TaskCard key={task.id} task={task} />
                            ))}
                        </div>
                    ) : (
                        <EmptyTasks />
                    )}
                </div>
            </section>
        </PublicLayout>
    );
}

function FilterForm({ filters, taskTypes, viewer }) {
    return (
        <form action="/tasks" method="get" className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 className="text-lg font-semibold text-slate-950 dark:text-white">Фильтры</h2>
            <div className="mt-5 space-y-4">
                <input type="hidden" name="q" value={filters.q ?? ''} />
                <input type="hidden" name="category" value={filters.category ?? ''} />

                <Field label="Вид задания">
                    <select name="type" defaultValue={filters.type ?? ''} className={inputClass}>
                        <option value="">Все виды</option>
                        {taskTypes.map((type) => (
                            <option key={type.id} value={type.slug}>
                                {type.name}
                            </option>
                        ))}
                    </select>
                </Field>

                <div className="grid grid-cols-2 gap-3">
                    <Field label="Бюджет от">
                        <input className={inputClass} type="number" name="budget_min" min="0" defaultValue={filters.budget_min ?? ''} />
                    </Field>
                    <Field label="Бюджет до">
                        <input className={inputClass} type="number" name="budget_max" min="0" defaultValue={filters.budget_max ?? ''} />
                    </Field>
                </div>

                <Field label="Срок до">
                    <input className={inputClass} type="date" name="deadline_before" defaultValue={filters.deadline_before ?? ''} />
                </Field>

                <Field label="Сортировка">
                    <select name="sort" defaultValue={filters.sort ?? 'newest'} className={inputClass}>
                        {Object.entries(sortLabels).map(([value, label]) => (
                            <option key={value} value={value}>
                                {label}
                            </option>
                        ))}
                    </select>
                </Field>

                <label className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="without_offers" value="1" defaultChecked={filters.without_offers} className="h-4 w-4 rounded border-slate-300 text-blue-600" />
                    Только без откликов
                </label>
                <label className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="urgent" value="1" defaultChecked={filters.urgent} className="h-4 w-4 rounded border-slate-300 text-blue-600" />
                    Только срочные
                </label>

                {viewer.is_performer && (
                    <>
                        <label className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                            <input type="checkbox" name="favorite_categories" value="1" defaultChecked={filters.favorite_categories} className="h-4 w-4 rounded border-slate-300 text-blue-600" />
                            Мои категории
                        </label>
                        <label className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
                            <input type="checkbox" name="favorite_types" value="1" defaultChecked={filters.favorite_types} className="h-4 w-4 rounded border-slate-300 text-blue-600" />
                            Мои виды заданий
                        </label>
                    </>
                )}
            </div>

            <div className="mt-5 flex flex-col gap-2">
                <button type="submit" className="rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white hover:bg-blue-700">
                    Применить фильтры
                </button>
                <Link href="/tasks" className={`rounded-md border border-slate-300 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 ${focusClass}`}>
                    Сбросить фильтры
                </Link>
            </div>
        </form>
    );
}

function DirectionPanel({ title, items, activeSlug, type, viewer }) {
    const [expanded, setExpanded] = useState(false);
    const visibleItems = expanded ? items : items.slice(0, 7);

    return (
        <section className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center justify-between gap-3">
                <h2 className="text-lg font-semibold text-slate-950 dark:text-white">{title}</h2>
                {items.length > 7 && (
                    <button type="button" onClick={() => setExpanded((value) => !value)} className={`text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200 ${focusClass}`}>
                        {expanded ? 'Свернуть' : 'Показать все'}
                    </button>
                )}
            </div>
            <div className="mt-4 space-y-2">
                {visibleItems.map((item) => (
                    <div
                        key={`${type}-${item.id}`}
                        className={`flex items-center justify-between gap-2 rounded-md border px-3 py-2 ${
                            activeSlug === item.slug || item.is_favorited
                                ? 'border-blue-100 bg-blue-50 dark:border-blue-700 dark:bg-blue-950/70'
                                : 'border-slate-100 bg-slate-50 dark:border-slate-700 dark:bg-slate-950'
                        }`}
                    >
                        <Link
                            href={type === 'category' ? tasksUrl({ category: item.slug, favorite_categories: '' }) : tasksUrl({ type: item.slug, favorite_types: '' })}
                            className={`min-w-0 flex-1 rounded-md hover:text-blue-700 dark:hover:text-white ${focusClass}`}
                        >
                            <span className="block truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{item.name}</span>
                            <span className="text-xs text-slate-500 dark:text-slate-400">{item.task_count} заданий</span>
                        </Link>
                        {viewer.is_performer && (
                            <FavoriteDirectionButton item={item} />
                        )}
                    </div>
                ))}
            </div>
        </section>
    );
}

function FavoriteDirectionButton({ item }) {
    return (
        <Link
            href={item.is_favorited ? item.favorite_destroy_url : item.favorite_store_url}
            method={item.is_favorited ? 'delete' : 'post'}
            as="button"
            preserveScroll
            className={`shrink-0 rounded-md px-2 py-1 text-xs font-semibold ${
                item.is_favorited
                    ? 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400'
                    : 'bg-white text-blue-700 ring-1 ring-blue-200 hover:bg-blue-50 dark:bg-slate-900 dark:text-blue-200 dark:ring-blue-800 dark:hover:bg-slate-800'
            } ${focusClass}`}
        >
            {item.is_favorited ? 'В избранном' : 'В избранное'}
        </Link>
    );
}

function ActiveSummary({ filters, activeCategory, activeTaskType, count }) {
    const chips = [
        filters.q && ['Поиск', filters.q, { q: '' }],
        activeCategory && ['Категория', activeCategory.name, { category: '' }],
        activeTaskType && ['Вид', activeTaskType.name, { type: '' }],
        filters.budget_min !== null && ['Бюджет от', `${filters.budget_min} ₽`, { budget_min: '' }],
        filters.budget_max !== null && ['Бюджет до', `${filters.budget_max} ₽`, { budget_max: '' }],
        filters.deadline_before && ['Срок до', filters.deadline_before, { deadline_before: '' }],
        filters.without_offers && ['Без откликов', 'да', { without_offers: '' }],
        filters.urgent && ['Срочные', 'да', { urgent: '' }],
        filters.favorite_categories && ['Мои категории', 'да', { favorite_categories: '' }],
        filters.favorite_types && ['Мои виды', 'да', { favorite_types: '' }],
        filters.sort !== 'newest' && ['Сортировка', sortLabels[filters.sort], { sort: 'newest' }],
    ].filter(Boolean);

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p className="text-sm font-semibold text-blue-700">Найдено заданий: {count}</p>
                    <h2 className="mt-1 text-2xl font-semibold text-slate-950">
                        {activeCategory?.name ?? 'Все направления'}
                    </h2>
                </div>
                <Link href="/tasks" className="text-sm font-semibold text-blue-700 hover:text-blue-800">
                    Сбросить фильтры
                </Link>
            </div>
            {chips.length > 0 && (
                <div className="mt-4 flex flex-wrap gap-2">
                    {chips.map(([label, value, reset]) => (
                        <Link key={label} href={tasksUrl(reset)} className="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200">
                            {label}: {value} ×
                        </Link>
                    ))}
                </div>
            )}
        </div>
    );
}

function TaskCard({ task }) {
    return (
        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex flex-col gap-5 xl:flex-row xl:items-start xl:justify-between">
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-2">
                        {task.badges.urgent && <Badge tone="red">Срочно</Badge>}
                        {task.badges.without_offers && <Badge tone="emerald">Без откликов</Badge>}
                        {task.badges.new && <Badge tone="blue">Новый</Badge>}
                        {task.badges.favorited && <Badge tone="slate">В избранном</Badge>}
                        {task.category?.name && <Badge tone="blue">{task.category.name}</Badge>}
                        {task.task_type?.name && <Badge tone="slate">{task.task_type.name}</Badge>}
                    </div>
                    <h2 className="mt-4 text-2xl font-semibold text-slate-950">{task.title}</h2>
                    <p className="mt-3 text-sm leading-6 text-slate-600">{task.excerpt}</p>
                    <div className="mt-4 grid gap-3 text-sm text-slate-600 sm:grid-cols-2 lg:grid-cols-5">
                        <p>Бюджет: <span className="font-semibold text-slate-950">{task.budget_label}</span></p>
                        <p>Срок: <span className="font-semibold text-slate-950">{task.deadline_at ?? 'Не указан'}</span></p>
                        <p>Отклики: <span className="font-semibold text-slate-950">{task.offers_count}</span></p>
                        <p>Заказчик: <span className="font-semibold text-slate-950">{task.customer?.name ?? 'Заказчик'}</span></p>
                        <p>Опубликовано: <span className="font-semibold text-slate-950">{task.published_at ?? 'Недавно'}</span></p>
                    </div>
                </div>
                <div className="flex shrink-0 flex-col gap-2 sm:flex-row xl:flex-col">
                    <Link href={task.url} className="inline-flex justify-center rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                        Подробнее
                    </Link>
                    {task.favorite.can_favorite && (
                        <Link
                            href={task.favorite.is_favorited ? task.favorite.destroy_url : task.favorite.store_url}
                            method={task.favorite.is_favorited ? 'delete' : 'post'}
                            as="button"
                            preserveScroll
                            className={`inline-flex justify-center rounded-md px-5 py-3 text-sm font-semibold ${
                                task.favorite.is_favorited
                                    ? 'border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100'
                                    : 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50'
                            }`}
                        >
                            {task.favorite.is_favorited ? 'В избранном' : 'В избранное'}
                        </Link>
                    )}
                    {task.favorite.show_login_cta && (
                        <Link href={task.favorite.login_url} className="inline-flex justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50">
                            Войти, чтобы сохранить
                        </Link>
                    )}
                </div>
            </div>
        </article>
    );
}

function Badge({ tone, children }) {
    const classes = {
        red: 'bg-red-50 text-red-700',
        emerald: 'bg-emerald-50 text-emerald-700',
        blue: 'bg-blue-50 text-blue-700',
        slate: 'bg-slate-100 text-slate-700',
    };

    return <span className={`rounded-md px-3 py-1 text-xs font-semibold ${classes[tone]}`}>{children}</span>;
}

function Field({ label, children }) {
    return (
        <label className="block">
            <span className="text-sm font-semibold text-slate-900 dark:text-slate-100">{label}</span>
            <span className="mt-2 block">{children}</span>
        </label>
    );
}

function EmptyTasks() {
    return (
        <div className="mt-6 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center">
            <h2 className="text-2xl font-semibold text-slate-950">По этим фильтрам заданий пока нет</h2>
            <p className="mt-3 text-sm leading-6 text-slate-600">
                Попробуйте расширить бюджет, убрать срочность или выбрать другое направление.
            </p>
            <Link href="/tasks" className="mt-6 inline-flex rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                Сбросить фильтры
            </Link>
        </div>
    );
}

function tasksUrl(overrides = {}) {
    const current = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');

    Object.entries(overrides).forEach(([key, value]) => {
        if (value === '' || value === null || value === undefined || value === false) {
            current.delete(key);
        } else {
            current.set(key, value);
        }
    });

    const query = current.toString();

    return query ? `/tasks?${query}` : '/tasks';
}
