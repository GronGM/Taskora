import { createContext, useContext, useEffect, useMemo, useState } from 'react';

export const THEME_STORAGE_KEY = 'taskora_theme';

export const themeLabels = {
    light: 'Светлая',
    dark: 'Темная',
    system: 'Как в системе',
};

export const themeOptions = Object.entries(themeLabels).map(([value, label]) => ({ value, label }));

const ThemeContext = createContext(null);

function normalizeThemePreference(preference) {
    return Object.prototype.hasOwnProperty.call(themeLabels, preference) ? preference : 'system';
}

function getStoredThemePreference() {
    if (typeof window === 'undefined') {
        return 'system';
    }

    try {
        return normalizeThemePreference(window.localStorage.getItem(THEME_STORAGE_KEY));
    } catch {
        return 'system';
    }
}

function getSystemTheme() {
    if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
        return 'light';
    }

    return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
}

function applyTheme(preference, systemTheme) {
    if (typeof document === 'undefined') {
        return;
    }

    const resolvedTheme = preference === 'system' ? systemTheme : preference;
    const root = document.documentElement;

    root.classList.toggle('dark', resolvedTheme === 'dark');
    root.dataset.themePreference = preference;
    root.dataset.theme = resolvedTheme;
}

export function ThemeProvider({ children }) {
    const [preference, setPreference] = useState(getStoredThemePreference);
    const [systemTheme, setSystemTheme] = useState(getSystemTheme);
    const resolvedTheme = preference === 'system' ? systemTheme : preference;

    useEffect(() => {
        if (typeof window === 'undefined' || typeof window.matchMedia !== 'function') {
            return undefined;
        }

        const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
        const handleChange = (event) => setSystemTheme(event.matches ? 'dark' : 'light');

        handleChange(mediaQuery);
        mediaQuery.addEventListener?.('change', handleChange);

        return () => mediaQuery.removeEventListener?.('change', handleChange);
    }, []);

    useEffect(() => {
        applyTheme(preference, systemTheme);
    }, [preference, systemTheme]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return;
        }

        try {
            window.localStorage.setItem(THEME_STORAGE_KEY, preference);
        } catch {
            // Theme switching must keep working even in restricted browser storage modes.
        }
    }, [preference]);

    useEffect(() => {
        if (typeof window === 'undefined') {
            return undefined;
        }

        const handleStorage = (event) => {
            if (event.key === THEME_STORAGE_KEY) {
                setPreference(normalizeThemePreference(event.newValue));
            }
        };

        window.addEventListener('storage', handleStorage);

        return () => window.removeEventListener('storage', handleStorage);
    }, []);

    const value = useMemo(
        () => ({
            preference,
            currentPreference: preference,
            resolvedTheme,
            labels: themeLabels,
            options: themeOptions,
            setTheme: (nextPreference) => setPreference(normalizeThemePreference(nextPreference)),
        }),
        [preference, resolvedTheme],
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

export function useTheme() {
    const context = useContext(ThemeContext);

    if (!context) {
        throw new Error('useTheme must be used inside ThemeProvider.');
    }

    return context;
}
