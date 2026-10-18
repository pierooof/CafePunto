// Tema claro/oscuro. Se carga en <head> (sin defer) para fijar data-theme antes de pintar
// y evitar el parpadeo. Sin elección guardada, sigue el tema del sistema.
(() => {
    const KEY = 'cafepunto-theme';
    const root = document.documentElement;
    const systemDark = window.matchMedia('(prefers-color-scheme: dark)');

    const saved = () => {
        try { return localStorage.getItem(KEY); } catch { return null; }
    };

    const apply = (theme) => {
        root.dataset.theme = theme;
        document.querySelector('meta[name="theme-color"]')
            ?.setAttribute('content', theme === 'dark' ? '#100905' : '#3b2314');
        document.querySelectorAll('[data-theme-toggle]').forEach((btn) => {
            btn.setAttribute('aria-label', theme === 'dark' ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro');
        });
    };

    apply(saved() || (systemDark.matches ? 'dark' : 'light'));
    document.addEventListener('DOMContentLoaded', () => apply(root.dataset.theme));

    document.addEventListener('click', (e) => {
        if (!e.target.closest('[data-theme-toggle]')) return;
        const next = root.dataset.theme === 'dark' ? 'light' : 'dark';
        try { localStorage.setItem(KEY, next); } catch { /* modo privado: solo dura la página */ }
        apply(next);
    });

    systemDark.addEventListener('change', (e) => {
        if (!saved()) apply(e.matches ? 'dark' : 'light');
    });
})();
