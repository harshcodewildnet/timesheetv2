let hoursChart = null;
let leavesChart = null;
let workdaysChart = null;
let projectUtilizationChart = null;

const leavesChartEl = document.getElementById('leavesChart');
const donutCtxLeaves = leavesChartEl ? leavesChartEl.getContext('2d') : null;
const workdaysChartEl = document.getElementById('workdaysChart');
const donutCtxWorkdays = workdaysChartEl ? workdaysChartEl.getContext('2d') : null;

function loadGraphs(empIds) {

    fetch('api/get-employee-hours.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ emp_ids: empIds })  // expecting array of emp_ids
    })
        .then(response => response.json())
        .then(data => {
            const weekDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const labels = weekDays;
            const hoursData = weekDays.map(day => parseFloat(data[day] ?? 0)); // from avg map returned

            if (hoursChart) {
                hoursChart.destroy();
            }

            hoursChart = new Chart(document.getElementById('hoursChart'), {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Avg Hours Worked',
                        data: hoursData,
                        backgroundColor: 'rgba(54, 162, 235, 0.4)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            min: 0,
                            suggestedMax: 9,
                            ticks: {
                                stepSize: 0.5,
                                precision: 1
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return `${context.dataset.label}: ${context.parsed.y.toFixed(1)} hrs`;
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(error => console.error('Error loading hours chart:', error));


    fetch('api/get-employee-leaves.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ emp_ids: empIds })
    })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                console.error('Leaves API error:', data.error);
                return;
            }

            const taken = data.data.total_leaves_taken || 0;
            const total = data.data.total_possible_leaves || 1;
            const remaining = total - taken;

            if (leavesChart) {
                leavesChart.destroy();
            }

            leavesChart = new Chart(donutCtxLeaves, {
                type: 'doughnut',
                data: {
                    labels: [
                        `Leaves Taken (${taken})`,
                        // `Remaining Leaves (${remaining})`
                    ],
                    datasets: [{
                        data: [taken, remaining],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(100, 255, 100, 0.5)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(100, 255, 100, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return `${context.label}: ${context.parsed} day${context.parsed !== 1 ? 's' : ''}`;
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(err => console.error('Error loading leaves data:', err));

    const idParam = empIds.join(',');

    fetch(`api/get-employee-workdays.php?emp_ids=${idParam}`)
        .then(res => res.json())
        .then(data => {
            const worked = data.days_worked;
            const countEmployees = data.total_employees;
            const totalWorkingDays = daysInThisMonth() * countEmployees;
            const remaining = totalWorkingDays - worked;

            if (workdaysChart) {
                workdaysChart.destroy();
            }

            workdaysChart = new Chart(donutCtxWorkdays, {
                type: 'doughnut',
                data: {
                    labels: [
                        `Total Days Worked (${worked} / ${totalWorkingDays})`,
                        `Remaining Days (${remaining})`
                    ],
                    datasets: [{
                        data: [worked, remaining],
                        backgroundColor: [
                            'rgba(243, 135, 255, 0.8)',
                            'rgba(200, 200, 200, 0.5)'
                        ],
                        borderColor: [
                            'rgba(243, 135, 255, 1)',
                            'rgba(200, 200, 200, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return `${context.label}: ${context.parsed} day${context.parsed !== 1 ? 's' : ''}`;
                                }
                            }
                        }
                    }
                }
            });
        })
        .catch(err => console.error('Error loading working days data:', err));
}

function daysInThisMonth() {
    var now = new Date();
    return new Date(now.getFullYear(), now.getMonth() + 1, 0).getDate();
}

// ================================================================
// Utilization Sub-tab switching
// ================================================================
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.util-sub-tab-link').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.util-sub-tab-link').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.util-sub-tab-panel').forEach(p => p.classList.remove('active'));
            this.classList.add('active');
            const target = this.getAttribute('data-util-tab');
            const panel = document.getElementById('util-' + target);
            if (panel) panel.classList.add('active');
        });
    });
});

