<?php
// cron.php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../vendor/autoload.php';
require_once 'mailer.php';
require_once 'utils.php';

date_default_timezone_set('Asia/Kolkata');
$today = new DateTime(); // e.g., Aug 4
$yesterday = (clone $today)->modify('-1 day');
$twoDaysAgo = (clone $today)->modify('-2 days');
$threeDaysAgo = (clone $today)->modify('-3 days');

// --- 1. Notify RMs & Executives for Missing Timesheets (for yesterday) ---
$missingEntries = getMissingTimesheets($yesterday->format('Y-m-d'));
$groupedByRM = groupByRM($missingEntries);
foreach ($groupedByRM as $rmEmail => $data) {
    sendMissingTimesheetEmail($rmEmail, $data['rm_name'], $data['employees']);

    // Send individual emails to executives
    foreach ($data['employees'] as $emp) {
        sendMissingTimesheetEmailToExecutive($emp);
    }
}


// --- 2. Notify RMs for Pending Approvals (>24 hrs) ---
$pendingApprovals = getPendingApprovals($twoDaysAgo->format('Y-m-d'));
echo "Pending  Count: " . count($pendingApprovals) . "\n";
$groupedPending = groupByRM($pendingApprovals);
foreach ($groupedPending as $rmEmail => $data) {
    sendPendingApprovalEmail($rmEmail, $data['rm_name'], $data['employees']);
}

// --- 3. Escalate to HOD if still unapproved after 48 hours ---
$escalations = getEscalations($threeDaysAgo->format('Y-m-d'));
echo "Escalations Count: " . count($escalations) . "\n";
$groupedHOD = groupByHOD($escalations);
foreach ($groupedHOD as $hodEmail => $data) {
    sendEscalationEmail($hodEmail, $data['hod_name'], $data['employees']);
}


// --- 4. Low Work Hour Violations (for yesterday only) ---
$lowWorkViolations = getLowWorkHourViolations($yesterday->format('Y-m-d'));
$groupedByRM = groupByRM($lowWorkViolations);
foreach ($groupedByRM as $rmEmail => $data) {
    sendLowHourEmail($rmEmail, $data['rm_name'], $data['employees']);
}


echo "Cron completed at " . date('Y-m-d H:i:s') . "\n";
