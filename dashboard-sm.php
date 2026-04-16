<?php
header("X-Robots-Tag: noindex, nofollow", true);
require_once 'config/session_check.php';
require_once 'config/config.php';
require_once 'includes/auth.php';
require_once 'classes/Employee.php';
require_once 'classes/Project.php';

requireRole(['sales_manager', 'admin']);

$emp_id   = $_SESSION['emp_id'];
$emp_role = $_SESSION['emp_role'];
$empObj     = new Employee($conn);
$projectObj = new Project($conn);
$employee   = $empObj->getEmployeeById($emp_id);
$projects   = $projectObj->getProjectsForDashboard($emp_id, $emp_role);

$totalProjects  = count($projects);
$activeProjects = count(array_filter($projects, fn($p) => $p['is_active']));
$poorManaged    = count(array_filter($projects, fn($p) => ($p['performance']['classification'] ?? '') === 'Poorly Managed'));
$efficient      = count(array_filter($projects, fn($p) => ($p['performance']['classification'] ?? '') === 'Highly Efficient'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <script>if (localStorage.getItem('sidebar-collapsed') === 'true') document.documentElement.classList.add('sidebar-collapsed');</script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Sales Manager Dashboard — WildNet Timesheet</title>
    <link rel="stylesheet" href="assets/css/index2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"/>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .stat-strip  { display:flex; gap:14px; margin-bottom:24px; flex-wrap:wrap; }
        .stat-box    { flex:1; min-width:140px; background:#f5f7fa; border-radius:10px; padding:16px 18px; text-align:center; }
        .stat-box .val { font-size:1.8rem; font-weight:700; }
        .stat-box .lbl { font-size:.78rem; color:#888; }
        .badge-type  { display:inline-block; padding:3px 10px; border-radius:12px; font-size:.75rem; font-weight:600; }
        .badge-fixed { background:#e0f0ff; color:#1565c0; }
        .badge-tm    { background:#fff3e0; color:#e65100; }
        .badge-staff { background:#f3e5f5; color:#7b1fa2; }
        .perf-badge  { display:inline-block; padding:3px 10px; border-radius:12px; font-size:.75rem; font-weight:600; }
        .perf-efficient { background:#e8f5e9; color:#2e7d32; }
        .perf-effective { background:#e3f2fd; color:#1565c0; }
        .perf-poor   { background:#ffebee; color:#c62828; }
        .chart-card  { background:#fff; border:1px solid #e0e0e0; border-radius:12px; padding:20px; margin-bottom:20px; }
    </style>
</head>
<body>
<header>
    <a href="dashboard-sm"><div class="logo"><img src="assets/images/wnet-image.png" alt="WildNet logo"></div></a>
    <div class="search-area"></div>
    <div class="actions"></div>
</header>

<?php include_once "includes/sidebar-" . $employee['role'] . ".php"; ?>

<main>
    <div class="top-row">
        <div class="left"><h3>Sales Manager Dashboard</h3></div>
        <div class="right">
            <a href="manage-projects" class="add-btn">
                <i class="fa-solid fa-plus-circle"></i> New Project
            </a>
        </div>
    </div>
    <div class="content-area">

        <!-- Stats -->
        <div class="stat-strip">
            <div class="stat-box"><div class="val"><?= $totalProjects ?></div><div class="lbl">Total Projects</div></div>
            <div class="stat-box" style="background:#e8f5e9"><div class="val" style="color:#2e7d32"><?= $efficient ?></div><div class="lbl">Highly Efficient</div></div>
            <div class="stat-box" style="background:#ffebee"><div class="val" style="color:#c62828"><?= $poorManaged ?></div><div class="lbl">Poorly Managed</div></div>
            <div class="stat-box"><div class="val" style="color:#1565c0"><?= $activeProjects ?></div><div class="lbl">Active Projects</div></div>
        </div>

        <!-- Project Performance Doughnut -->
        <div style="display:grid;grid-template-columns:300px 1fr;gap:20px;margin-bottom:20px;">
            <div class="chart-card">
                <h5 style="margin:0 0 12px;font-size:.95rem;">
                    <i class="fa-solid fa-chart-pie" style="color:#7b1fa2;margin-right:6px;"></i> Performance Split
                </h5>
                <canvas id="chart-perf-donut" height="220"></canvas>
            </div>
            <div class="chart-card">
                <h5 style="margin:0 0 12px;font-size:.95rem;">
                    <i class="fa-solid fa-chart-bar" style="color:#1565c0;margin-right:6px;"></i> Budget vs Actual (by Project)
                </h5>
                <canvas id="chart-budget-bar" height="220"></canvas>
            </div>
        </div>

        <!-- Projects Table -->
        <div class="chart-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
                <h5 style="margin:0;font-size:.95rem;"><i class="fa-solid fa-table" style="color:#e65100;margin-right:6px;"></i> My Projects</h5>
                <a href="manage-projects" style="font-size:.82rem;color:#1565c0;">View All &rarr;</a>
            </div>
            <div class="panel-table-container">
                <table class="panel-table">
                    <thead>
                        <tr class="table-head">
                            <th>Project</th><th>Client</th><th>Type</th>
                            <th>Budget</th><th>Actual</th><th>Util%</th><th>Performance</th><th>PM</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($projects as $p):
                            $perf  = $p['performance'] ?? [];
                            $label = $perf['classification'] ?? '—';
                            $perfClass = $label === 'Highly Efficient' ? 'perf-efficient' : ($label === 'Effective' ? 'perf-effective' : 'perf-poor');
                            $typeMap = ['fixed_cost'=>'Fixed','time_material'=>'T&M','staff_augmentation'=>'Staff Aug'];
                            $typeBadgeClass = ['fixed_cost'=>'badge-fixed','time_material'=>'badge-tm','staff_augmentation'=>'badge-staff'][$p['project_type']] ?? '';
                        ?>
                        <tr>
                            <td><a href="manage-projects" style="font-weight:600;color:#1565c0;"><?= htmlspecialchars($p['project_name']) ?></a></td>
                            <td><?= htmlspecialchars($p['client_name'] ?? '—') ?></td>
                            <td><span class="badge-type <?= $typeBadgeClass ?>"><?= $typeMap[$p['project_type']] ?? '—' ?></span></td>
                            <td><?= $perf['budget_hours'] ?? 0 ?> hrs</td>
                            <td><?= $perf['actual_hours'] ?? 0 ?> hrs</td>
                            <td><?= $perf['utilization_pct'] ?? 0 ?>%</td>
                            <td><span class="perf-badge <?= $perfClass ?>"><?= $label ?></span></td>
                            <td><?= htmlspecialchars($p['managed_by_name'] ?? '—') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($projects)): ?>
                        <tr><td colspan="8" style="text-align:center;padding:30px;color:#aaa;">No projects yet. <a href="manage-projects">Create your first project</a>.</td></tr>
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
    const projects = <?= json_encode($projects) ?>;
    renderSMCharts(projects);
});
</script>
</body>
</html>
