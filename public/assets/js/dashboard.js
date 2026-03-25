document.addEventListener('DOMContentLoaded', function () {
    const filters = document.querySelectorAll('.form-select');
    const tableBody = document.querySelector('.vcm-table tbody');

    // Chart instances (Activity)
    let chartEstado = null;
    let chartAnio = null;
    let chartTipo = null;

    // Chart instances (Convenio)
    let chartConvTipo = null;
    let chartConvEstado = null;
    let chartConvAnio = null;

    // Initial Load
    fetchData();

    // Event Listeners
    filters.forEach(select => {
        select.addEventListener('change', fetchData);
    });

    document.getElementById('btn-clean')?.addEventListener('click', (e) => {
        e.preventDefault();
        filters.forEach(s => s.value = '');
        fetchData();
    });

    function fetchData() {
        const params = new URLSearchParams();
        filters.forEach(s => {
            if (s.value) params.append(s.name, s.value);
        });

        // Show loading state (optional)
        if (tableBody) tableBody.style.opacity = '0.5';

        fetch('api/dashboard_data.php?' + params.toString())
            .then(response => response.json())
            .then(data => {
                // Wrap in try-catch for robustness
                try { if (data.cards) updateCards(data.cards); } catch (e) { console.error('Error updating cards:', e); }
                try { if (data.charts) updateCharts(data.charts); } catch (e) { console.error('Error updating charts:', e); }
                try {
                    if (data.convenios_charts) updateConvenioCharts(data.convenios_charts);
                } catch (e) { console.error('Error updating convenio charts:', e); }
                try { if (data.table_html) updateTable(data.table_html); } catch (e) { console.error('Error updating table:', e); }

                if (tableBody) tableBody.style.opacity = '1';
            })
            .catch(error => console.error('Error:', error));
    }

    function updateCards(cards) {
        animateValue('card-total', cards.total);
        animateValue('card-extension', cards.extension);
        animateValue('card-academica', cards.academica);
        animateValue('card-difusion', cards.difusion);
    }

    function animateValue(id, end) {
        const obj = document.getElementById(id);
        if (!obj) return;
        const start = parseInt(obj.innerText.replace(/\D/g, '')) || 0;
        if (start === end) return;

        let current = start;
        const range = end - start;
        const increment = end > start ? 1 : -1;
        const stepTime = Math.abs(Math.floor(1000 / range));

        if (Math.abs(range) > 50) {
            obj.innerText = end;
            return;
        }

        const timer = setInterval(function () {
            current += increment;
            obj.innerText = current;
            if (current == end) {
                clearInterval(timer);
            }
        }, Math.max(stepTime, 20));
    }

    function updateTable(html) {
        if (tableBody) tableBody.innerHTML = html;
    }

    function updateCharts(data) {
        const labels = (arr, k) => arr.map(x => x[k]);
        const values = (arr) => arr.map(x => parseInt(x.c));

        // 1. Estado Chart
        const ctxEstado = document.getElementById('chartEstado');
        if (ctxEstado) {
            if (chartEstado) {
                chartEstado.data.labels = labels(data.estado, 'estado');
                chartEstado.data.datasets[0].data = values(data.estado);
                chartEstado.update();
            } else {
                chartEstado = new Chart(ctxEstado, {
                    type: 'bar',
                    data: {
                        labels: labels(data.estado, 'estado'),
                        datasets: [{ label: 'Actividades', data: values(data.estado), backgroundColor: '#00509e', borderRadius: 4 }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, title: { display: true, text: 'Por Estado' } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }
        }

        // 2. Anio Chart
        const ctxAnio = document.getElementById('chartAnio');
        if (ctxAnio) {
            if (chartAnio) {
                chartAnio.data.labels = labels(data.anio, 'anio');
                chartAnio.data.datasets[0].data = values(data.anio);
                chartAnio.update();
            } else {
                chartAnio = new Chart(ctxAnio, {
                    type: 'line',
                    data: {
                        labels: labels(data.anio, 'anio'),
                        datasets: [{
                            label: 'Actividades',
                            data: values(data.anio),
                            borderColor: '#d32f2f',
                            backgroundColor: 'rgba(211, 47, 47, 0.1)',
                            fill: true,
                            tension: 0.3
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, title: { display: true, text: 'Evolución Anual' } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }
        }

        // 3. Tipo Chart
        const ctxTipo = document.getElementById('chartTipo');
        if (ctxTipo) {
            if (chartTipo) {
                chartTipo.data.labels = labels(data.tipo, 'tipo_vinculacion');
                chartTipo.data.datasets[0].data = values(data.tipo);
                chartTipo.update();
            } else {
                chartTipo = new Chart(ctxTipo, {
                    type: 'doughnut',
                    data: {
                        labels: labels(data.tipo, 'tipo_vinculacion'),
                        datasets: [{
                            data: values(data.tipo),
                            backgroundColor: ['#00509e', '#d32f2f', '#f09819', '#10b981', '#6b7280'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'right', labels: { boxWidth: 12, font: { size: 10 } } },
                            title: { display: true, text: 'Distribución por Tipo' }
                        },
                        cutout: '60%'
                    }
                });
            }
        }
    }

    function updateConvenioCharts(data) {
        const labels = (arr, k) => arr.map(x => x[k]);
        const values = (arr) => arr.map(x => parseInt(x.c));

        // 1. Tipo Convenio
        const ctxCT = document.getElementById('chartConvTipo');
        if (ctxCT) {
            if (chartConvTipo) {
                chartConvTipo.data.labels = labels(data.tipo, 'tipo');
                chartConvTipo.data.datasets[0].data = values(data.tipo);
                chartConvTipo.update();
            } else {
                chartConvTipo = new Chart(ctxCT, {
                    type: 'doughnut',
                    data: {
                        labels: labels(data.tipo, 'tipo'),
                        datasets: [{
                            data: values(data.tipo),
                            backgroundColor: ['#4f46e5', '#ec4899', '#8b5cf6', '#06b6d4', '#f59e0b'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'right', labels: { boxWidth: 10 } }, title: { display: true, text: 'Tipos de Convenio' } },
                        cutout: '60%'
                    }
                });
            }
        }

        // 2. Estado Convenio
        const ctxCE = document.getElementById('chartConvEstado');
        if (ctxCE) {
            if (chartConvEstado) {
                chartConvEstado.data.labels = labels(data.estado, 'estado_calc');
                chartConvEstado.data.datasets[0].data = values(data.estado);
                chartConvEstado.update();
            } else {
                chartConvEstado = new Chart(ctxCE, {
                    type: 'pie',
                    data: {
                        labels: labels(data.estado, 'estado_calc'),
                        datasets: [{
                            data: values(data.estado),
                            backgroundColor: ['#10b981', '#ef4444', '#6b7280'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'right' }, title: { display: true, text: 'Estado (Vigencia)' } }
                    }
                });
            }
        }

        // 3. Evolución Anual Convenios
        const ctxCA = document.getElementById('chartConvAnio');
        if (ctxCA) {
            if (chartConvAnio) {
                chartConvAnio.data.labels = labels(data.anio, 'anio');
                chartConvAnio.data.datasets[0].data = values(data.anio);
                chartConvAnio.update();
            } else {
                chartConvAnio = new Chart(ctxCA, {
                    type: 'bar',
                    data: {
                        labels: labels(data.anio, 'anio'),
                        datasets: [{
                            label: 'Convenios',
                            data: values(data.anio),
                            backgroundColor: '#8b5cf6',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false }, title: { display: true, text: 'Nuevos Convenios por Año' } },
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
                    }
                });
            }
        }
    }
});
