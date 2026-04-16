<?php
// utils.php
// require_once  __DIR__ . '/../../config/config.php';

function getMissingTimesheets($date)
{
    global $conn;

    $executivesQuery = "
        SELECT e.emp_id, e.name AS emp_name, e.email AS emp_email, e.rm_id, rm.name AS rm_name, rm.email AS rm_email,
               d.dept_name AS dept, sd.subdept_name AS sub_dept
        FROM employee e
        LEFT JOIN employee rm ON e.rm_id = rm.emp_id
        LEFT JOIN department d ON e.dept_id = d.dept_id
        LEFT JOIN sub_department sd ON e.subdept_id = sd.subdept_id
        WHERE e.role in ('executive', '')
    ";

    $result = $conn->query($executivesQuery);
    $executives = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

    $results = [];

    foreach ($executives as $emp) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM task WHERE emp_id = ? AND date = ?");
        $stmt->bind_param("is", $emp['emp_id'], $date);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        if ($count == 0 && !emailAlreadySent($emp['emp_id'], 'missing', $date)) {
            $emp['task_date'] = $date; // Add date for table display
            $results[] = $emp;
        }
    }
    // echo "<br>inside getmissing() date :" . $date . "<br>";
    return $results;
}


// function getPendingApprovals($date)
// {
//     global $conn;

//     $query = "
//         SELECT t.emp_id, t.date AS task_date, e.name AS emp_name, e.email AS emp_email, e.rm_id, rm.name AS rm_name, rm.email AS rm_email, d.dept_name AS dept, sd.subdept_name AS sub_dept
//         FROM task t
//         JOIN employee e ON t.emp_id = e.emp_id
//         LEFT JOIN employee rm ON e.rm_id = rm.emp_id
//         LEFT JOIN department d ON e.dept_id = d.dept_id
//         LEFT JOIN sub_department sd ON e.subdept_id = sd.subdept_id
//         WHERE t.date = ? AND t.status = 0
//     ";

//     $stmt = $conn->prepare($query);
//     $stmt->bind_param("s", $date);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $tasks = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
//     $stmt->close();
//     echo "<br>inside getpending() date :" . $date . "<br>";
//     echo "<pre>";
//     print_r($tasks);
//     echo "</pre>";

//     $tasksFiltered = array_filter($tasks, fn($t) => !emailAlreadySent($t['emp_id'], 'pending', $date));
//     echo "<br> tasks Filtered: ";
//     echo "<pre>";
//     print_r($tasksFiltered);
//     echo "</pre>";

//     return $tasksFiltered;
// }
function getPendingApprovals($date)
{
    global $conn;

    $query = "
        SELECT 
            e.emp_id, 
            e.name AS emp_name, 
            e.email AS emp_email, 
            e.rm_id, 
            rm.name AS rm_name, 
            rm.email AS rm_email, 
            d.dept_name AS dept, 
            sd.subdept_name AS sub_dept,
            ? AS task_date
        FROM task t
        JOIN employee e ON t.emp_id = e.emp_id
        LEFT JOIN employee rm ON e.rm_id = rm.emp_id
        LEFT JOIN department d ON e.dept_id = d.dept_id
        LEFT JOIN sub_department sd ON e.subdept_id = sd.subdept_id
        WHERE t.date = ? AND t.status = 0
        GROUP BY e.emp_id, t.date
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $date, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();
    // Exclude if already sent
    return array_filter($tasks, fn($t) => !emailAlreadySent($t['emp_id'], 'pending', $date));
}



// function getEscalations($date)
// {
//     global $conn;
//     echo "inside getesc() date : " . $date . "<br>";
//     $query = "
//         SELECT t.emp_id, t.date AS task_date, e.name AS emp_name, e.email AS emp_email, hod.emp_id AS hod_id,
//                hod.name AS hod_name, hod.email AS hod_email, d.dept_name AS dept, sd.subdept_name AS sub_dept
//         FROM task t
//         JOIN employee e ON t.emp_id = e.emp_id
//         LEFT JOIN department d ON e.dept_id = d.dept_id
//         LEFT JOIN sub_department sd ON e.subdept_id = sd.subdept_id
//         LEFT JOIN employee rm ON e.rm_id = rm.emp_id
//         LEFT JOIN employee hod ON d.dept_head = hod.emp_id
//         WHERE t.date = ? AND t.status = 0
//     ";

//     $stmt = $conn->prepare($query);
//     $stmt->bind_param("s", $date);
//     $stmt->execute();
//     $result = $stmt->get_result();
//     $tasks = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
//     $stmt->close();
//     // echo $tasks;
//     echo "inside getesc()";
//     echo "<pre>";
//     print_r($tasks);
//     echo "</pre>";

