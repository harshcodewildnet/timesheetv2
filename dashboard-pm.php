<?php
header("X-Robots-Tag: noindex, nofollow", true);
require_once 'config/session_check.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';
require_once 'classes/Project.php';

requireRole(['project_manager', 'admin']);

$emp_id   = $_SESSION['emp_id'];
$emp_role = $_SESSION['emp_role'];
$empObj     = new Employee($conn);
$projectObj = new Project($conn);
$employee   = $empObj->getEmployeeById($emp_id);
$projects   = $projectObj->getProjectsForDashboard($emp_id, $emp_role);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>if (localStorage.getItem('sidebar-collapsed') === 'true') document.documentElement.classList.add('sidebar-collapsed');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Project Manager Dashboard — WildNet Timesheet</title>
    <link rel="stylesheet" href="assets/css/index2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .pm-grid        { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .project-card   { background: #fff; border: 1px solid #e0e0e0; border-radius: 12px; padding: 18px 20px; cursor: pointer; transition: box-shadow 0.2s; }
        .project-card:hover { box-shadow: 0 4px 18px rgba(0,0,0,0.1); }
        .project-card .pc-type  { font-size:.75rem; font-weight:600; margin-bottom:6px; }
        .project-card .pc-name  { font-size:1.05rem; font-weight:700; margin-bottom:10px; }
        .project-card .pc-bar-bg{ background:#e0e0e0; border-radius:6px; height:8px; overflow:hidden; margin-bottom:6px; }
        .project-card .pc-bar-fill { height:100%; border-radius:6px; transition:width .5s ease; }
        .project-card .pc-meta  { font-size:.78rem; color:#888; display:flex; justify-content:space-between; }
        .perf-badge     { display:inline-block; padding:3px 10px; border-radius:12px; font-size:.75rem; font-weight:600; }
        .perf-efficient { background:#e8f5e9; color:#2e7d32; }
        .perf-effective { background:#e3f2fd; color:#1565c0; }
        .perf-poor      { background:#ffebee; color:#c62828; }
        .badge-fixed    { background:#e0f0ff; color:#1565c0; border-radius:10px; padding:2px 8px; }
        .badge-tm       { background:#fff3e0; color:#e65100; border-radius:10px; padding:2px 8px; }
        .badge-staff    { background:#f3e5f5; color:#7b1fa2; border-radius:10px; padding:2px 8px; }
        .chart-card     { background:#fff; border:1px solid #e0e0e0; border-radius:12px; padding:20px; margin-bottom:20px; }
        .chart-card h5  { margin:0 0 14px; font-size:.95rem; }
        .stat-strip     { display:flex; gap:14px; margin-bottom:20px; flex-wrap:wrap; }
        .stat-box       { flex:1; min-width:140px; background:#f5f7fa; border-radius:10px; padding:16px 18px; text-align:center; }
        .stat-box .val  { font-size:1.8rem; font-weight:700; }
        .stat-box .lbl  { font-size:.78rem; color:#888; }
    </style>
</head>
<body>
<header>
    <a href="dashboard-pm"><div class="logo"><img src="assets/images/wnet-image.png" alt="WildNet logo"></div></a>
    <div class="search-area"></div>
    <div class="actions"></div>
</header>

<?php include_once "includes/sidebar-" . $employee['role'] . ".php"; ?>

<main>
    <div class="top-row"><div class="left"><h3>Project Manager Dashboard</h3></div></div>
    <div class="content-area">

        <!-- Summary strip (populated by JS) -->
        <div class="stat-strip" id="pm-stats"></div>

        <!-- Project Cards -->
        <h4 style="margin-bottom:12px;">My Projects</h4>
        <div class="pm-grid" id="pm-project-grid">
            <?php foreach ($projects as $p):
                $perf  = $p['performance'] ?? [];
                $label = $perf['classification'] ?? '—';
                $pct   = $perf['utilization_pct'] ?? 0;
                $act   = $perf['actual_hours'] ?? 0;
                $bud   = $perf['budget_hours'] ?? 0;
                $fillColor = $pct > 100 ? '#f44336' : ($pct > 85 ? '#ff9800' : '#4caf50');
                $perfClass = $label === 'Highly Efficient' ? 'perf-efficient' : ($label === 'Effective' ? 'perf-effective' : 'perf-poor');
                $typeBadge = [
                    'fixed_cost'        => '<span class="badge-fixed">Fixed Cost</span>',
                    'time_material'     => '<span class="badge-tm">T&M</span>',
                    'staff_augmentation'=> '<span class="badge-staff">Staff Aug</span>',
                ][$p['project_type']] ?? $p['project_type'];
            ?>
            <div class="project-card" onclick="window.location='manage-projects'">
                <div class="pc-type"><?= $typeBadge ?></div>
                <div class="pc-name"><?= htmlspecialchars($p['project_name']) ?></div>
                <div style="margin-bottom:4px;"><span class="perf-badge <?= $perfClass ?>"><?= $label ?></span></div>
                <div class="pc-bar-bg">
                    <div class="pc-bar-fill" style="width:<?= min($pct,100) ?>%;background:<?= $fillColor ?>;"></div>
                </div>
                <div class="pc-meta">
                    <span><?= $act ?> / <?= $bud ?> hrs</span>
                    <span><?= $pct ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($projects)): ?>
            <div style="grid-column:1/-1;text-align:center;padding:40px;color:#aaa;">
                <i class="fa-solid fa-diagram-project" style="font-size:3rem;margin-bottom:12px;display:block;"></i>
                No projects assigned yet.
            </div>
            <?php endif; ?>
        </div>

        <!-- Charts Row -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-top:8px;">
            <div class="chart-card">
                <h5><i class="fa-solid fa-chart-bar" style="color:#1565c0;margin-right:6px;"></i> Resource Utilization (Allocated vs Actual)</h5>
                <canvas id="chart-resource" height="220"></canvas>
            </div>
            <div class="chart-card">
                <h5><i class="fa-solid fa-chart-line" style="color:#e65100;margin-right:6px;"></i> Monthly Hours Trend (T&M Projects)</h5>
                <canvas id="chart-trend" height="220"></canvas>
            </div>
        </div>

        <!-- Project Hours Detail Table -->
        <div class="chart-card" style="margin-top:20px;">
            <h5><i class="fa-solid fa-table" style="color:#7b1fa2;margin-right:6px;"></i> Project Hours Overview</h5>
            <div class="panel-table-container">
                <table class="panel-table">
                    <thead>
                        <tr class="table-head">
                            <th>Project</th><th>Type</th><th>Budget</th>
                            <th>Actual</th><th>Remaining</th><th>Utilization %</th><th>Performance</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $p):
                            $perf = $p['performance'] ?? [];
                            $label = $perf['classification'] ?? '—';
                            $perfClass = $label === 'Highly Efficient' ? 'perf-efficient' : ($label === 'Effective' ? 'perf-effective' : 'perf-poor');
                            $rem = round(($perf['budget_hours'] ?? 0) - ($perf['actual_hours'] ?? 0), 2);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($p['project_name']) ?></td>
                            <td><?= str_replace(['fixed_cost','time_material','staff_augmentation'],['Fixed','T&M','Staff Aug'], $p['project_type']) ?></td>
                            <td><?= $perf['budget_hours']      ?? 0 ?> hrs</td>
                            <td><?= $perf['actual_hours']      ?? 0 ?> hrs</td>
                            <td style="color:<?= $rem < 0 ? '#c62828' : '#2e7d32' ?>"><?= $rem ?> hrs</td>
                            <td><?= $perf['utilization_pct']   ?? 0 ?>%</td>
                            <td><span class="perf-badge <?= $perfClass ?>"><?= $label ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?>
                        <tr><td colspan="7" style="text-align:center;padding:20px;">No data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</main>

<div id="toast">Saved!</div>
<script src="assets/js/main.js"></script>
<script src="assets/js/project-graph.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    // Build summary stats
    const projects = <?= json_encode($projects) ?>;
    let efficient = 0, effective = 0, poor = 0;
    projects.forEach(p => {
        const c = p.performance?.classification;
        if (c === 'Highly Efficient') efficient++;
        else if (c === 'Effective')   effective++;
        else if (c === 'Poorly Managed') poor++;
    });
    document.getElementById('pm-stats').innerHTML = `
        <div class="stat-box"><div class="val">${projects.length}</div><div class="lbl">Total Projects</div></div>
        <div class="stat-box" style="background:#e8f5e9"><div class="val" style="color:#2e7d32">${efficient}</div><div class="lbl">Highly Efficient</div></div>
        <div class="stat-box" style="background:#e3f2fd"><div class="val" style="color:#1565c0">${effective}</div><div class="lbl">Effective</div></div>
        <div class="stat-box" style="background:#ffebee"><div class="val" style="color:#c62828">${poor}</div><div class="lbl">Poorly Managed</div></div>
    `;

    initProjectCharts();
});
</script>
</body>
</html>
