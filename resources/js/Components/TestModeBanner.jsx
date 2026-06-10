import { Link, usePage } from '@inertiajs/react';

export default function TestModeBanner() {
    const { testMode = {} } = usePage().props;

    if (!testMode.enabled) {
        return null;
    }

    return (
        <div className="border-b border-amber-200 bg-amber-50 px-4 py-2 text-center text-sm font-semibold leading-6 text-amber-900">
            <p>
                {testMode.message}{' '}
                <Link href="/beta-feedback/create" className="underline decoration-amber-500 underline-offset-4 hover:text-amber-700">
                    Сообщить о проблеме
                </Link>
            </p>
            {testMode.debug_warning && <p className="mt-1 text-xs font-medium text-amber-800">{testMode.debug_warning}</p>}
        </div>
    );
}