// ================================================================
// loadUtilizationReports – fetches all three data sets
// ================================================================
function loadUtilizationReports(filters) {
    const startDate = (filters.timeline === 'custom' ? filters.customFrom : null)
        || getStartDateFromTimeline(filters.timeline);
    const endDate = (filters.timeline === 'custom' ? filters.customTo : null)
        || new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD

    const payload = {
        employees: filters.selectedEmployees,
        departments: filters.selectedDepartments,
        repManagers: filters.selectedManagers,
        allowedEmployees: [],
        startDate: startDate,
        endDate: endDate
    };

    // Update date labels in both sub-panels
    const fmt = d => d ? new Date(d + 'T00:00:00').toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' }) : '';
    const rangeLabel = `${fmt(startDate)} – ${fmt(endDate)}`;
    ['dailyHoursDateLabel', 'teamUtilizationDateLabel'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.textContent = rangeLabel;
    });

    // 1. Daily Hours
    fetch('api/get-daily-hours.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') renderDailyHoursTable(data.data, startDate, endDate);
    })
    .catch(err => console.error('Error loading daily hours:', err));

    // 2. Project Utilization
    fetch('api/get-project-utilization.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') renderProjectUtilizationChart(data.data);
    })
    .catch(err => console.error('Error loading project utilization:', err));

    // 3. Resource Matrix
    fetch('api/get-resource-project-matrix.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(data => {
        if (data.status === 'success') renderResourceProjectMatrix(data.data, startDate, endDate);
    })
    .catch(err => console.error('Error loading resource matrix:', err));
}

function getStartDateFromTimeline(timeline) {
    const now = new Date();
    if (timeline === 'last7') {
        now.setDate(now.getDate() - 7);
    } else if (timeline === 'last30') {
        now.setDate(now.getDate() - 30);
    } else if (timeline === 'thisweek') {
        const day = now.getDay(); // 0=Sun
        now.setDate(now.getDate() - (day === 0 ? 6 : day - 1));
    } else if (timeline === 'thismonth') {
        now.setDate(1);
    } else {
        now.setDate(now.getDate() - 7); // Default 7 days
    }
    return now.toLocaleDateString('en-CA'); // YYYY-MM-DD
}

// ================================================================
// Render Daily Hours Table
// ================================================================
function renderDailyHoursTable(data, startDate, endDate) {
    const headerRow = document.getElementById('dailyHoursHeaderRow');
    const tbody = document.getElementById('dailyHoursBody');
    if (!headerRow || !tbody) return;

    // Build date range array
    const dates = [];
    const cur = new Date(startDate + 'T00:00:00');
    const end = new Date(endDate + 'T00:00:00');
    while (cur <= end) {
        dates.push(cur.toLocaleDateString('en-CA'));
        cur.setDate(cur.getDate() + 1);
    }

    // Group data: { emp_name: { date: hours } }
    const employees = [];
    const lookup = {};
    data.forEach(row => {
        if (!lookup[row.emp_name]) {
            lookup[row.emp_name] = {};
            employees.push(row.emp_name);
        }
        lookup[row.emp_name][row.date] = parseFloat(row.total_hours);
    });
    employees.sort();

    // Build header
    let headerHtml = '<th>Employee</th>';
    dates.forEach(d => {
        const dt = new Date(d + 'T00:00:00');
        const day = dt.getDay();
        const isWeekend = day === 0 || day === 6;
        const label = dt.toLocaleDateString('en-IN', { day: '2-digit', month: 'short' });
        const dayLabel = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'][day];
        headerHtml += `<th class="${isWeekend ? 'daily-weekend' : ''}" title="${d}">${label}<br><small>${dayLabel}</small></th>`;
    });
    headerHtml += '<th class="daily-total">Total</th>';
    headerRow.innerHTML = headerHtml;

     // Build rows
    const rangeCapacityHrs = (dates.length / 7) * 40;
    let bodyHtml = '';
    if (employees.length === 0) {
        bodyHtml = `<tr><td colspan="${dates.length + 2}" style="text-align:center;color:#999;padding:20px;">No data for selected range/filters.</td></tr>`;
    } else {
        employees.forEach(emp => {
            let total = 0;
            let rowHtml = `<tr><td>${emp}</td>`;
            dates.forEach(d => {
                const dt = new Date(d + 'T00:00:00');
                const isWeekend = dt.getDay() === 0 || dt.getDay() === 6;
                const hrs = lookup[emp][d] || 0;
                total += hrs;
                const pct = (hrs / 8) * 100;
                const colorClass = (hrs > 0 || !isWeekend) ? getUtilizationColor(pct) : '';
                
                if (isWeekend) {
                    rowHtml += `<td class="daily-weekend ${colorClass}">${hrs > 0 ? hrs.toFixed(1) : '<span class="daily-zero">–</span>'}</td>`;
                } else {
                    rowHtml += `<td class="${colorClass}">${hrs > 0 ? hrs.toFixed(1) : '<span class="daily-zero">–</span>'}</td>`;
                }
            });
            const empPct = rangeCapacityHrs > 0 ? (total / rangeCapacityHrs) * 100 : 0;
            const totalColor = getUtilizationColor(empPct);
            rowHtml += `<td class="daily-total ${totalColor}">${total.toFixed(1)}</td></tr>`;
            bodyHtml += rowHtml;
        });

        // Totals row
        let colTotals = dates.map(d => {
            return employees.reduce((sum, emp) => sum + (lookup[emp][d] || 0), 0);
        });
        let grandTotal = colTotals.reduce((a, b) => a + b, 0);
        let footerHtml = '<tr class="matrix-totals-row"><td><strong>Daily Total</strong></td>';
        colTotals.forEach(t => {
            footerHtml += `<td>${t > 0 ? t.toFixed(1) : '<span class="daily-zero">–</span>'}</td>`;
        });
        const teamCapacity = rangeCapacityHrs * employees.length;
        const teamPct = teamCapacity > 0 ? (grandTotal / teamCapacity) * 100 : 0;
        const teamColor = getUtilizationColor(teamPct);
        footerHtml += `<td class="daily-total ${teamColor}">${grandTotal.toFixed(1)}</td></tr>`;
        bodyHtml += footerHtml;
    }

    tbody.innerHTML = bodyHtml;
}

