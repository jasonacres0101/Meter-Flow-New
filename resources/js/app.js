import './bootstrap';
import ApexCharts from 'apexcharts';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

window.ApexCharts = ApexCharts;
window.L = L;

const scrollStoragePrefix = 'copier-monitor:scroll:';

const scrollKey = (url = window.location.href) => {
    const parsedUrl = new URL(url, window.location.origin);

    return `${scrollStoragePrefix}${parsedUrl.pathname}${parsedUrl.search}`;
};

document.addEventListener('DOMContentLoaded', () => {
    const savedPosition = sessionStorage.getItem(scrollKey());

    if (savedPosition !== null) {
        sessionStorage.removeItem(scrollKey());
        requestAnimationFrame(() => window.scrollTo({ top: Number(savedPosition) || 0, left: 0 }));
    }
});

document.addEventListener('submit', (event) => {
    const form = event.target;

    if (!(form instanceof HTMLFormElement) || form.dataset.preserveScroll === 'false') {
        return;
    }

    const method = (form.getAttribute('method') || 'get').toLowerCase();
    const target = form.getAttribute('target');

    if (method === 'get' || target === '_blank') {
        return;
    }

    sessionStorage.setItem(scrollKey(), String(window.scrollY));
});