//     return array_filter($tasks, fn($t) => !emailAlreadySent($t['emp_id'], 'escalation', $date));
// }


function getEscalations($date)
{
    global $conn;

    $query = "
        SELECT 
            e.emp_id, 
            e.name AS emp_name, 
            e.email AS emp_email, 
            hod.emp_id AS hod_id,
            hod.name AS hod_name, 
            hod.email AS hod_email, 
            d.dept_name AS dept, 
            sd.subdept_name AS sub_dept,
            ? AS task_date
        FROM task t
        JOIN employee e ON t.emp_id = e.emp_id
        LEFT JOIN department d ON e.dept_id = d.dept_id
        LEFT JOIN sub_department sd ON e.subdept_id = sd.subdept_id
        LEFT JOIN employee rm ON e.rm_id = rm.emp_id
        LEFT JOIN employee hod ON d.dept_head = hod.emp_id
        WHERE t.date = ? AND t.status = 0
        GROUP BY e.emp_id, t.date
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $date, $date);
    $stmt->execute();
    $result = $stmt->get_result();
    $tasks = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $stmt->close();

    return array_filter($tasks, fn($t) => !emailAlreadySent($t['emp_id'], 'escalation', $date));
}


function getLowWorkHourViolations($date)
{
    global $conn;

    $query = "
        SELECT 
            e.emp_id, 
            e.name AS emp_name, 
            e.email AS emp_email, 
            e.rm_id, 
            rm.name AS rm_name, 
            rm.email AS rm_email,
            d.dept_name AS dept, 
            sd.subdept_name AS sub_dept,
            t.date AS task_date,

            -- Total working hours in seconds and hours
            ROUND(SUM(CASE WHEN t.worktype = 'Working' THEN TIME_TO_SEC(t.duration) ELSE 0 END) / 3600, 2) AS total_working_hours,

            -- Check if any half day leave entry exists
            MAX(CASE WHEN t.worktype = 'Half-Day Leave' THEN 1 ELSE 0 END) AS has_half_day,

            -- Count of worktypes for the day to ignore non-working types
            GROUP_CONCAT(DISTINCT t.worktype) AS worktypes

        FROM task t
        JOIN employee e ON t.emp_id = e.emp_id
        LEFT JOIN employee rm ON e.rm_id = rm.emp_id
        LEFT JOIN department d ON e.dept_id = d.dept_id
        LEFT JOIN sub_department sd ON e.subdept_id = sd.subdept_id
        WHERE t.date = ?
        GROUP BY t.emp_id, t.date;
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $date);
    $stmt->execute();
    $result = $stmt->get_result();

    $violations = [];

    while ($row = $result->fetch_assoc()) {
        $hours = floatval($row['total_working_hours']);
        $hasHalfDay = intval($row['has_half_day']);
        $worktypes = explode(',', $row['worktypes']);

        // Skip if only non-working types exist
        $nonWorkingTypes = ['Leave', 'Week Off', 'Public Holiday'];
        if (array_diff($worktypes, $nonWorkingTypes) === []) {
            continue;
        }

        // Threshold: 4 for half day leave + working, 8 for working only
        $threshold = $hasHalfDay ? 4 : 8;
        $type = $hasHalfDay ? 'half_day_violation' : 'working_violation';

        if ($hours < $threshold && !emailAlreadySent($row['emp_id'], $type, $date)) {
            $violations[] = $row;
        }
    }

    $stmt->close();
    return $violations;
}


function emailAlreadySent($empId, $type, $date)
{
    global $conn;

    $stmt = $conn->prepare("SELECT 1 FROM email_logs WHERE emp_id = ? AND email_type = ? AND timesheet_date = ?");
    $stmt->bind_param("iss", $empId, $type, $date);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();

    return $exists;
}

function groupByRM(array $entries)
{
    $grouped = [];

    foreach ($entries as $entry) {
        $rmEmail = $entry['rm_email'];
        if (!$rmEmail)
            continue; // skip if RM missing

        if (!isset($grouped[$rmEmail])) {
            $grouped[$rmEmail] = [
                'rm_name' => $entry['rm_name'],
                'employees' => []
            ];
        }

        $grouped[$rmEmail]['employees'][] = $entry;
    }

    return $grouped;
}

function groupByHOD(array $entries)
{
    $grouped = [];

    foreach ($entries as $entry) {
        $hodEmail = $entry['hod_email'];
        if (!$hodEmail)
            continue; // skip if HOD missing

        if (!isset($grouped[$hodEmail])) {
            $grouped[$hodEmail] = [
                'hod_name' => $entry['hod_name'],
                'employees' => []
            ];
        }

        $grouped[$hodEmail]['employees'][] = $entry;
    }

    return $grouped;
}