// ================================================================
// Project Utilization Chart + Table
// ================================================================
function renderProjectUtilizationChart(data) {
    // Chart
    const canvas = document.getElementById('projectUtilizationChart');
    if (canvas) {
        const ctx = canvas.getContext('2d');
        // Sort: others at the end, rest by hours DESC
        data.sort((a, b) => {
            if (a.client_name === 'Others') return 1;
            if (b.client_name === 'Others') return -1;
            return parseFloat(b.total_hours) - parseFloat(a.total_hours);
        });

        const labels = data.map(item => item.client_name);
        const hours = data.map(item => parseFloat(item.total_hours));
        const grandTotal = hours.reduce((a, b) => a + b, 0);

        const barColors = [
            '#fdad17','#36a2eb','#ff6384','#4bc0c0','#9966ff',
            '#ff9f40','#c9cbcf','#66bb6a','#ef5350','#42a5f5'
        ];

        if (projectUtilizationChart) projectUtilizationChart.destroy();

        // Dynamic height: 44px per bar + 60px for axes
        const chartHeight = Math.max(220, labels.length * 44 + 60);
        canvas.parentElement.style.height = chartHeight + 'px';

        // Estimate longest label width to set left padding
        const maxLabelLen = Math.max(...labels.map(l => l.length));
        const leftPad = Math.min(Math.max(maxLabelLen * 6, 100), 260);

        projectUtilizationChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Total Hours',
                    data: hours,
                    backgroundColor: labels.map((_, i) => barColors[i % barColors.length] + 'dd'),
                    borderColor: labels.map((_, i) => barColors[i % barColors.length]),
                    borderWidth: 1,
                    borderRadius: 5,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: { left: 0, right: 20, top: 6, bottom: 6 }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        title: { display: true, text: 'Hours', font: { size: 11, weight: '500' } },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: { font: { size: 11 } }
                    },
                    y: {
                        grid: { display: false },
                        ticks: {
                            font: { size: 11 },
                            // Let Chart.js handle wrapping naturally; don't truncate
                            callback: function(val) { return this.getLabelForValue(val); }
                        },
                        afterFit(scale) {
                            // Ensure enough room for the longest label
                            scale.width = leftPad;
                        }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => ` ${ctx.parsed.x.toFixed(1)} hrs  (${grandTotal > 0 ? ((ctx.parsed.x / grandTotal) * 100).toFixed(1) : 0}%)`
                        }
                    }
                }
            }
        });

        // Force Chart.js to recalculate size against the full container width
        requestAnimationFrame(() => { projectUtilizationChart.resize(); });


        const tbody = document.getElementById('projectUtilizationTableBody');
        if (tbody) {
            let html = '';
            data.forEach(item => {
                const hrs = parseFloat(item.total_hours);
                const pct = grandTotal > 0 ? ((hrs / grandTotal) * 100).toFixed(1) : '0.0';
                html += `<tr>
                    <td>${item.client_name}</td>
                    <td style="text-align:center;">${hrs.toFixed(1)}</td>
                    <td style="text-align:center;">${pct}%</td>
                </tr>`;
            });
            const totalRow = `<tr class="matrix-totals-row">
                <td><strong>Grand Total</strong></td>
                <td style="text-align:center;"><strong>${grandTotal.toFixed(1)}</strong></td>
                <td style="text-align:center;"><strong>100%</strong></td>
            </tr>`;
            tbody.innerHTML = html + totalRow;
        }
    }
}

