// assets/js/project-graph.js
// Chart.js rendering for PM and SM dashboards

// ────────────────────────────────────────────────────────────
// SHARED: Resource Utilization Bar Chart (allocated vs actual)
// ────────────────────────────────────────────────────────────
async function initProjectCharts() {
    try {
        const [resourceRes, trendRes] = await Promise.all([
            fetch('api/get-resource-utilization.php'),
            fetch('api/get-monthly-trend.php')
        ]);
        const resourceData = await resourceRes.json();
        const trendData    = await trendRes.json();

        renderResourceChart(resourceData);
        renderTrendChart(trendData);
    } catch(e) {
        console.warn('Chart load error:', e);
    }
}

function renderResourceChart(data) {
    const canvas = document.getElementById('chart-resource');
    if (!canvas || !data || !data.length) return;

    const labels    = data.map(d => d.name);
    const allocated = data.map(d => d.allocated_hrs);
    const actual    = data.map(d => d.actual_hrs);

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Allocated',
                    data: allocated,
                    backgroundColor: 'rgba(21, 101, 192, 0.3)',
                    borderColor: 'rgba(21, 101, 192, 0.8)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Actual',
                    data: actual,
                    backgroundColor: 'rgba(76, 175, 80, 0.5)',
                    borderColor: 'rgba(76, 175, 80, 0.9)',
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Hours' } }
            }
        }
    });
}

// ────────────────────────────────────────────────────────────
// Monthly Trend Line Chart (T&M Projects)
// ────────────────────────────────────────────────────────────
function renderTrendChart(data) {
    const canvas = document.getElementById('chart-trend');
    if (!canvas || !data || !data.datasets || !data.datasets.length) {
        if (canvas) {
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#aaa';
            ctx.font = '14px sans-serif';
            ctx.textAlign = 'center';
            ctx.fillText('No T&M projects available', canvas.width / 2, 80);
        }
        return;
    }

    const months   = data.months;
    const colors   = ['#1565c0','#e65100','#7b1fa2','#2e7d32','#c62828'];
    const datasets = data.datasets.map((ds, i) => ({
        label: ds.project_name,
        data: ds.monthly_actuals,
        borderColor: colors[i % colors.length],
        backgroundColor: 'transparent',
        tension: 0.3,
        pointRadius: 4,
        borderWidth: 2
    }));

    // Add budget reference line for first T&M project
    if (data.datasets[0]?.monthly_budget) {
        datasets.push({
            label: 'Budget (' + data.datasets[0].project_name + ')',
            data: Array(months.length).fill(data.datasets[0].monthly_budget),
            borderColor: '#f44336',
            borderDash: [6, 3],
            borderWidth: 1.5,
            pointRadius: 0,
            backgroundColor: 'transparent'
        });
    }

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: months,
            datasets
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Hours' } }
            }
        }
    });
}

// ────────────────────────────────────────────────────────────
// SM Dashboard: Budget vs Actual Bar + Performance Donut
// ────────────────────────────────────────────────────────────
function renderSMCharts(projects) {
    renderDonut(projects);
    renderBudgetBar(projects);
}

function renderDonut(projects) {
    const canvas = document.getElementById('chart-perf-donut');
    if (!canvas) return;

    let efficient = 0, effective = 0, poor = 0;
    projects.forEach(p => {
        const c = p.performance?.classification;
        if (c === 'Highly Efficient') efficient++;
        else if (c === 'Effective')   effective++;
        else if (c === 'Poorly Managed') poor++;
    });

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: ['Highly Efficient', 'Effective', 'Poorly Managed'],
            datasets: [{
                data: [efficient, effective, poor],
                backgroundColor: ['#4caf50', '#2196f3', '#f44336'],
                borderWidth: 2
            }]
        },
        options: {
            plugins: { legend: { position: 'bottom' } },
            cutout: '65%'
        }
    });
}

function renderBudgetBar(projects) {
    const canvas = document.getElementById('chart-budget-bar');
    if (!canvas || !projects.length) return;

    const labels  = projects.map(p => p.project_name.length > 14 ? p.project_name.substring(0,14)+'…' : p.project_name);
    const budget  = projects.map(p => p.performance?.budget_hours  || 0);
    const actual  = projects.map(p => p.performance?.actual_hours  || 0);

    new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                {
                    label: 'Budget',
                    data: budget,
                    backgroundColor: 'rgba(21,101,192,0.25)',
                    borderColor: 'rgba(21,101,192,0.8)',
                    borderWidth: 1,
                    borderRadius: 4
                },
                {
                    label: 'Actual',
                    data: actual,
                    backgroundColor: actual.map((a, i) => a > budget[i] ? 'rgba(244,67,54,0.6)' : 'rgba(76,175,80,0.6)'),
                    borderColor:     actual.map((a, i) => a > budget[i] ? '#f44336' : '#4caf50'),
                    borderWidth: 1,
                    borderRadius: 4
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true, title: { display: true, text: 'Hours' } }
            }
        }
    });
}
