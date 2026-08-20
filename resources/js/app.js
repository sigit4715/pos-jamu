import './bootstrap';

document.addEventListener('DOMContentLoaded', () => {
    const shell = document.querySelector('#app-shell');
    const toggle = document.querySelector('[data-mobile-menu]');

    if (!shell || !toggle) return;

    toggle.addEventListener('click', () => {
        const isOpen = shell.classList.toggle('nav-open');
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute('aria-label', isOpen ? 'Tutup menu' : 'Buka menu');
    });
});
