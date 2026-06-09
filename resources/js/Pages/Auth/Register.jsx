import { Head, Link, useForm } from '@inertiajs/react';
import PublicLayout from '../../Layouts/PublicLayout';

const roleOptions = [
    {
        value: 'customer',
        title: 'Заказчик',
        description: 'Размещаю задания и покупаю услуги.',
    },
    {
        value: 'performer',
        title: 'Исполнитель',
        description: 'Публикую услуги и откликаюсь на задания.',
    },
];

export default function Register() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        role: 'customer',
        password: '',
        password_confirmation: '',
    });

    const submit = (event) => {
        event.preventDefault();
        post('/register');
    };

    return (
        <PublicLayout>
            <Head title="Регистрация" />

            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto grid max-w-7xl gap-10 px-4 py-16 sm:px-6 lg:grid-cols-[0.85fr_1.15fr] lg:px-8 lg:py-20">
                    <div className="flex flex-col justify-center">
                        <p className="text-sm font-semibold uppercase text-blue-700">Регистрация</p>
                        <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">
                            Создайте аккаунт для работы в Таскоре
                        </h1>
                        <p className="mt-5 max-w-2xl text-lg leading-8 text-slate-600">
                            На публичной регистрации доступны только роли заказчика и исполнителя. Модераторы и администраторы назначаются внутри платформы.
                        </p>
                    </div>

                    <form onSubmit={submit} className="rounded-lg border border-slate-200 bg-slate-50 p-6 shadow-sm">
                        <div>
                            <label className="text-sm font-semibold text-slate-900" htmlFor="name">
                                Имя
                            </label>
                            <input
                                id="name"
                                type="text"
                                value={data.name}
                                onChange={(event) => setData('name', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                autoComplete="name"
                                required
                            />
                            {errors.name && <p className="mt-2 text-sm text-red-600">{errors.name}</p>}
                        </div>

                        <div className="mt-5">
                            <label className="text-sm font-semibold text-slate-900" htmlFor="email">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                value={data.email}
                                onChange={(event) => setData('email', event.target.value)}
                                className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                autoComplete="email"
                                required
                            />
                            {errors.email && <p className="mt-2 text-sm text-red-600">{errors.email}</p>}
                        </div>

                        <fieldset className="mt-5">
                            <legend className="text-sm font-semibold text-slate-900">Роль</legend>
                            <div className="mt-2 grid gap-3 sm:grid-cols-2">
                                {roleOptions.map((option) => (
                                    <label
                                        key={option.value}
                                        className={`cursor-pointer rounded-md border p-4 ${
                                            data.role === option.value
                                                ? 'border-blue-600 bg-blue-50'
                                                : 'border-slate-200 bg-white hover:border-slate-300'
                                        }`}
                                    >
                                        <input
                                            type="radio"
                                            name="role"
                                            value={option.value}
                                            checked={data.role === option.value}
                                            onChange={(event) => setData('role', event.target.value)}
                                            className="sr-only"
                                        />
                                        <span className="block text-sm font-semibold text-slate-950">{option.title}</span>
                                        <span className="mt-1 block text-sm leading-6 text-slate-600">{option.description}</span>
                                    </label>
                                ))}
                            </div>
                            {errors.role && <p className="mt-2 text-sm text-red-600">{errors.role}</p>}
                        </fieldset>

                        <div className="mt-5 grid gap-4 sm:grid-cols-2">
                            <div>
                                <label className="text-sm font-semibold text-slate-900" htmlFor="password">
                                    Пароль
                                </label>
                                <input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(event) => setData('password', event.target.value)}
                                    className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    autoComplete="new-password"
                                    required
                                />
                                {errors.password && <p className="mt-2 text-sm text-red-600">{errors.password}</p>}
                            </div>
                            <div>
                                <label className="text-sm font-semibold text-slate-900" htmlFor="password_confirmation">
                                    Повтор пароля
                                </label>
                                <input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(event) => setData('password_confirmation', event.target.value)}
                                    className="mt-2 w-full rounded-md border border-slate-300 bg-white px-4 py-3 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-100"
                                    autoComplete="new-password"
                                    required
                                />
                            </div>
                        </div>

                        <button
                            type="submit"
                            disabled={processing}
                            className="mt-6 w-full rounded-md bg-blue-600 px-5 py-3 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            Создать аккаунт
                        </button>

                        <p className="mt-5 text-center text-sm text-slate-600">
                            Уже есть аккаунт?{' '}
                            <Link href="/login" className="font-semibold text-blue-700 hover:text-blue-800">
                                Войти
                            </Link>
                        </p>
                    </form>
                </div>
            </section>
        </PublicLayout>
    );
}
