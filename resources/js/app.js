import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('#app-shell');
    const toggle = document.querySelector('[data-mobile-menu]');
    const closeButton = document.querySelector('[data-mobile-menu-close]');

    if (!shell || !toggle) return;

    const setMenu = (isOpen) => {
        shell.classList.toggle('nav-open', isOpen);
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
    };

    toggle.addEventListener('click', () => {
        setMenu(!shell.classList.contains('nav-open'));
    });

    closeButton?.addEventListener('click', () => setMenu(false));
    document.querySelectorAll('.app-sidebar .nav-link').forEach((link) => link.addEventListener('click', () => setMenu(false)));
});
