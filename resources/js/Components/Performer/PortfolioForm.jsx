export default function PortfolioForm({ form, categories, onSubmit, submitLabel }) {
    return (
        <form onSubmit={onSubmit} className="rounded-lg border border-slate-200 bg-white p-6 shadow-sm">
            <div className="grid gap-5">
                <label className="block">
                    <span className="text-sm font-semibold text-slate-900">Название работы</span>
                    <input
                        value={form.data.title}
                        onChange={(event) => form.setData('title', event.target.value)}
                        className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    />
                    {form.errors.title && <p className="mt-2 text-sm text-red-600">{form.errors.title}</p>}
                </label>

                <label className="block">
                    <span className="text-sm font-semibold text-slate-900">Описание</span>
                    <textarea
                        value={form.data.description}
                        onChange={(event) => form.setData('description', event.target.value)}
                        rows={6}
                        className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm leading-6 outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                    />
                    {form.errors.description && <p className="mt-2 text-sm text-red-600">{form.errors.description}</p>}
                </label>

                <div className="grid gap-5 sm:grid-cols-2">
                    <label className="block">
                        <span className="text-sm font-semibold text-slate-900">Категория</span>
                        <select
                            value={form.data.category_id}
                            onChange={(event) => form.setData('category_id', event.target.value)}
                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="">Без категории</option>
                            {categories.map((category) => (
                                <option key={category.id} value={category.id}>{category.name}</option>
                            ))}
                        </select>
                        {form.errors.category_id && <p className="mt-2 text-sm text-red-600">{form.errors.category_id}</p>}
                    </label>

                    <label className="block">
                        <span className="text-sm font-semibold text-slate-900">Статус</span>
                        <select
                            value={form.data.status}
                            onChange={(event) => form.setData('status', event.target.value)}
                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        >
                            <option value="published">Опубликована</option>
                            <option value="draft">Черновик</option>
                        </select>
                        {form.errors.status && <p className="mt-2 text-sm text-red-600">{form.errors.status}</p>}
                    </label>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    <label className="block">
                        <span className="text-sm font-semibold text-slate-900">Внешняя ссылка</span>
                        <input
                            value={form.data.external_url}
                            onChange={(event) => form.setData('external_url', event.target.value)}
                            placeholder="https://example.com"
                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                        {form.errors.external_url && <p className="mt-2 text-sm text-red-600">{form.errors.external_url}</p>}
                    </label>

                    <label className="block">
                        <span className="text-sm font-semibold text-slate-900">Порядок сортировки</span>
                        <input
                            type="number"
                            min="0"
                            value={form.data.sort_order}
                            onChange={(event) => form.setData('sort_order', event.target.value)}
                            className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                        />
                        {form.errors.sort_order && <p className="mt-2 text-sm text-red-600">{form.errors.sort_order}</p>}
                    </label>
                </div>

                <div className="grid gap-5 sm:grid-cols-2">
                    <label className="block">
                        <span className="text-sm font-semibold text-slate-900">Изображение</span>
                        <input
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp"
                            onChange={(event) => form.setData('image', event.target.files[0])}
                            className="mt-2 block w-full text-sm text-slate-600"
                        />
                        {form.errors.image && <p className="mt-2 text-sm text-red-600">{form.errors.image}</p>}
                    </label>

                    <label className="block">
                        <span className="text-sm font-semibold text-slate-900">Файл</span>
                        <input
                            type="file"
                            accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt"
                            onChange={(event) => form.setData('file', event.target.files[0])}
                            className="mt-2 block w-full text-sm text-slate-600"
                        />
                        {form.errors.file && <p className="mt-2 text-sm text-red-600">{form.errors.file}</p>}
                    </label>
                </div>
            </div>

            <button
                type="submit"
                disabled={form.processing}
                className="mt-6 rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                {submitLabel}
            </button>
        </form>
    );
}
