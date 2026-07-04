import { Head, Link } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';
import PublicLayout from '../../Layouts/PublicLayout';

const sortLabels = {
    newest: 'Новые сначала',
    urgent: 'Сначала срочные',
    budget_high: 'Бюджет выше',
    budget_low: 'Бюджет ниже',
    offers_low: 'Меньше откликов',
};

const focusClass = 'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:focus-visible:ring-blue-300 dark:focus-visible:ring-offset-slate-950';
const inputClass = 'w-full rounded-md border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-950 outline-none transition placeholder:text-slate-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-100 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-500 dark:focus:border-blue-400 dark:focus:ring-blue-950';

export default function TasksIndex({
    categories = [],
    taskTypes = [],
    popularTaskTypes = [],
    tasks = [],
    pagination = null,
    weeklyNewTasks = 0,
    filters = {},
    activeCategory = null,
    activeTaskType = null,
    activeCategories = [],
    activeTaskTypes = [],
    viewer = {},
    favoritesSummary = {},
}) {
    const [filtersOpen, setFiltersOpen] = useState(false);
    const allCategories = useMemo(
        () => categories.flatMap((category) => [category, ...(category.children ?? [])]),
        [categories],
    );
    const selectedCategorySlugs = getArrayFilter(filters, 'categories', 'category');
    const selectedTaskTypeSlugs = getArrayFilter(filters, 'task_types', 'type');
    const visibleActiveCategories = activeCategories.length > 0 ? activeCategories : activeCategory ? [activeCategory] : [];
    const visibleActiveTaskTypes = activeTaskTypes.length > 0 ? activeTaskTypes : activeTaskType ? [activeTaskType] : [];
    const activeFilterCount = countActiveFilters(filters);
    const favoriteHintVisible = viewer.is_performer && (favoritesSummary.category_count ?? 0) === 0 && (favoritesSummary.task_type_count ?? 0) === 0;

    return (
        <PublicLayout>
            <Head title="Биржа заданий" />

            <section className="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-950">
                <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8 lg:py-12">
                    <div className="flex flex-col justify-between gap-8 lg:flex-row lg:items-end">
                        <div>
                            <p className="text-sm font-semibold uppercase text-blue-700 dark:text-blue-300">Биржа</p>
                            <h1 className="mt-3 text-4xl font-semibold tracking-tight text-slate-950 dark:text-white">Биржа заданий</h1>
                            <p className="mt-4 max-w-3xl text-lg leading-8 text-slate-600 dark:text-slate-300">
                                Живая лента заказов: документы, презентации, расчеты, дизайн, сайты и бизнес-задачи. Фильтруйте по бюджету, сроку и любимым направлениям.
                            </p>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Link href="/customer/tasks/create" className={`rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 ${focusClass}`}>
                                    Разместить задание
                                </Link>
                                <Link href="/tasks" className={`rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 ${focusClass}`}>
                                    Смотреть все задания
                                </Link>
                            </div>
                        </div>

                        <form action="/tasks" method="get" className="w-full rounded-lg border border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-900 lg:max-w-lg">
                            <div className="flex gap-2">
                                <input
                                    type="search"
                                    name="q"
                                    defaultValue={filters.q ?? ''}
                                    placeholder="Поиск по названию или описанию"
                                    className={inputClass}
                                />
                                <button type="submit" className={`rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 ${focusClass}`}>
                                    Найти
                                </button>
                            </div>
                            {selectedCategorySlugs.map((slug) => (
                                <input key={`hero-category-${slug}`} type="hidden" name="categories[]" value={slug} />
                            ))}
                            {selectedTaskTypeSlugs.map((slug) => (
                                <input key={`hero-task-type-${slug}`} type="hidden" name="task_types[]" value={slug} />
                            ))}
                            <input type="hidden" name="sort" value={filters.sort ?? 'newest'} />
                        </form>
                    </div>

                    {popularTaskTypes.length > 0 && (
                        <div className="mt-8">
                            <p className="text-sm font-semibold text-slate-700 dark:text-slate-200">Популярные виды</p>
                            <div className="mt-3 flex flex-wrap gap-2">
                                {popularTaskTypes.map((type) => {
                                    const isActive = selectedTaskTypeSlugs.includes(type.slug);

                                    return (
                                        <Link
                                            key={type.id}
                                            href={tasksUrl({ task_types: [type.slug], favorite_types: '' })}
                                            className={`rounded-full border px-4 py-2 text-sm font-semibold transition ${
                                                isActive
                                                    ? 'border-blue-200 bg-blue-50 text-blue-700 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-200'
                                                    : 'border-slate-200 bg-white text-slate-700 hover:border-blue-200 hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-slate-800 dark:hover:text-white'
                                            } ${focusClass}`}
                                        >
                                            {type.name}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    )}
                </div>
            </section>

            <section className="mx-auto grid max-w-7xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[minmax(280px,360px)_1fr] lg:px-8">
                <aside>
                    <button
                        type="button"
                        onClick={() => setFiltersOpen((value) => !value)}
                        className={`mb-4 flex w-full items-center justify-between gap-3 rounded-md border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 lg:hidden ${focusClass}`}
                    >
                        <span>{filtersOpen ? 'Скрыть фильтры' : 'Фильтры'}</span>
                        {activeFilterCount > 0 && (
                            <span className="rounded-full bg-blue-600 px-2 py-0.5 text-xs font-semibold text-white">
                                {activeFilterCount}
                            </span>
                        )}
                    </button>

                    <div id="task-filters" className={`${filtersOpen ? 'block' : 'hidden'} lg:block`}>
                        <FilterForm
                            filters={filters}
                            categories={allCategories}
                            taskTypes={taskTypes}
                            selectedCategorySlugs={selectedCategorySlugs}
                            selectedTaskTypeSlugs={selectedTaskTypeSlugs}
                            viewer={viewer}
                            favoritesSummary={favoritesSummary}
                        />

                        {favoriteHintVisible && (
                            <div className="mt-5 rounded-lg border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-900 dark:border-blue-800 dark:bg-blue-950/70 dark:text-blue-100">
                                Добавляйте категории и виды заданий в избранное, чтобы быстрее открывать подходящие заказы.
                            </div>
                        )}
                    </div>
                </aside>

                <div className="min-w-0">
                    <ActiveSummary
                        filters={filters}
                        activeCategories={visibleActiveCategories}
                        activeTaskTypes={visibleActiveTaskTypes}
                        selectedCategorySlugs={selectedCategorySlugs}
                        selectedTaskTypeSlugs={selectedTaskTypeSlugs}
                        count={pagination?.total ?? tasks.length}
                        weeklyNewTasks={weeklyNewTasks}
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

                    {pagination && pagination.last_page > 1 && (
                        <nav aria-label="Пагинация заданий" className="mt-8 flex items-center justify-between gap-4">
                            <div>
                                {pagination.prev_page_url && (
                                    <Link
                                        href={pagination.prev_page_url}
                                        preserveScroll
                                        className={`rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 ${focusClass}`}
                                    >
                                        Назад
                                    </Link>
                                )}
                            </div>
                            <span className="text-sm text-slate-500 dark:text-slate-400">
                                Страница {pagination.current_page} из {pagination.last_page}
                            </span>
                            <div>
                                {pagination.next_page_url && (
                                    <Link
                                        href={pagination.next_page_url}
                                        preserveScroll
                                        className={`rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 ${focusClass}`}
                                    >
                                        Вперед
                                    </Link>
                                )}
                            </div>
                        </nav>
                    )}
                </div>
            </section>
        </PublicLayout>
    );
}

function FilterForm({
    filters,
    categories,
    taskTypes,
    selectedCategorySlugs,
    selectedTaskTypeSlugs,
    viewer,
    favoritesSummary,
}) {
    const [categorySearch, setCategorySearch] = useState('');
    const [taskTypeSearch, setTaskTypeSearch] = useState('');
    const [draftCategorySlugs, setDraftCategorySlugs] = useState(selectedCategorySlugs);
    const [draftTaskTypeSlugs, setDraftTaskTypeSlugs] = useState(selectedTaskTypeSlugs);
    const visibleCategories = useMemo(() => filterDirections(categories, categorySearch), [categories, categorySearch]);
    const visibleTaskTypes = useMemo(() => filterDirections(taskTypes, taskTypeSearch), [taskTypes, taskTypeSearch]);
    const canUseFavorites = viewer.is_performer === true;

    useEffect(() => {
        setDraftCategorySlugs(selectedCategorySlugs);
    }, [selectedCategorySlugs.join('|')]);

    useEffect(() => {
        setDraftTaskTypeSlugs(selectedTaskTypeSlugs);
    }, [selectedTaskTypeSlugs.join('|')]);

    return (
        <form action="/tasks" method="get" className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-start justify-between gap-4">
                <div>
                    <h2 className="text-lg font-semibold text-slate-950 dark:text-white">Фильтры</h2>
                    <p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                        Категории и виды можно выбирать вместе. Конкретный выбор важнее быстрых фильтров по избранному.
                    </p>
                </div>
                <Link href="/tasks" className={`shrink-0 text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200 ${focusClass}`}>
                    Сбросить
                </Link>
            </div>

            <div className="mt-5 space-y-5">
                <Field label="Поиск по заданиям">
                    <input className={inputClass} type="search" name="q" defaultValue={filters.q ?? ''} placeholder="Название или описание" />
                </Field>

                <MultiSelectList
                    label="Категории"
                    name="categories[]"
                    items={visibleCategories}
                    selectedSlugs={draftCategorySlugs}
                    onToggle={(slug) => setDraftCategorySlugs((slugs) => toggleSlug(slugs, slug))}
                    searchValue={categorySearch}
                    onSearchChange={setCategorySearch}
                    searchLabel="Найти категорию"
                    searchPlaceholder="Найти категорию"
                    emptyText="Ничего не найдено"
                    viewer={viewer}
                    guestHint="Избранные категории доступны исполнителям после входа."
                    testId="task-filter-categories"
                />

                <MultiSelectList
                    label="Виды заданий"
                    name="task_types[]"
                    items={visibleTaskTypes}
                    selectedSlugs={draftTaskTypeSlugs}
                    onToggle={(slug) => setDraftTaskTypeSlugs((slugs) => toggleSlug(slugs, slug))}
                    searchValue={taskTypeSearch}
                    onSearchChange={setTaskTypeSearch}
                    searchLabel="Найти вид задания"
                    searchPlaceholder="Найти вид задания"
                    emptyText="Ничего не найдено"
                    viewer={viewer}
                    guestHint="Избранные виды заданий доступны исполнителям после входа."
                    testId="task-filter-types"
                />

                {canUseFavorites && (
                    <BulkTaskTypeFavoriteButton
                        selectedTaskTypeSlugs={draftTaskTypeSlugs}
                        url={viewer.bulk_task_type_favorite_url}
                    />
                )}

                {canUseFavorites && (
                    <section className="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950">
                        <h3 className="text-sm font-semibold text-slate-950 dark:text-white">Избранные направления</h3>
                        <p className="mt-1 text-xs leading-5 text-slate-500 dark:text-slate-400">
                            Категории: {favoritesSummary.category_count ?? 0}. Виды заданий: {favoritesSummary.task_type_count ?? 0}.
                        </p>
                        <div className="mt-3 grid gap-2">
                            <Link href={tasksUrl({ categories: [], favorite_categories: '1' })} className={`rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-slate-800 dark:hover:text-white ${focusClass}`}>
                                Мои категории
                            </Link>
                            <Link href={tasksUrl({ task_types: [], favorite_types: '1' })} className={`rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-slate-800 dark:hover:text-white ${focusClass}`}>
                                Мои виды заданий
                            </Link>
                            <Link href="/tasks" className={`rounded-md border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-200 hover:bg-slate-50 hover:text-slate-950 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-700 dark:hover:bg-slate-800 dark:hover:text-white ${focusClass}`}>
                                Все задания
                            </Link>
                        </div>
                    </section>
                )}

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

                <CheckboxField name="without_offers" checked={filters.without_offers}>
                    Только без откликов
                </CheckboxField>
                <CheckboxField name="urgent" checked={filters.urgent}>
                    Только срочные
                </CheckboxField>

                {canUseFavorites && (
                    <>
                        <CheckboxField name="favorite_categories" checked={filters.favorite_categories}>
                            Только мои категории
                        </CheckboxField>
                        <CheckboxField name="favorite_types" checked={filters.favorite_types}>
                            Только мои виды заданий
                        </CheckboxField>
                    </>
                )}
            </div>

            <div className="mt-5 flex flex-col gap-2">
                <button type="submit" className={`rounded-md bg-blue-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400 ${focusClass}`}>
                    Применить фильтры
                </button>
                <Link href="/tasks" className={`rounded-md border border-slate-300 bg-white px-4 py-3 text-center text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 ${focusClass}`}>
                    Сбросить фильтры
                </Link>
            </div>
        </form>
    );
}

function MultiSelectList({
    label,
    name,
    items,
    selectedSlugs,
    onToggle,
    searchValue,
    onSearchChange,
    searchLabel,
    searchPlaceholder,
    emptyText,
    viewer,
    guestHint,
    testId,
}) {
    const showGuestHint = !viewer.role;
    const visibleSlugs = new Set(items.map((item) => item.slug));
    const hiddenSelectedSlugs = selectedSlugs.filter((slug) => !visibleSlugs.has(slug));

    return (
        <section data-testid={testId} className="rounded-md border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-950">
            {hiddenSelectedSlugs.map((slug) => (
                <input key={`${name}-hidden-${slug}`} type="hidden" name={name} value={slug} />
            ))}
            <div className="flex items-center justify-between gap-3">
                <h3 className="text-sm font-semibold text-slate-950 dark:text-white">{label}</h3>
                {selectedSlugs.length > 0 && (
                    <span className="rounded-full bg-blue-600 px-2 py-0.5 text-xs font-semibold text-white">
                        {selectedSlugs.length}
                    </span>
                )}
            </div>
            <label className="mt-3 block">
                <span className="sr-only">{searchLabel}</span>
                <input
                    type="search"
                    value={searchValue}
                    onChange={(event) => onSearchChange(event.target.value)}
                    placeholder={searchPlaceholder}
                    className={inputClass}
                    aria-label={searchLabel}
                />
            </label>
            <div className="mt-3 max-h-72 space-y-2 overflow-y-auto pr-1">
                {items.length > 0 ? (
                    items.map((item) => (
                        <DirectionOption
                            key={`${name}-${item.id}`}
                            item={item}
                            name={name}
                            checked={selectedSlugs.includes(item.slug)}
                            onToggle={onToggle}
                            viewer={viewer}
                        />
                    ))
                ) : (
                    <p className="rounded-md border border-dashed border-slate-300 bg-white px-3 py-3 text-sm text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400">
                        {emptyText}
                    </p>
                )}
            </div>
            {showGuestHint && (
                <p className="mt-3 text-xs leading-5 text-slate-500 dark:text-slate-400">
                    {guestHint}
                </p>
            )}
        </section>
    );
}

function DirectionOption({ item, name, checked, onToggle, viewer }) {
    return (
        <div
            className={`flex items-start justify-between gap-2 rounded-md border px-3 py-2 transition ${
                checked || item.is_favorited
                    ? 'border-blue-100 bg-blue-50 dark:border-blue-700 dark:bg-blue-950/70'
                    : 'border-slate-200 bg-white hover:border-blue-200 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-blue-700 dark:hover:bg-slate-800'
            }`}
        >
            <label className="flex min-w-0 flex-1 items-start gap-3">
                <input
                    type="checkbox"
                    name={name}
                    value={item.slug}
                    checked={checked}
                    onChange={() => onToggle(item.slug)}
                    className="mt-1 h-4 w-4 shrink-0 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-950"
                />
                <span className="min-w-0">
                    <span className="block truncate text-sm font-semibold text-slate-900 dark:text-slate-100">{item.name}</span>
                    <span className="text-xs text-slate-500 dark:text-slate-400">
                        {item.category?.name ? `${item.category.name} · ` : ''}{item.task_count} заданий
                    </span>
                </span>
            </label>
            {viewer.is_performer && item.can_favorite && (
                <FavoriteDirectionButton item={item} />
            )}
        </div>
    );
}

function FavoriteDirectionButton({ item }) {
    return (
        <Link
            href={item.is_favorited ? item.favorite_destroy_url : item.favorite_store_url}
            method={item.is_favorited ? 'delete' : 'post'}
            as="button"
            type="button"
            preserveScroll
            className={`shrink-0 rounded-md px-2 py-1 text-xs font-semibold transition ${
                item.is_favorited
                    ? 'bg-blue-600 text-white hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-400'
                    : 'bg-white text-blue-700 ring-1 ring-blue-200 hover:bg-blue-50 dark:bg-slate-950 dark:text-blue-200 dark:ring-blue-800 dark:hover:bg-slate-800'
            } ${focusClass}`}
        >
            {item.is_favorited ? 'В избранном' : 'В избранное'}
        </Link>
    );
}

function BulkTaskTypeFavoriteButton({ selectedTaskTypeSlugs, url }) {
    if (selectedTaskTypeSlugs.length === 0 || !url) {
        return (
            <button
                type="button"
                disabled
                className="w-full rounded-md border border-slate-200 bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-400 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-500"
            >
                Добавить выбранные виды в избранное
            </button>
        );
    }

    return (
        <Link
            href={url}
            method="post"
            as="button"
            type="button"
            data={{ task_type_slugs: selectedTaskTypeSlugs }}
            preserveScroll
            className={`w-full rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-semibold text-blue-700 transition hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-200 dark:hover:bg-blue-900 ${focusClass}`}
        >
            Добавить выбранные виды в избранное
        </Link>
    );
}

function ActiveSummary({ filters, activeCategories, activeTaskTypes, selectedCategorySlugs, selectedTaskTypeSlugs, count , weeklyNewTasks = 0 }) {
    const chips = [
        filters.q && ['search', 'Поиск', filters.q, { q: '' }],
        ...activeCategories.map((category) => [
            `category-${category.slug}`,
            'Категория',
            category.name,
            { categories: selectedCategorySlugs.filter((slug) => slug !== category.slug) },
        ]),
        ...activeTaskTypes.map((taskType) => [
            `task-type-${taskType.slug}`,
            'Вид',
            taskType.name,
            { task_types: selectedTaskTypeSlugs.filter((slug) => slug !== taskType.slug) },
        ]),
        filters.budget_min !== null && ['budget-min', 'Бюджет от', `${filters.budget_min} ₽`, { budget_min: '' }],
        filters.budget_max !== null && ['budget-max', 'Бюджет до', `${filters.budget_max} ₽`, { budget_max: '' }],
        filters.deadline_before && ['deadline', 'Срок до', filters.deadline_before, { deadline_before: '' }],
        filters.without_offers && ['without-offers', 'Без откликов', 'да', { without_offers: '' }],
        filters.urgent && ['urgent', 'Срочные', 'да', { urgent: '' }],
        filters.favorite_categories && ['favorite-categories', 'Мои категории', 'да', { favorite_categories: '' }],
        filters.favorite_types && ['favorite-types', 'Мои виды', 'да', { favorite_types: '' }],
        filters.sort !== 'newest' && ['sort', 'Сортировка', sortLabels[filters.sort], { sort: 'newest' }],
    ].filter(Boolean);

    const title = activeCategories.length === 1
        ? activeCategories[0].name
        : activeCategories.length > 1
            ? `${activeCategories.length} категории`
            : 'Все направления';

    return (
        <div className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <p className="text-sm font-semibold text-blue-700 dark:text-blue-300">
                        Найдено заданий: {count}
                        {weeklyNewTasks > 0 && <span className="ml-2 font-normal text-slate-500 dark:text-slate-400">· за неделю опубликовано {weeklyNewTasks}</span>}
                    </p>
                    <h2 className="mt-1 text-2xl font-semibold text-slate-950 dark:text-white">{title}</h2>
                </div>
                <Link href="/tasks" className={`text-sm font-semibold text-blue-700 hover:text-blue-800 dark:text-blue-300 dark:hover:text-blue-200 ${focusClass}`}>
                    Сбросить фильтры
                </Link>
            </div>
            {chips.length > 0 && (
                <div className="mt-4 flex flex-wrap gap-2">
                    {chips.map(([key, label, value, reset]) => (
                        <Link
                            key={key}
                            href={tasksUrl(reset)}
                            className={`rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700 ${focusClass}`}
                        >
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
        <article className="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
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
                    <h2 className="mt-4 text-2xl font-semibold text-slate-950 dark:text-white">{task.title}</h2>
                    <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{task.excerpt}</p>
                    <div className="mt-4 grid gap-3 text-sm text-slate-600 dark:text-slate-300 sm:grid-cols-2 lg:grid-cols-5">
                        <p>Бюджет: <span className="font-semibold text-slate-950 dark:text-white">{task.budget_label}</span></p>
                        <p>Срок: <span className="font-semibold text-slate-950 dark:text-white">{task.deadline_at ?? 'Не указан'}</span></p>
                        <p>Отклики: <span className="font-semibold text-slate-950 dark:text-white">{task.offers_count}</span></p>
                        <p>Проверка: <span className="font-semibold text-slate-950 dark:text-white">{task.review_hold_days} дн.</span></p>
                        <p>Заказчик: <span className="font-semibold text-slate-950 dark:text-white">{task.customer?.name ?? 'Заказчик'}</span></p>
                        <p>Опубликовано: <span className="font-semibold text-slate-950 dark:text-white">{task.published_at ?? 'Недавно'}</span></p>
                    </div>
                </div>
                <div className="flex shrink-0 flex-col gap-2 sm:flex-row xl:flex-col">
                    <Link href={task.url} className={`inline-flex justify-center rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 ${focusClass}`}>
                        Подробнее
                    </Link>
                    {task.favorite.can_favorite && (
                        <Link
                            href={task.favorite.is_favorited ? task.favorite.destroy_url : task.favorite.store_url}
                            method={task.favorite.is_favorited ? 'delete' : 'post'}
                            as="button"
                            preserveScroll
                            className={`inline-flex justify-center rounded-md px-5 py-3 text-sm font-semibold transition ${
                                task.favorite.is_favorited
                                    ? 'border border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 dark:border-blue-700 dark:bg-blue-950 dark:text-blue-200 dark:hover:bg-blue-900'
                                    : 'border border-slate-300 bg-white text-slate-800 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800'
                            } ${focusClass}`}
                        >
                            {task.favorite.is_favorited ? 'В избранном' : 'В избранное'}
                        </Link>
                    )}
                    {task.favorite.show_login_cta && (
                        <Link href={task.favorite.login_url} className={`inline-flex justify-center rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800 ${focusClass}`}>
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
        red: 'bg-red-50 text-red-700 dark:bg-red-950 dark:text-red-200',
        emerald: 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
        blue: 'bg-blue-50 text-blue-700 dark:bg-blue-950 dark:text-blue-200',
        slate: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
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

function CheckboxField({ name, checked, children }) {
    return (
        <label className="flex items-center gap-2 text-sm font-medium text-slate-700 dark:text-slate-300">
            <input type="checkbox" name={name} value="1" defaultChecked={checked} className="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-950" />
            {children}
        </label>
    );
}

function EmptyTasks() {
    return (
        <div className="mt-6 rounded-lg border border-dashed border-slate-300 bg-white p-10 text-center dark:border-slate-700 dark:bg-slate-900">
            <h2 className="text-2xl font-semibold text-slate-950 dark:text-white">По этим фильтрам заданий пока нет</h2>
            <p className="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">
                Попробуйте расширить бюджет, убрать срочность или выбрать другое направление.
            </p>
            <Link href="/tasks" className={`mt-6 inline-flex rounded-md bg-slate-950 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-white dark:text-slate-950 dark:hover:bg-slate-200 ${focusClass}`}>
                Сбросить фильтры
            </Link>
        </div>
    );
}

function filterDirections(items, query) {
    const normalizedQuery = query.trim().toLowerCase();

    if (normalizedQuery === '') {
        return items;
    }

    return items.filter((item) => {
        const haystack = `${item.name ?? ''} ${item.category?.name ?? ''}`.toLowerCase();

        return haystack.includes(normalizedQuery);
    });
}

function toggleSlug(slugs, slug) {
    return slugs.includes(slug)
        ? slugs.filter((value) => value !== slug)
        : [...slugs, slug];
}

function getArrayFilter(filters, key, legacyKey) {
    const values = Array.isArray(filters?.[key]) ? filters[key].filter(Boolean) : [];

    if (values.length > 0) {
        return values;
    }

    return filters?.[legacyKey] ? [filters[legacyKey]] : [];
}

function countActiveFilters(filters) {
    const categoryCount = getArrayFilter(filters, 'categories', 'category').length;
    const taskTypeCount = getArrayFilter(filters, 'task_types', 'type').length;

    return [
        filters.q,
        categoryCount > 0,
        taskTypeCount > 0,
        filters.budget_min !== null && filters.budget_min !== undefined,
        filters.budget_max !== null && filters.budget_max !== undefined,
        filters.deadline_before,
        filters.without_offers,
        filters.urgent,
        filters.favorite_categories,
        filters.favorite_types,
        filters.sort && filters.sort !== 'newest',
    ].filter(Boolean).length;
}

function tasksUrl(overrides = {}) {
    const current = new URLSearchParams(typeof window !== 'undefined' ? window.location.search : '');

    Object.entries(overrides).forEach(([key, value]) => {
        if (key === 'categories') {
            deleteParamFamily(current, 'categories');
            current.delete('category');
            appendArrayValues(current, 'categories[]', value);
            return;
        }

        if (key === 'task_types') {
            deleteParamFamily(current, 'task_types');
            current.delete('type');
            appendArrayValues(current, 'task_types[]', value);
            return;
        }

        if (key === 'category') {
            current.delete('category');
            deleteParamFamily(current, 'categories');
            return;
        }

        if (key === 'type') {
            current.delete('type');
            deleteParamFamily(current, 'task_types');
            return;
        }

        if (value === '' || value === null || value === undefined || value === false || (Array.isArray(value) && value.length === 0)) {
            current.delete(key);
        } else {
            current.set(key, value);
        }
    });

    const query = current.toString();

    return query ? `/tasks?${query}` : '/tasks';
}

function appendArrayValues(params, key, values) {
    if (!Array.isArray(values)) {
        return;
    }

    values.filter(Boolean).forEach((value) => params.append(key, value));
}

function deleteParamFamily(params, key) {
    [...params.keys()].forEach((paramKey) => {
        if (paramKey === key || paramKey === `${key}[]` || paramKey.startsWith(`${key}[`)) {
            params.delete(paramKey);
        }
    });
}
