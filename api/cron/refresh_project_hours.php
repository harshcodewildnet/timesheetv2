<?php
/**
 * api/cron/refresh_project_hours.php
 * Cron script: refreshes project_hours_log cache table.
 * Schedule: Every 6 hours → 0 *\/6 * * *
 *
 * Run manually: php c:\xampp\htdocs\timesheetv2\api\cron\refresh_project_hours.php
 */

define('IS_CRON', true);
require_once __DIR__ . '/../../config/config.php';

$startTime = microtime(true);
$log = [];

// ─── Get all active projects ──────────────────────────────────────────────────
$result = $conn->query("SELECT project_id, project_type FROM project WHERE is_active = 1");
if (!$result) {
    error_log('[CRON refresh_project_hours] Failed to fetch projects: ' . $conn->error);
    exit(1);
}

$projects = $result->fetch_all(MYSQLI_ASSOC);
$currentMonth = date('Y-m');

// ─── Prepare UPSERT statement ─────────────────────────────────────────────────
$upsert = $conn->prepare("
    INSERT INTO project_hours_log (project_id, emp_id, month, actual_hours)
    VALUES (?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        actual_hours = VALUES(actual_hours),
        computed_at  = CURRENT_TIMESTAMP
");

if (!$upsert) {
    error_log('[CRON refresh_project_hours] Prepare failed: ' . $conn->error);
    exit(1);
}

foreach ($projects as $project) {
    $projectId = (int) $project['project_id'];

    // ── 1. Project-level rollup (emp_id = NULL) for current month ─────────────
    $stmt = $conn->prepare("
        SELECT ROUND(COALESCE(SUM(TIME_TO_SEC(duration)), 0) / 3600, 2) AS total
        FROM task
        WHERE project_id = ?
          AND DATE_FORMAT(date, '%Y-%m') = ?
    ");
    $stmt->bind_param('is', $projectId, $currentMonth);
    $stmt->execute();
    $stmt->bind_result($projectTotal);
    $stmt->fetch();
    $stmt->close();

    $empIdNull  = null;
    $upsert->bind_param('issd', $projectId, $empIdNull, $currentMonth, $projectTotal);
    $upsert->execute();
    $log[] = "Project $projectId — total: $projectTotal hrs ($currentMonth)";

    // ── 2. Per-member rollup ───────────────────────────────────────────────────
    $members = $conn->prepare("
        SELECT emp_id FROM project_member WHERE project_id = ? AND is_active = 1
    ");
    $members->bind_param('i', $projectId);
    $members->execute();
    $membersResult = $members->get_result();

    while ($member = $membersResult->fetch_assoc()) {
        $empId = (int) $member['emp_id'];

        $mStmt = $conn->prepare("
            SELECT ROUND(COALESCE(SUM(TIME_TO_SEC(duration)), 0) / 3600, 2) AS total
            FROM task
            WHERE project_id = ? AND emp_id = ?
              AND DATE_FORMAT(date, '%Y-%m') = ?
        ");
        $mStmt->bind_param('iis', $projectId, $empId, $currentMonth);
        $mStmt->execute();
        $mStmt->bind_result($memberTotal);
        $mStmt->fetch();
        $mStmt->close();

        $upsert->bind_param('issd', $projectId, $empId, $currentMonth, $memberTotal);
        $upsert->execute();
        $log[] = "  → emp $empId: $memberTotal hrs";
    }
    $members->close();
}

$upsert->close();
$elapsed = round(microtime(true) - $startTime, 3);

$logLine = '[' . date('Y-m-d H:i:s') . '] refresh_project_hours: '
    . count($projects) . ' projects refreshed in ' . $elapsed . 's';
error_log($logLine);

echo $logLine . PHP_EOL;
foreach ($log as $line) echo '  ' . $line . PHP_EOL;
