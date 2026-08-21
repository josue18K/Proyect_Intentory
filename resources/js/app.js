import './bootstrap';
import 'bootstrap/dist/js/bootstrap.bundle.min.js';
import Chart from 'chart.js/auto';

const chartElement = document.querySelector('#movementChart');

if (chartElement) {
    new Chart(chartElement, {
        type: 'line',
        data: {
            labels: ['Lun', 'Mar', 'Mie', 'Jue', 'Vie', 'Sab', 'Dom'],
            datasets: [
                { label: 'Entradas', data: [12, 19, 8, 15, 12, 18, 22], borderColor: '#f5c400', backgroundColor: 'rgba(245,196,0,.12)', fill: true, tension: .35 },
                { label: 'Salidas', data: [8, 12, 14, 10, 17, 13, 18], borderColor: '#111111', backgroundColor: 'rgba(17,17,17,.05)', fill: true, tension: .35 },
            ],
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } },
    });
}

document.querySelectorAll('form[data-loading]').forEach((form) => form.addEventListener('submit', () => {
    const button = form.querySelector('[data-loading-button]');
    if (button) { button.disabled = true; button.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Procesando...'; }
}));

document.querySelector('[data-whatsapp]')?.addEventListener('click', () => {
    const text = `Reporte de inventario LIUVA - ${document.title}`;
    window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank', 'noopener');
});