// ================================================================
// Utilization color helper
// ================================================================
function getUtilizationColor(pct) {
    if (pct < 70)  return 'util-red';
    if (pct < 85)  return 'util-yellow';
    if (pct <= 110) return 'util-green';
    return 'util-blue';
}

// ================================================================
// Resource Matrix (Team Member Utilization)
// ================================================================
function renderResourceProjectMatrix(data, startDate, endDate) {
    const headerRow = document.getElementById('matrixHeaderRow');
    const tbody = document.getElementById('matrixTableBody');
    if (!headerRow || !tbody) return;

    // Calculate capacity based on weeks between startDate and endDate
    const start = new Date(startDate + 'T00:00:00');
    const end = new Date(endDate + 'T00:00:00');
    const diffDays = Math.round((end - start) / (1000 * 60 * 60 * 24)) + 1;
    const weeks = diffDays / 7;
    const capacityHrs = Math.round(weeks * 40 * 10) / 10; // 40 hrs/week

    // Extract unique projects & employees
    const projects = [...new Set(data.map(item => item.client_name))].sort((a, b) => {
        if (a === 'Others') return 1;
        if (b === 'Others') return -1;
        return a.localeCompare(b);
    });
    const employees = [...new Set(data.map(item => item.emp_name))].sort();

    // Lookup { emp_name: { client_name: hours } }
    const lookup = {};
    data.forEach(item => {
        if (!lookup[item.emp_name]) lookup[item.emp_name] = {};
        lookup[item.emp_name][item.client_name] = parseFloat(item.total_hours);
    });

    // Header
    let headerHtml = '<th>Employee</th>';
    projects.forEach(p => { headerHtml += `<th>${p}</th>`; });
    headerHtml += `<th>Total Hrs</th><th>Capacity Hrs</th><th>Utilization %</th>`;
    headerRow.innerHTML = headerHtml;

    // Rows
    let bodyHtml = '';
    const projectTotals = {};
    projects.forEach(p => { projectTotals[p] = 0; });
    let grandEmpTotal = 0;

    if (employees.length === 0) {
        bodyHtml = `<tr><td colspan="${projects.length + 4}" style="text-align:center;color:#999;padding:20px;">No data available for selected range/filters.</td></tr>`;
    } else {
        employees.forEach(emp => {
            let empTotal = 0;
            let rowHtml = `<tr><td>${emp}</td>`;
            projects.forEach(p => {
                const hrs = lookup[emp][p] || 0;
                empTotal += hrs;
                projectTotals[p] += hrs;
                rowHtml += `<td style="text-align:center;">${hrs > 0 ? hrs.toFixed(1) : '<span style="color:#ccc">–</span>'}</td>`;
            });
            grandEmpTotal += empTotal;
            const pct = capacityHrs > 0 ? (empTotal / capacityHrs) * 100 : 0;
            const colorClass = getUtilizationColor(pct);
            rowHtml += `<td style="text-align:center;font-weight:600;">${empTotal.toFixed(1)}</td>`;
            rowHtml += `<td style="text-align:center;color:#888;">${capacityHrs.toFixed(1)}</td>`;
            rowHtml += `<td class="${colorClass}" style="text-align:center;">
                <span class="util-pct-badge ${colorClass}">${pct.toFixed(0)}%</span>
            </td>`;
            rowHtml += '</tr>';
            bodyHtml += rowHtml;
        });

        // Totals footer row
        let footerHtml = '<tr class="matrix-totals-row"><td><strong>Project Total</strong></td>';
        projects.forEach(p => {
            footerHtml += `<td style="text-align:center;"><strong>${projectTotals[p].toFixed(1)}</strong></td>`;
        });
        const teamCapacity = capacityHrs * employees.length;
        const teamPct = teamCapacity > 0 ? (grandEmpTotal / teamCapacity) * 100 : 0;
        const teamColor = getUtilizationColor(teamPct);
        footerHtml += `<td style="text-align:center;"><strong>${grandEmpTotal.toFixed(1)}</strong></td>`;
        footerHtml += `<td style="text-align:center;color:#888;">${teamCapacity.toFixed(1)}</td>`;
        footerHtml += `<td class="${teamColor}" style="text-align:center;">
            <span class="util-pct-badge ${teamColor}">${teamPct.toFixed(0)}%</span>
        </td>`;
        footerHtml += '</tr>';
        bodyHtml += footerHtml;
    }

    tbody.innerHTML = bodyHtml;
}