import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../Layouts/PublicLayout';

export default function Placeholder({ title, description }) {
    return (
        <PublicLayout>
            <Head title={title} />
            <section className="border-b border-slate-200 bg-white">
                <div className="mx-auto max-w-4xl px-4 py-16 sm:px-6 lg:px-8">
                    <p className="text-sm font-semibold uppercase text-blue-700">Раздел MVP</p>
                    <h1 className="mt-3 text-4xl font-semibold tracking-normal text-slate-950">{title}</h1>
                    <p className="mt-5 text-lg leading-8 text-slate-600">{description}</p>
                    <Link
                        href="/"
                        className="mt-8 inline-flex rounded-md border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-100"
                    >
                        Вернуться на главную
                    </Link>
                </div>
            </section>
        </PublicLayout>
    );
}
