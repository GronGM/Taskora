<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="color-scheme" content="light dark">
        <link rel="icon" href="{{ asset('favicon.ico') }}?v=2" sizes="any">
        <link rel="icon" type="image/png" href="{{ asset('favicon-32x32.png') }}?v=2" sizes="32x32">
        <link rel="icon" type="image/png" href="{{ asset('favicon-192x192.png') }}?v=2" sizes="192x192">
        <link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}?v=2">
        @if (\App\Support\BetaAccess::shouldNoIndex())
            <meta name="robots" content="noindex,nofollow">
        @endif

        <script>
            (() => {
                const storageKey = 'taskora_theme';
                const root = document.documentElement;
                let preference = 'system';

                try {
                    const stored = window.localStorage.getItem(storageKey);
                    preference = ['light', 'dark', 'system'].includes(stored) ? stored : 'system';
                } catch {
                    preference = 'system';
                }

                const systemDark = window.matchMedia?.('(prefers-color-scheme: dark)').matches ?? false;
                const resolvedTheme = preference === 'system' ? (systemDark ? 'dark' : 'light') : preference;

                root.classList.toggle('dark', resolvedTheme === 'dark');
                root.dataset.themePreference = preference;
                root.dataset.theme = resolvedTheme;
            })();
        </script>
        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @inertiaHead
    </head>
    <body class="font-sans">
        @inertia
    </body>
</html>
