<?php

class Task
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getEmployeeTasks($id)
    {
        $stmt = $this->conn->prepare("SELECT t.*, tc.cat_name, tc.cat_id, tsc.subcat_id, tsc.brief FROM task t left join task_category tc on t.cat_id = tc.cat_id left join task_subcategory tsc on t.subcat_id = tsc.subcat_id WHERE emp_id = ? order by t.date desc");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();

        $tasks = [];
        while ($row = $result->fetch_assoc()) {
            $tasks[] = $row;
        }

        return $tasks;
    }

    // public Tasks(
    //     $employees,
    //     $departments,
    //     $repManagers,
    //     $status,
    //     $timeline,
    //     $customFrom,
    //     $customTo,
    //     $allowedEmployees = [],
    //     $page = 1,
    //     $limit = 25
    // ) {
    //     $offset = ($page - 1) * $limit;

    //     $query = "SELECT SQL_CALC_FOUND_ROWS 
    //             t.task_id, t.emp_id, t.worktype, t.description, t.date, t.duration, t.status, t.comment,
    //             tc.cat_name AS cat_name, 
    //             tscat.brief AS brief, 
    //             e.name AS emp_name, 
    //             e.rm_id AS rm_id, 
    //             d.dept_name, 
    //             d.dept_id 
    //           FROM task t 
    //           JOIN task_category tc ON t.cat_id = tc.cat_id 
    //           LEFT JOIN task_subcategory tscat ON t.subcat_id = tscat.subcat_id 
    //           JOIN employee e ON t.emp_id = e.emp_id 
    //           JOIN department d ON e.dept_id = d.dept_id 
    //           WHERE 1 = 1";

    //     $params = [];
    //     $types = '';

    //     // if (!empty($loggedUserId)) {
    //     //     $query .= " AND e.rm_id = ?";
    //     //     $types .= 'i';
    //     //     $params[] = $loggedUserId;
    //     // }

    //     // Restrict by allowedEmployees
    //     if (!empty($allowedEmployees)) {
    //         $placeholders = implode(',', array_fill(0, count($allowedEmployees), '?'));
    //         $query .= " AND e.emp_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($allowedEmployees));
    //         $params = array_merge($params, $allowedEmployees);
    //     }

    //     if (!empty($employees)) {
    //         $placeholders = implode(',', array_fill(0, count($employees), '?'));
    //         $query .= " AND e.emp_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($employees));
    //         $params = array_merge($params, $employees);
    //     } else {
    //         // $employees = [$loggedUserId];
    //         // $query .= " AND e.emp_id = ?";
    //         // $types .= 's';
    //         // $params[] = $loggedUserId;
    //     }

    //     if (!empty($departments)) {
    //         $placeholders = implode(',', array_fill(0, count($departments), '?'));
    //         $query .= " AND d.dept_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($departments));
    //         $params = array_merge($params, $departments);
    //     }

    //     if (!empty($repManagers)) {
    //         $placeholders = implode(',', array_fill(0, count($repManagers), '?'));
    //         $query .= " AND e.rm_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($repManagers));
    //         $params = array_merge($params, $repManagers);
    //     }

    //     if (!empty($status)) {
    //         $placeholders = implode(',', array_fill(0, count($status), '?'));
    //         $query .= " AND t.status IN ($placeholders)";
    //         $types .= str_repeat('i', count($status));
    //         $params = array_merge($params, $status);
    //     }

    //     if (!empty($timeline)) {
    //         if ($timeline == 'today') {
    //             $query .= " AND DATE(t.date) = CURDATE()";
    //         } elseif ($timeline == 'last7') {
    //             $query .= " AND t.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    //         } elseif ($timeline == 'last30') {
    //             $query .= " AND t.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    //         } elseif ($timeline == 'custom' && !empty($customFrom) && !empty($customTo)) {
    //             $query .= " AND DATE(t.date) BETWEEN ? AND ?";
    //             $types .= 'ss';
    //             $params[] = $customFrom;
    //             $params[] = $customTo;
    //         } else {
    //         }
    //     }

    //     $query .= " AND e.role != 'hod'";
    //     $query .= " ORDER BY t.date DESC, e.emp_id ASC";
    //     $query .= " LIMIT ? OFFSET ?";

    //     $types .= 'ii';
    //     $params[] = $limit;
    //     $params[] = $offset;


    //     $start = microtime(true);

    //     $stmt = $this->conn->prepare($query);
    //     if (!empty($params)) {
    //         $stmt->bind_param($types, ...$params);
    //     }

    //     $stmt->execute();
    //     $result = $stmt->get_result();

    //     $end = microtime(true);
    //     $executionTime = $end - $start;

    //     $tasks = [];
    //     while ($row = $result->fetch_assoc()) {
    //         $tasks[] = $row;
    //     }

    //     // Get total count (without LIMIT)
    //     $countResult = $this->conn->query("SELECT FOUND_ROWS() as total");
    //     $total = $countResult->fetch_assoc()['total'];

    //     return [
    //         'timeline' => $timeline,
    //         'tasks' => $tasks,
    //         'total' => $total,
    //         'page' => $page,
    //         'limit' => $limit,
    //         'exec_time' => $executionTime
    //     ];
    // }


    // public function getFilteredssTasks($employees, $departments, $repManagers, $status, $timeline, $customFrom, $customTo, $allowedEmployees = [])
    // {
    //     $query = "SELECT t.*, tc.cat_name AS cat_name, tscat.brief AS brief, e.name AS emp_name, e.rm_id AS rm_id, d.dept_name, d.dept_id FROM task t JOIN task_category tc ON t.cat_id = tc.cat_id LEFT JOIN task_subcategory tscat ON t.subcat_id = tscat.subcat_id JOIN employee e ON t.emp_id = e.emp_id JOIN department d ON e.dept_id = d.dept_id WHERE 1 = 1";

    //     $params = [];
    //     $types = '';

    //     // if (!empty($loggedUserId)) {
    //     //     $query .= " AND e.rm_id = ?";
    //     //     $types .= 'i';
    //     //     $params[] = $loggedUserId;
    //     // }

    //     // Restrict by allowedEmployees
    //     if (!empty($allowedEmployees)) {
    //         $placeholders = implode(',', array_fill(0, count($allowedEmployees), '?'));
    //         $query .= " AND e.emp_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($allowedEmployees));
    //         $params = array_merge($params, $allowedEmployees);
    //     }

    //     if (!empty($employees)) {
    //         $placeholders = implode(',', array_fill(0, count($employees), '?'));
    //         $query .= " AND e.emp_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($employees));
    //         $params = array_merge($params, $employees);
    //     } else {
    //         // $employees = [$loggedUserId];
    //         // $query .= " AND e.emp_id = ?";
    //         // $types .= 's';
    //         // $params[] = $loggedUserId;
    //     }

    //     if (!empty($departments)) {
    //         $placeholders = implode(',', array_fill(0, count($departments), '?'));
    //         $query .= " AND d.dept_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($departments));
    //         $params = array_merge($params, $departments);
    //     }

    //     if (!empty($repManagers)) {
    //         $placeholders = implode(',', array_fill(0, count($repManagers), '?'));
    //         $query .= " AND e.rm_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($repManagers));
    //         $params = array_merge($params, $repManagers);
    //     }

    //     if (!empty($status)) {
    //         $placeholders = implode(',', array_fill(0, count($status), '?'));
    //         $query .= " AND t.status IN ($placeholders)";
    //         $types .= str_repeat('i', count($status));
    //         $params = array_merge($params, $status);
    //     }

    //     if (!empty($timeline)) {
    //         if ($timeline == 'today') {
    //             $query .= " AND DATE(t.date) = CURDATE()";
    //         } elseif ($timeline == 'last7') {
    //             $query .= " AND t.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    //         } elseif ($timeline == 'last30') {
    //             $query .= " AND t.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    //         } elseif ($timeline == 'custom' && !empty($customFrom) && !empty($customTo)) {
    //             $query .= " AND DATE(t.date) BETWEEN ? AND ?";
    //             $types .= 'ss';
    //             $params[] = $customFrom;
    //             $params[] = $customTo;
    //         } else {
    //         }
    //     }


    //     $query .= " AND e.role != 'hod'";
    //     $query .= " ORDER BY t.date DESC, e.emp_id ASC";

    //     $stmt = $this->conn->prepare($query);
    //     if (!empty($params)) {
    //         $stmt->bind_param($types, ...$params);
    //     }

    //     $stmt->execute();
    //     $result = $stmt->get_result();

    //     $tasks = [];
    //     while ($row = $result->fetch_assoc()) {
    //         $tasks[] = $row;
    //     }

    //     return [
    //         'timeline' => $timeline,
    //         'tasks' => $tasks,
    //         'managers' => $repManagers,
    //         'query' => $stmt,
    //         'employees[]' => $employees,
    //         'allowedEmployees[]' => $allowedEmployees
    //     ];

    // }

    // public function getFilteredTasks($employees, $departments, $repManagers, $status, $timeline, $customFrom, $customTo, $allowedEmployees = [], $page, $limit, $taskType = 'all')
    // {
    //     $query = "SELECT t.*, tc.cat_name, tscat.brief, e.name AS emp_name, e.rm_id, d.dept_name, d.dept_id
    //           FROM task t
    //           JOIN task_category tc ON t.cat_id = tc.cat_id
    //           LEFT JOIN task_subcategory tscat ON t.subcat_id = tscat.subcat_id
    //           JOIN employee e ON t.emp_id = e.emp_id
    //           JOIN department d ON e.dept_id = d.dept_id
    //           WHERE 1=1";

    //     $params = [];
    //     $types = '';

    //     // Restrict by allowedEmployees
    //     if (!empty($allowedEmployees)) {
    //         $placeholders = implode(',', array_fill(0, count($allowedEmployees), '?'));
    //         $query .= " AND e.emp_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($allowedEmployees));
    //         $params = array_merge($params, $allowedEmployees);
    //     }

    //     // Filter by employees
    //     if (!empty($employees)) {
    //         $placeholders = implode(',', array_fill(0, count($employees), '?'));
    //         $query .= " AND e.emp_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($employees));
    //         $params = array_merge($params, $employees);
    //     }

    //     // Filter by department
    //     if (!empty($departments)) {
    //         $placeholders = implode(',', array_fill(0, count($departments), '?'));
    //         $query .= " AND d.dept_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($departments));
    //         $params = array_merge($params, $departments);
    //     }

    //     // Filter by reporting manager
    //     if (!empty($repManagers)) {
    //         $placeholders = implode(',', array_fill(0, count($repManagers), '?'));
    //         $query .= " AND e.rm_id IN ($placeholders)";
    //         $types .= str_repeat('i', count($repManagers));
    //         $params = array_merge($params, $repManagers);
    //     }

    //     // Filter by status
    //     if (!empty($status)) {
    //         $placeholders = implode(',', array_fill(0, count($status), '?'));
    //         $query .= " AND t.status IN ($placeholders)";
    //         $types .= str_repeat('i', count($status));
    //         $params = array_merge($params, $status);
    //     }

    //     // Filter by timeline
    //     if (!empty($timeline)) {
    //         if ($timeline == 'today') {
    //             $query .= " AND DATE(t.date) = CURDATE()";
    //         } elseif ($timeline == 'last7') {
    //             $query .= " AND t.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
    //         } elseif ($timeline == 'last30') {
    //             $query .= " AND t.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    //         } elseif ($timeline == 'custom' && !empty($customFrom) && !empty($customTo)) {
    //             $query .= " AND DATE(t.date) BETWEEN ? AND ?";
    //             $types .= 'ss';
    //             $params[] = $customFrom;
    //             $params[] = $customTo;
    //         }
    //     }

    //     $query .= " AND e.role != 'hod'";

    //     // Type-specific filters
    //     switch ($taskType) {
    //         case 'missed':
    //             // No record found for employee for that date range
    //             // You can later fetch via LEFT JOIN on dates table or logic elsewhere
    //             $query .= " AND t.status = 'missed'";
    //             break;

    //         case 'pending':
    //             // Task exists but not yet approved
    //             $query .= " AND t.status = '0'";
    //             break;

    //         case 'lowhours':
    //             // Low work-hour violations
    //             $query .= " AND (
    //             (t.worktype = 'Working' AND t.duration < '08:00:00')
    //             OR (t.worktype = 'Half-Day Leave' AND t.duration < '04:00:00')
    //         )";
    //             break;

    //         case 'special':
    //             // Special cases like Week off, Holiday, etc.
    //             $query .= " AND t.worktype IN ('Week Off', 'Public Holiday', 'Leave')";
    //             break;

    //         case 'all':
    //         default:
    //             // no extra condition
    //             break;
    //     }

    //     $query .= " ORDER BY t.date DESC, e.emp_id ASC";

    //     $stmt = $this->conn->prepare($query);
    //     if (!empty($params)) {
    //         $stmt->bind_param($types, ...$params);
    //     }

    //     $stmt->execute();
    //     $result = $stmt->get_result();
    //     $tasks = [];

    //     while ($row = $result->fetch_assoc()) {
    //         $tasks[] = $row;
    //     }

    //     return [
    //         'managers' => $repManagers,
    //         'timeline' => $timeline,
    //         'tasks' => $tasks,
    //         'type' => $taskType,
    //         'query_debug' => $stmt
    //     ];
    // }

    // unified method
    public function getFilteredTasks(
        $employees,
        $departments,
        $repManagers,
        $status,
        $timeline,
        $customFrom,
        $customTo,
        $allowedEmployees = [],
        $page = 1,
        $limit = 100,
        $taskType = 'all'
    ) {
        switch ($taskType) {
            case 'missed':
                return $this->getMissedTasks($employees, $departments, $repManagers, $timeline, $customFrom, $customTo, $allowedEmployees, $page, $limit);

            case 'pending':
                return $this->getPendingTasks($employees, $departments, $repManagers, $timeline, $customFrom, $customTo, $allowedEmployees, $page, $limit);

            case 'lowhours':
                return $this->getLowHourTasks($employees, $departments, $repManagers, $timeline, $customFrom, $customTo, $allowedEmployees, $page, $limit);

            case 'special':
                return $this->getSpecialTasks($employees, $departments, $repManagers, $status, $timeline, $customFrom, $customTo, $allowedEmployees, $page, $limit);

            case 'all':
            default:
                return $this->getAllTasks($employees, $departments, $repManagers, $status, $timeline, $customFrom, $customTo, $allowedEmployees, $page, $limit);
        }
    }


    //////////////////// OLD
    // private function getAllTasks($employees, $departments, $repManagers, $status, $timeline, $customFrom, $customTo, $allowedEmployees, $limit)
    // {
    //     try {
    //         $query = "SELECT t.*, tc.cat_name, tscat.brief, e.name AS name, e.email, e.rm_id, 
    //                     d.dept_name, d.dept_id, cl.client_name
    //               FROM task t
    //               JOIN task_category tc ON t.cat_id = tc.cat_id
    //               LEFT JOIN task_subcategory tscat ON t.subcat_id = tscat.subcat_id
    //               JOIN employee e ON t.emp_id = e.emp_id
    //               JOIN department d ON e.dept_id = d.dept_id
    //               LEFT JOIN client cl ON t.client_id = cl.client_id
    //               WHERE e.role != 'hod'";

    //         $params = [];
    //         $types = '';

    //         // Apply filters
    //         $this->applyCommonFilters(
    //             $query,
    //             $params,
    //             $types,
    //             $employees,
    //             $departments,
    //             $repManagers,
    //             $status,
    //             $timeline,
    //             $customFrom,
    //             $customTo,
    //             $allowedEmployees
    //         );

    //         $query .= " ORDER BY t.date DESC, e.emp_id ASC";

    //         $stmt = $this->conn->prepare($query);
    //         if (!$stmt) {
    //             return [
    //                 'status' => 'error',
    //                 'message' => 'Failed to prepare statement.',
    //                 'error' => $this->conn->error
    //             ];
    //         }

    //         if (!empty($params)) {
    //             if (!$stmt->bind_param($types, ...$params)) {
    //                 return [
    //                     'status' => 'error',
    //                     'message' => 'Failed to bind SQL parameters.',
    //                     'error' => $stmt->error
    //                 ];
    //             }
    //         }

    //         if (!$stmt->execute()) {
    //             return [
    //                 'status' => 'error',
    //                 'message' => 'Database query failed to execute.',
    //                 'error' => $stmt->error
    //             ];
    //         }

    //         $result = $stmt->get_result();
    //         if (!$result) {
    //             return [
    //                 'status' => 'error',
    //                 'message' => 'Failed to fetch query results.',
    //                 'error' => $stmt->error
    //             ];
    //         }

    //         $tasks = [];
    //         while ($row = $result->fetch_assoc()) {
    //             $tasks[] = $row;
    //         }

    //         return [
    //             'status' => 'success',
    //             'type' => 'all',
    //             'message' => empty($tasks) ? "No tasks found." : "Tasks retrieved.",
    //             'tasks' => $tasks
    //         ];

    //     } catch (Exception $e) {
    //         return [
    //             'status' => 'error',
    //             'message' => 'Unexpected error while fetching all tasks.',
    //             'error' => $e->getMessage()
    //         ];
    //     }
    // }

    ////////////// NEW
    private function getAllTasks(
        $employees,
        $departments,
        $repManagers,
        $status,
        $timeline,
        $customFrom,
        $customTo,
        $allowedEmployees,
        $page = 1,
        $limit = 100
    ) {
        try {

            $offset = ($page - 1) * $limit;

            // Add SQL_CALC_FOUND_ROWS to count total results
            $query = "SELECT SQL_CALC_FOUND_ROWS
                        t.*, 
                        tc.cat_name, 
                        tscat.brief, 
                        e.name AS name, 
                        e.email, 
                        e.rm_id, 
                        d.dept_name, 
                        d.dept_id, 
                        cl.client_name
                    FROM task t
                    JOIN task_category tc ON t.cat_id = tc.cat_id
                    LEFT JOIN task_subcategory tscat ON t.subcat_id = tscat.subcat_id
                    JOIN employee e ON t.emp_id = e.emp_id
                    JOIN department d ON e.dept_id = d.dept_id
                    LEFT JOIN client cl ON t.client_id = cl.client_id
                    WHERE e.role != 'hod'";

            $params = [];
            $types = '';

            // Apply dynamically
            $this->applyCommonFilters(
                $query,
                $params,
                $types,
                $employees,
                $departments,
                $repManagers,
                $status,
                $timeline,
                $customFrom,
                $customTo,
                $allowedEmployees
            );

            // Append ordering + pagination
            $query .= " ORDER BY t.date DESC, e.emp_id ASC";
            $query .= " LIMIT ? OFFSET ?";

            // Add limit + offset params
            $types .= "ii";
            $params[] = $limit;
            $params[] = $offset;

            // Prepare main query
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to prepare statement.',
                    'error' => $this->conn->error
                ];
            }

            if (!empty($params)) {
                if (!$stmt->bind_param($types, ...$params)) {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to bind SQL parameters.',
                        'error' => $stmt->error
                    ];
                }
            }

            if (!$stmt->execute()) {
                return [
                    'status' => 'error',
                    'message' => 'Database query failed to execute.',
                    'error' => $stmt->error
                ];
            }

            $result = $stmt->get_result();
            if (!$result) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to fetch query results.',
                    'error' => $stmt->error
                ];
            }

            $tasks = [];
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }

            // Fetch total count
            $countResult = $this->conn->query("SELECT FOUND_ROWS() AS total");
            $total = $countResult ? intval($countResult->fetch_assoc()['total']) : 0;

            return [
                'status' => 'success',
                'type' => 'all',
                'message' => empty($tasks) ? "No tasks found." : "Tasks retrieved.",
                'tasks' => $tasks,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ];

        } catch (Exception $e) {

            return [
                'status' => 'error',
                'message' => 'Unexpected error while fetching all tasks.',
                'error' => $e->getMessage()
            ];
        }
    }





    private function getPendingTasks($employees, $departments, $repManagers, $timeline, $customFrom, $customTo, $allowedEmployees, $page = 1, $limit = 100)
    {
        try {


            $offset = ($page - 1) * $limit;

            $query = "SELECT SQL_CALC_FOUND_ROWS t.*, tc.cat_name, ts.brief, e.name, e.rm_id, d.dept_name, e.dept_id
                  FROM task t
                  JOIN task_category tc ON t.cat_id = tc.cat_id
                  LEFT JOIN task_subcategory ts ON t.subcat_id = ts.subcat_id
                  JOIN employee e ON t.emp_id = e.emp_id
                  JOIN department d ON e.dept_id = d.dept_id
                  WHERE t.status = 0 AND e.role != 'hod'";

            $params = [];
            $types = '';

            $this->applyCommonFilters(
                $query,
                $params,
                $types,
                $employees,
                $departments,
                $repManagers,
                [],
                $timeline,
                $customFrom,
                $customTo,
                $allowedEmployees
            );

            $query .= " ORDER BY t.date DESC, e.emp_id ASC";
            $query .= " LIMIT ? OFFSET ?";

            $types .= "ii";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to prepare statement.',
                    'error' => $this->conn->error
                ];
            }

            if (!empty($params)) {
                if (!$stmt->bind_param($types, ...$params)) {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to bind SQL parameters.',
                        'error' => $stmt->error
                    ];
                }
            }

            if (!$stmt->execute()) {
                return [
                    'status' => 'error',
                    'message' => 'Database query failed to execute.',
                    'error' => $stmt->error
                ];
            }

            $result = $stmt->get_result();
            if (!$result) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to fetch query results.',
                    'error' => $stmt->error
                ];
            }

            $tasks = [];
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }

            // Fetch total count
            $countResult = $this->conn->query("SELECT FOUND_ROWS() AS total");
            $total = $countResult ? intval($countResult->fetch_assoc()['total']) : 0;

            return [
                'status' => 'success',
                'type' => 'pending',
                'message' => empty($tasks) ? "No pending tasks found." : "Pending tasks retrieved.",
                'tasks' => $tasks,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Unexpected error while fetching pending tasks.',
                'error' => $e->getMessage()
            ];
        }
    }


    private function getLowHourTasks($employees, $departments, $repManagers, $timeline, $customFrom, $customTo, $allowedEmployees, $page = 1, $limit = 100)
    {
        // $query = "SELECT DATE(t.date) AS date, e.emp_id, e.name, e.email, rm.name AS rm_name,
        //              SEC_TO_TIME(SUM(TIME_TO_SEC(t.duration))) AS total_duration
        //       FROM task t
        //       JOIN employee e ON t.emp_id = e.emp_id
        //       LEFT JOIN employee rm ON rm.emp_id = e.rm_id
        //       JOIN department d ON e.dept_id = d.dept_id
        //       WHERE e.role != 'hod'";

        // $params = [];
        // $types = '';

        // $this->applyCommonFilters($query, $params, $types, $employees, $departments, $repManagers, [], $timeline, $customFrom, $customTo, $allowedEmployees);

        // $query .= " GROUP BY DATE(t.date), e.emp_id
        //         HAVING total_duration < '08:00:00'
        //         ORDER BY t.date DESC, e.emp_id ASC";

        // $stmt = $this->conn->prepare($query);
        // if (!empty($params))
        //     $stmt->bind_param($types, ...$params);
        // $stmt->execute();
        // $result = $stmt->get_result();

        // $tasks = [];
        // while ($row = $result->fetch_assoc())
        //     $tasks[] = $row;

        // return ['type' => 'lowhours', 'tasks' => $tasks];



        try {

            $offset = ($page - 1) * $limit;

            $query = "SELECT SQL_CALC_FOUND_ROWS DATE(t.date) AS date, e.emp_id, e.name, e.email, rm.name AS rm_name, e.dept_id,
                         SEC_TO_TIME(SUM(TIME_TO_SEC(t.duration))) AS total_duration
                  FROM task t
                  JOIN employee e ON t.emp_id = e.emp_id
                  LEFT JOIN employee rm ON rm.emp_id = e.rm_id
                  JOIN department d ON e.dept_id = d.dept_id
                  WHERE e.role != 'hod'";

            $params = [];
            $types = '';

            // Apply filters safely
            $this->applyCommonFilters($query, $params, $types, $employees, $departments, $repManagers, [], $timeline, $customFrom, $customTo, $allowedEmployees);

            $query .= " GROUP BY DATE(t.date), e.emp_id
                    HAVING total_duration < '08:00:00'
                    ORDER BY DATE(t.date) DESC, e.emp_id ASC";

            $query .= " LIMIT ? OFFSET ?";

            $types .= "ii";
            $params[] = $limit;
            $params[] = $offset;


            // Prepare statement
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return [
                    'status' => 'error',
                    'message' => 'Database error: Failed to prepare statement.',
                    'query' => $query,
                    'error' => $this->conn->error
                ];
            }

            // Bind parameters if any
            if (!empty($params)) {
                if (!$stmt->bind_param($types, ...$params)) {
                    return [
                        'status' => 'error',
                        'message' => 'Database error: Failed to bind parameters.',
                        'error' => $stmt->error
                    ];
                }
            }

            // Execute query
            if (!$stmt->execute()) {
                return [
                    'status' => 'error',
                    'message' => 'Database error: Failed to execute query.',
                    'error' => $stmt->error
                ];
            }

            // Get results
            $result = $stmt->get_result();
            if (!$result) {
                return [
                    'status' => 'error',
                    'message' => 'Database error: Failed to fetch results.',
                    'error' => $stmt->error
                ];
            }

            $tasks = [];
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }

            // Fetch total count
            $countResult = $this->conn->query("SELECT FOUND_ROWS() AS total");
            $total = $countResult ? intval($countResult->fetch_assoc()['total']) : 0;


            return [
                'status' => 'success',
                'type' => 'lowhours',
                'message' => 'Low-hour tasks retrieved successfully.',
                'tasks' => $tasks,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ];
        } catch (Exception $e) {
            // Catch unexpected exceptions
            return [
                'status' => 'error',
                'message' => 'Unexpected error occurred while fetching low-hour tasks.',
                'error' => $e->getMessage()
            ];
        }

    }

    private function getSpecialTasks($employees, $departments, $repManagers, $status, $timeline, $customFrom, $customTo, $allowedEmployees, $page = 1, $limit = 100)
    {
        try {

            $offset = ($page - 1) * $limit;

            $query = "SELECT SQL_CALC_FOUND_ROWS t.*, tc.cat_name, ts.brief, cl.description as occasion, 
                        e.emp_id, e.name, e.email, rm.name AS rm_name, e.dept_id
                  FROM task t
                  JOIN task_category tc ON t.cat_id = tc.cat_id
                  LEFT JOIN task_subcategory ts ON t.subcat_id = ts.subcat_id
                  JOIN employee e ON t.emp_id = e.emp_id
                  LEFT JOIN employee rm ON rm.emp_id = e.rm_id
                  JOIN calendar cl ON t.date = cl.date
                  WHERE t.worktype = 'Working' AND e.role != 'hod'";

            $params = [];
            $types = '';

            $this->applyCommonFilters(
                $query,
                $params,
                $types,
                $employees,
                $departments,
                $repManagers,
                $status,
                $timeline,
                $customFrom,
                $customTo,
                $allowedEmployees
            );

            $query .= " ORDER BY t.date DESC, e.emp_id ASC";
            $query .= " LIMIT ? OFFSET ?";

            $types .= "ii";
            $params[] = $limit;
            $params[] = $offset;

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to prepare statement.',
                    'error' => $this->conn->error
                ];
            }

            if (!empty($params)) {
                if (!$stmt->bind_param($types, ...$params)) {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to bind SQL parameters.',
                        'error' => $stmt->error
                    ];
                }
            }

            if (!$stmt->execute()) {
                return [
                    'status' => 'error',
                    'message' => 'Database query failed to execute.',
                    'error' => $stmt->error
                ];
            }

            $result = $stmt->get_result();
            if (!$result) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to fetch query results.',
                    'error' => $stmt->error
                ];
            }

            $tasks = [];
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }

            $countResult = $this->conn->query("SELECT FOUND_ROWS() AS total");
            $total = $countResult ? intval($countResult->fetch_assoc()['total']) : 0;

            return [
                'status' => 'success',
                'type' => 'special',
                'message' => empty($tasks) ? "No special tasks found." : "Special tasks retrieved.",
                'tasks' => $tasks,
                'total' => $total,
                'page' => $page,
                'limit' => $limit
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Unexpected error while fetching special tasks.',
                'error' => $e->getMessage()
            ];
        }
    }


    private function getMissedTasks($employees, $departments, $repManagers, $timeline, $customFrom, $customTo, $allowedEmployees, $page = 1, $limit = 100)
    {
        try {


            $offset = ($page - 1) * $limit;

            $query = "SELECT SQL_CALC_FOUND_ROWS d.date, e.emp_id, e.name, e.email, rm.name AS rm_name, e.dept_id
                  FROM (
                      SELECT CURDATE() - INTERVAL (a.n + b.n * 10) DAY AS date
                      FROM (
                          SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
                          SELECT 4 UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL
                          SELECT 8 UNION ALL SELECT 9
                      ) a
                      CROSS JOIN (
                          SELECT 0 AS n UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL
                          SELECT 4 UNION ALL SELECT 5
                      ) b
                      WHERE (a.n + b.n * 10) > 0
                  ) d
                  CROSS JOIN employee e
                  LEFT JOIN employee rm ON rm.emp_id = e.rm_id
                  LEFT JOIN task t ON t.emp_id = e.emp_id AND DATE(t.date) = d.date
                  WHERE t.task_id IS NULL
                  AND e.role NOT IN ('hod', 'admin')";

            $params = [];
            $types = '';

            // Apply timeline filter on d.date (not t.date which is always NULL in LEFT JOIN)
            if (!empty($timeline)) {
                if ($timeline === 'today') {
                    $query .= " AND d.date = CURDATE()";
                } elseif ($timeline === 'last7') {
                    $query .= " AND d.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                } elseif ($timeline === 'last30') {
                    $query .= " AND d.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                } elseif ($timeline === 'custom' && !empty($customFrom) && !empty($customTo)) {
                    $query .= " AND d.date BETWEEN ? AND ?";
                    $types .= 'ss';
                    $params[] = $customFrom;
                    $params[] = $customTo;
                }
            }

            // Apply employee/dept/manager filters (skip timeline - handled above on d.date)
            $this->applyCommonFilters(
                $query,
                $params,
                $types,
                $employees,
                $departments,
                $repManagers,
                [],
                '', // pass empty - timeline already handled above
                '',
                '',
                $allowedEmployees
            );

            $query .= " ORDER BY d.date DESC, e.emp_id ASC";
            $query .= " LIMIT ? OFFSET ?";

            // Add limit + offset params
            $types .= "ii";
            $params[] = $limit;
            $params[] = $offset;


            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to prepare SQL statement.',
                    'error' => $this->conn->error
                ];
            }

            if (!empty($params)) {
                if (!$stmt->bind_param($types, ...$params)) {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to bind SQL parameters.',
                        'error' => $stmt->error
                    ];
                }
            }

            if (!$stmt->execute()) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to execute SQL query.',
                    'error' => $stmt->error
                ];
            }

            $result = $stmt->get_result();
            if (!$result) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to fetch SQL results.',
                    'error' => $stmt->error
                ];
            }

            $tasks = [];
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }
            $countResult = $this->conn->query("SELECT FOUND_ROWS() AS total");
            $total = $countResult ? intval($countResult->fetch_assoc()['total']) : 0;

            return [
                'status' => 'success',
                'type' => 'missed',
                'message' => empty($tasks) ? "No missed tasks found." : "Missed tasks retrieved.",
                'tasks' => $tasks,
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                // 'query_debug' => $query
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Unexpected error while fetching missed tasks.',
                'error' => $e->getMessage()
            ];
        }
    }


    public function getMissedTasksLastMonth($employees, $departments, $repManagers, $allowedEmployees)
    {
        try {
            $query = "
            SELECT d.date, e.emp_id, e.name, e.email, rm.name AS rm_name, e.dept_id
            FROM (
                SELECT DATE_FORMAT(
                    DATE_ADD(
                        DATE_SUB(LAST_DAY(CURDATE()), INTERVAL DAY(LAST_DAY(CURDATE())) DAY),
                        INTERVAL seq.n DAY
                    ),
                    '%Y-%m-%d'
                ) AS date
                FROM (
                    SELECT @row := @row + 1 AS n
                    FROM 
                        (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
                              UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                        (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
                              UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b,
                        (SELECT @row := 0) r
                ) seq
                WHERE DATE_ADD(
                          DATE_SUB(LAST_DAY(CURDATE()), INTERVAL DAY(LAST_DAY(CURDATE())) DAY),
                          INTERVAL seq.n DAY
                      )
                      BETWEEN DATE_FORMAT(CURDATE() - INTERVAL 1 MONTH, '%Y-%m-01')
                      AND LAST_DAY(CURDATE() - INTERVAL 1 MONTH)
            ) d
            CROSS JOIN employee e
            LEFT JOIN employee rm ON rm.emp_id = e.rm_id
            LEFT JOIN task t ON t.emp_id = e.emp_id AND DATE(t.date) = d.date
            WHERE t.task_id IS NULL
            AND e.role NOT IN ('hod', 'admin')
        ";

            $params = [];
            $types = '';

            // Apply common filters
            $this->applyCommonFilters(
                $query,
                $params,
                $types,
                $employees,
                $departments,
                $repManagers,
                [],
                'custom',
                null,
                null,
                $allowedEmployees
            );

            $query .= " ORDER BY d.date DESC, e.emp_id ASC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to prepare SQL statement.',
                    'error' => $this->conn->error
                ];
            }

            if (!empty($params)) {
                if (!$stmt->bind_param($types, ...$params)) {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to bind SQL parameters.',
                        'error' => $stmt->error
                    ];
                }
            }

            if (!$stmt->execute()) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to execute SQL query.',
                    'error' => $stmt->error
                ];
            }

            $result = $stmt->get_result();
            if (!$result) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to fetch SQL results.',
                    'error' => $stmt->error
                ];
            }

            $tasks = [];
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }

            return [
                'status' => 'success',
                'type' => 'missed_last_month',
                'message' => empty($tasks) ? "No missed tasks found last month." : "Missed tasks from last month retrieved.",
                'tasks' => $tasks
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Unexpected error while fetching last month\'s missed tasks.',
                'error' => $e->getMessage()
            ];
        }
    }


    private function applyCommonFilters(&$query, &$params, &$types, $employees, $departments, $repManagers, $status, $timeline, $customFrom, $customTo, $allowedEmployees)
    {
        if (!empty($allowedEmployees)) {
            $placeholders = implode(',', array_fill(0, count($allowedEmployees), '?'));
            $query .= " AND e.emp_id IN ($placeholders)";
            $types .= str_repeat('i', count($allowedEmployees));
            $params = array_merge($params, $allowedEmployees);
        }

        if (!empty($employees)) {
            $placeholders = implode(',', array_fill(0, count($employees), '?'));
            $query .= " AND e.emp_id IN ($placeholders)";
            $types .= str_repeat('i', count($employees));
            $params = array_merge($params, $employees);
        }

        if (!empty($departments)) {
            $placeholders = implode(',', array_fill(0, count($departments), '?'));
            $query .= " AND e.dept_id IN ($placeholders)";
            $types .= str_repeat('i', count($departments));
            $params = array_merge($params, $departments);
        }

        if (!empty($repManagers)) {
            $placeholders = implode(',', array_fill(0, count($repManagers), '?'));
            $query .= " AND (e.rm_id IN ($placeholders) OR e.emp_id IN ($placeholders))";
            $types .= str_repeat('i', 2 * count($repManagers));
            $params = array_merge($params, $repManagers, $repManagers);
        }

        if (!empty($status)) {
            $placeholders = implode(',', array_fill(0, count($status), '?'));
            $query .= " AND t.status IN ($placeholders)";
            $types .= str_repeat('i', count($status));
            $params = array_merge($params, $status);
        }

        // Handle timeline filters
        if (!empty($timeline)) {
            if ($timeline === 'today') {
                $query .= " AND DATE(t.date) = CURDATE()";
            } elseif ($timeline === 'last7') {
                $query .= " AND t.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            } elseif ($timeline === 'last30') {
                $query .= " AND t.date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            } elseif ($timeline === 'custom' && !empty($customFrom) && !empty($customTo)) {
                $query .= " AND DATE(t.date) BETWEEN ? AND ?";
                $types .= 'ss';
                $params[] = $customFrom;
                $params[] = $customTo;
            }
        }
    }

    public function addTask($task)
    {
        $stmt = $this->conn->prepare('INSERT INTO task (emp_id, worktype, cat_id, subcat_id, client_id, project_id, description, date, duration, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        if (!$stmt) {
            return ['success' => false, 'error' => $this->conn->error];
        }

        $projectId   = isset($task['project_id']) && $task['project_id'] ? (int)$task['project_id'] : null;
        $description = $task['task_description'] ?? $task['description'] ?? null;

        $stmt->bind_param(
            'isiisisssi',
            $task['emp_id'],
            $task['work_type'],
            $task['task_category'],
            $task['task_subcategory'],
            $task['client_id'],
            $projectId,
            $description,
            $task['date'],
            $task['time_taken'],
            $task['status']
        );

        if ($stmt->execute()) {
            return ['success' => true, 'insert_id' => $stmt->insert_id];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }

    public function updateTask($task)
    {
        $stmt = $this->conn->prepare('
        UPDATE task
        SET worktype = ?, cat_id = ?, subcat_id = ?, client_id = ?, project_id = ?, description = ?, date = ?, duration = ?, status = ?
        WHERE task_id = ? AND emp_id = ?
    ');

        if (!$stmt) {
            return ['success' => false, 'error' => $this->conn->error];
        }

        $projectId = isset($task['project_id']) ? (int)$task['project_id'] : null;

        $stmt->bind_param(
            'siisiisssii',
            $task['work_type'],
            $task['task_category'],
            $task['task_subcategory'],
            $task['client_id'],
            $projectId,
            $task['task_description'] ?? $task['description'] ?? null,
            $task['date'],
            $task['time_taken'],
            $task['status'],
            $task['task_id'],
            $task['emp_id'],
        );

        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }

    public function updateTaskStatus($taskId, $status, $comment)
    {
        $stmt = $this->conn->prepare('UPDATE task SET status = ?, comment = ? WHERE task_id = ?');
        $stmt->bind_param('isi', $status, $comment, $taskId);
        return $stmt->execute();
    }

    public function bulkUpdateTaskStatus($tasks)
    {
        $stmt = $this->conn->prepare("UPDATE task SET status = ?, comment = ? WHERE task_id = ?");

        foreach ($tasks as $task) {
            $status = $task['status'];
            $comment = $task['comment'];
            $taskId = $task['task_id'];

            $stmt->bind_param('isi', $status, $comment, $taskId);
            $stmt->execute();
        }

        return true;
    }

    public function deleteTask($emp_id, $task_id)
    {
        $stmt = $this->conn->prepare('DELETE FROM task WHERE task_id = ? AND emp_id = ?');

        if (!$stmt) {
            return ['success' => false, 'error' => $this->conn->error];
        }

        $stmt->bind_param('ii', $task_id, $emp_id);

        if ($stmt->execute()) {
            return ['success' => true];
        } else {
            return ['success' => false, 'error' => $stmt->error];
        }
    }

    public function getWeeklyHoursByEmployee($emp_id)
    {
        $query = "SELECT day, total_hours
        FROM (
            SELECT 
                DAYNAME(date) AS day,
                SEC_TO_TIME(SUM(TIME_TO_SEC(duration))) AS total_hours
            FROM task 
            WHERE emp_id = ?
            AND WEEK(date, 1) = WEEK(CURDATE(), 1)
            AND YEAR(date) = YEAR(CURDATE())
            GROUP BY DAYNAME(date)
        ) AS daily_summary
        ORDER BY CASE day
            WHEN 'Monday' THEN 1
            WHEN 'Tuesday' THEN 2
            WHEN 'Wednesday' THEN 3
            WHEN 'Thursday' THEN 4
            WHEN 'Friday' THEN 5
            WHEN 'Saturday' THEN 6
            -- WHEN 'Sunday' THEN 7
        END";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            file_put_contents('log-error.txt', 'SQL Error: ' . $this->conn->error);
            return [];
        }

        $stmt->bind_param('i', $emp_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $weeklyHours = [
            'Monday' => 0,
            'Tuesday' => 0,
            'Wednesday' => 0,
            'Thursday' => 0,
            'Friday' => 0,
            'Saturday' => 0
            // 'Sunday' => 0
        ];

        while ($row = $result->fetch_assoc()) {
            $timeParts = explode(':', $row['total_hours']);
            $hoursDecimal = (int) $timeParts[0] + ((int) $timeParts[1] / 60);
            $weeklyHours[$row['day']] = round($hoursDecimal, 2);
        }

        return $weeklyHours;
    }

    // Average Week hours of Employees
// Average Week hours of Employees
    public function getAverageWeeklyHoursByEmployees(array $emp_ids)
    {
        // Validate input
        if (empty($emp_ids)) {
            throw new Exception("Employee list cannot be empty.");
        }

        // Ensure all values are INT
        foreach ($emp_ids as $id) {
            if (!is_numeric($id)) {
                throw new Exception("Invalid employee ID: " . json_encode($id));
            }
        }

        // Construct placeholders (?, ?, ?, ...)
        $placeholders = implode(',', array_fill(0, count($emp_ids), '?'));

        $query = "
        SELECT day, SEC_TO_TIME(SUM(TIME_TO_SEC(duration))) AS total_hours
        FROM (
            SELECT 
                DAYNAME(date) AS day,
                duration
            FROM task 
            WHERE emp_id IN ($placeholders)
              AND WEEK(date, 1) = WEEK(CURDATE(), 1)
              AND YEAR(date) = YEAR(CURDATE())
        ) AS subquery
        GROUP BY day
        ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday')
    ";

        // Prepare statement
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("SQL Prepare failed: " . $this->conn->error);
        }

        // Bind parameters
        $types = str_repeat('i', count($emp_ids));
        if (!$stmt->bind_param($types, ...$emp_ids)) {
            throw new Exception("Parameter binding failed: " . $stmt->error);
        }

        // Execute
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        // Initialize days output
        $weeklyAvgHours = [
            'Monday' => 0,
            'Tuesday' => 0,
            'Wednesday' => 0,
            'Thursday' => 0,
            'Friday' => 0,
            'Saturday' => 0
        ];

        // Process output
        while ($row = $result->fetch_assoc()) {

            if (!$row['total_hours']) {
                continue;
            }

            // Convert HH:MM:SS → decimal hours
            $timeParts = explode(':', $row['total_hours']);

            if (count($timeParts) < 2) {
                throw new Exception("Invalid time format returned by SQL: " . $row['total_hours']);
            }

            $hoursDecimal = (int) $timeParts[0] + ((int) $timeParts[1] / 60);
            $avgHours = round($hoursDecimal / count($emp_ids), 2);

            $weeklyAvgHours[$row['day']] = $avgHours;
        }

        return $weeklyAvgHours;
    }


    public function getWorkingDaysByEmployee($emp_id)
    {
        $query = "SELECT count(distinct date) AS days_worked FROM task WHERE emp_id = ? 
                AND worktype = 'Working'
                    -- AND WEEK(date, 1) = WEEK(CURDATE(), 1)
                    -- AND YEAR(date) = YEAR(CURDATE())
                    ";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $emp_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $daysWorked = (int) $result['days_worked'];
        $totalWorkingDays = 22;
        $remainingDays = $totalWorkingDays - $daysWorked;

        return [
            'days_worked' => $daysWorked,
            'total_working_days' => $totalWorkingDays
        ];

    }

    public function getWorkingDaysByEmployees($empIds)
    {
        // Prepare placeholders for IN clause
        $placeholders = implode(',', array_fill(0, count($empIds), '?'));
        $types = str_repeat('i', count($empIds));

        $query = "SELECT COUNT(DISTINCT date) AS days_worked FROM task
              WHERE emp_id IN ($placeholders)
              AND worktype = 'Working'
              AND MONTH(date) = MONTH(CURDATE())
              AND YEAR(date) = YEAR(CURDATE())";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$empIds);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        $daysWorked = (int) $result['days_worked'];
        $totalEmployees = count($empIds);
        // $totalWorkingDays = count($empIds) * 22;

        return [
            'days_worked' => $daysWorked,
            // 'total_working_days' => $totalWorkingDays,
            'total_employees' => $totalEmployees
        ];
    }

    public function getLeavesByEmployeeId($emp_id)
    {
        $query = "SELECT COUNT(distinct date) AS leave_count FROM task WHERE emp_id = ? AND worktype = 'Leave'";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $emp_id);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        return [
            'emp_id' => $emp_id,
            'total_allocated_leaves' => 12,
            'leaves' => $row['leave_count'] ?? 0
        ];
    }

    // public function getLeavesByEmployeeIds(array $emp_ids)
    // {
    //     if (empty($emp_ids)) {
    //         return ['error' => 'No employees selected'];
    //     }

    //     // Generate placeholders and types
    //     $placeholders = implode(',', array_fill(0, count($emp_ids), '?'));
    //     $types = str_repeat('i', count($emp_ids)); // For emp_id params

    //     // Add current month and year filtering
    //     // $query = "SELECT emp_id, COUNT(DISTINCT date) AS leave_count 
    //     //       FROM task 
    //     //       WHERE emp_id IN ($placeholders)
    //     //         AND worktype = 'Leave'
    //     //         AND MONTH(date) = MONTH(CURDATE())
    //     //         AND YEAR(date) = YEAR(CURDATE())
    //     //       GROUP BY emp_id";

    //     $query = "SELECT emp_id, 
    //             SUM(
    //                 CASE 
    //                     WHEN worktype = 'Leave' THEN 1
    //                     WHEN worktype = 'Half-Day Leave' THEN 0.5
    //                     ELSE 0
    //                 END
    //             ) AS leave_count
    //                 FROM task
    //                 WHERE emp_id IN ($placeholders)
    //                 AND (worktype = 'Leave' OR worktype = 'Half-Day Leave')
    //                 AND MONTH(date) = MONTH(CURDATE())
    //                 AND YEAR(date) = YEAR(CURDATE())
    //                 GROUP BY emp_id;
    //                 ";



    //     $stmt = $this->conn->prepare($query);

    //     if (!$stmt) {
    //         return ['error' => 'Query prepare failed'];
    //     }

    //     $stmt->bind_param($types, ...$emp_ids);
    //     $stmt->execute();

    //     $result = $stmt->get_result();

    //     $totalLeaves = 0;
    //     while ($row = $result->fetch_assoc()) {
    //         // $totalLeaves += (int) $row['leave_count'];
    //         $totalLeaves += $row['leave_count'];
    //     }

    //     return [
    //         'total_leaves_taken' => $totalLeaves,
    //         'total_employees' => count($emp_ids),
    //         'total_possible_leaves' => count($emp_ids) * 22
    //     ];
    // }

    public function getLeavesByEmployeeIds(array $emp_ids)
    {
        // Validate input
        if (empty($emp_ids)) {
            throw new Exception("No employees selected.");
        }

        foreach ($emp_ids as $id) {
            if (!is_numeric($id)) {
                throw new Exception("Invalid employee ID: " . json_encode($id));
            }
        }

        // Create placeholders (?, ?, ?, ...)
        $placeholders = implode(',', array_fill(0, count($emp_ids), '?'));
        $types = str_repeat('i', count($emp_ids));

        $query = "
        SELECT emp_id, 
            SUM(
                CASE 
                    WHEN worktype = 'Leave' THEN 1
                    WHEN worktype = 'Half-Day Leave' THEN 0.5
                    ELSE 0
                END
            ) AS leave_count
        FROM task
        WHERE emp_id IN ($placeholders)
          AND (worktype = 'Leave' OR worktype = 'Half-Day Leave')
          AND MONTH(date) = MONTH(CURDATE())
          AND YEAR(date) = YEAR(CURDATE())
        GROUP BY emp_id
    ";

        // Prepare statement
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("SQL Prepare Failed: " . $this->conn->error);
        }

        // Bind parameters
        if (!$stmt->bind_param($types, ...$emp_ids)) {
            throw new Exception("Parameter binding failed: " . $stmt->error);
        }

        // Execute
        if (!$stmt->execute()) {
            throw new Exception("SQL Execute Failed: " . $stmt->error);
        }

        $result = $stmt->get_result();

        $totalLeaves = 0;

        while ($row = $result->fetch_assoc()) {
            if (!isset($row['leave_count'])) {
                continue;
            }

            $totalLeaves += floatval($row['leave_count']);
        }

        return [
            'total_leaves_taken' => $totalLeaves,
            'total_employees' => count($emp_ids),
            'total_possible_leaves' => count($emp_ids) * 22
        ];
    }


    public function getCategoriesByDept($dept_id, $subdept_id = 0)
    {
        if ($subdept_id == null || $subdept_id == 0) {
            $stmt = $this->conn->prepare("SELECT * FROM task_category WHERE dept_id = ? and is_active = 1");
            $stmt->bind_param("i", $dept_id);

        } else {
            $stmt = $this->conn->prepare("SELECT * FROM task_category WHERE dept_id = ? AND subdept_id = ? and is_active = 1");
            $stmt->bind_param("ii", $dept_id, $subdept_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $taskCategories = [];
        while ($taskCategory = $result->fetch_assoc()) {
            $taskCategories[] = $taskCategory;
        }

        return $taskCategories;

    }

    public function getTaskBriefsByCategories($taskCategories)
    {
        if (empty($taskCategories)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($taskCategories), '?'));


        // Prepare the query
        $stmt = $this->conn->prepare("SELECT * FROM task_subcategory WHERE cat_id IN ($placeholders)");

        if (!$stmt) {
            return [];
        }

        // Bind parameters dynamically
        $types = str_repeat('i', count($taskCategories));
        $stmt->bind_param($types, ...$taskCategories);

        $stmt->execute();
        $result = $stmt->get_result();

        $briefsByCategory = [];
        while ($row = $result->fetch_assoc()) {
            $catId = $row['cat_id'];
            if (!isset($briefsByCategory[$catId])) {
                $briefsByCategory[$catId] = [];
            }
            $briefsByCategory[$catId][] = [
                'brief_id' => $row['subcat_id'],
                'brief_name' => $row['brief'],
            ];
        }

        return $briefsByCategory;
    }

    public function getMissedTasksForDateRange($employees, $departments, $repManagers, $allowedEmployees, $startDate, $endDate)
    {
        try {
            // Validate date format
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                return [
                    'status' => 'error',
                    'message' => 'Invalid date format. Use YYYY-MM-DD.'
                ];
            }

            $query = "
            SELECT d.date, e.emp_id, e.name, e.email, dept.dept_name AS department, rm.name AS rm_name, e.dept_id
            FROM (
                SELECT DATE_FORMAT(
                    DATE_ADD(?, INTERVAL seq.n DAY),
                    '%Y-%m-%d'
                ) AS date
                FROM (
                    SELECT @row := @row + 1 AS n
                    FROM 
                        (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
                              UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) a,
                        (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
                              UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) b,
                        (SELECT 0 UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4 
                              UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8 UNION ALL SELECT 9) c,
                        (SELECT @row := -1) r
                ) seq
                WHERE DATE_ADD(?, INTERVAL seq.n DAY) <= ?
            ) d
            CROSS JOIN employee e
            LEFT JOIN department dept ON e.dept_id = dept.dept_id
            LEFT JOIN employee rm ON rm.emp_id = e.rm_id
            LEFT JOIN task t ON t.emp_id = e.emp_id AND DATE(t.date) = d.date
            LEFT JOIN calendar cal ON cal.date = d.date AND (cal.type = 'Public Holiday' OR cal.type = 'Week Off')
            WHERE t.task_id IS NULL
            AND cal.id IS NULL
            AND e.role NOT IN ('hod', 'admin')
            AND (e.status IS NULL OR e.status = 1)
        ";

            $params = [$startDate, $startDate, $endDate];
            $types = 'sss';

            // Apply common filters
            if (!empty($allowedEmployees)) {
                $placeholders = implode(',', array_fill(0, count($allowedEmployees), '?'));
                $query .= " AND e.emp_id IN ($placeholders)";
                $types .= str_repeat('i', count($allowedEmployees));
                $params = array_merge($params, $allowedEmployees);
            }

            if (!empty($employees)) {
                $placeholders = implode(',', array_fill(0, count($employees), '?'));
                $query .= " AND e.emp_id IN ($placeholders)";
                $types .= str_repeat('i', count($employees));
                $params = array_merge($params, $employees);
            }

            if (!empty($departments)) {
                $placeholders = implode(',', array_fill(0, count($departments), '?'));
                $query .= " AND e.dept_id IN ($placeholders)";
                $types .= str_repeat('i', count($departments));
                $params = array_merge($params, $departments);
            }

            if (!empty($repManagers)) {
                $placeholders = implode(',', array_fill(0, count($repManagers), '?'));
                $query .= " AND (e.rm_id IN ($placeholders) OR e.emp_id IN ($placeholders))";
                $types .= str_repeat('i', 2 * count($repManagers));
                $params = array_merge($params, $repManagers, $repManagers);
            }

            $query .= " ORDER BY d.date DESC, e.emp_id ASC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to prepare SQL statement.',
                    'error' => $this->conn->error
                ];
            }

            if (!empty($params)) {
                if (!$stmt->bind_param($types, ...$params)) {
                    return [
                        'status' => 'error',
                        'message' => 'Failed to bind SQL parameters.',
                        'error' => $stmt->error
                    ];
                }
            }

            if (!$stmt->execute()) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to execute SQL query.',
                    'error' => $stmt->error
                ];
            }

            $result = $stmt->get_result();
            if (!$result) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to fetch SQL results.',
                    'error' => $stmt->error
                ];
            }

            $tasks = [];
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }

            return [
                'status' => 'success',
                'type' => 'missed_date_range',
                'message' => empty($tasks) ? "No missed tasks found for the specified date range." : "Missed tasks retrieved.",
                'tasks' => $tasks
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Unexpected error while fetching missed tasks.',
                'error' => $e->getMessage()
            ];
        }
    }



    /**
     * Get all submitted tasks for a specific date range
     * Returns detailed task information including employee details
     */
    public function getSubmittedTasksForDateRange($employees, $departments, $repManagers, $allowedEmployees, $startDate, $endDate)
    {
        try {
            $query = "SELECT 
                        t.date,
                        t.emp_id,
                        t.client_id,
                        t.duration,
                        e.name,
                        e.email,
                        d.dept_name,
                        rm.name AS rm_name,
                        t.description AS task_description,
                        TIME_FORMAT(t.duration, '%H:%i') AS hours,
                        tc.cat_name AS task_category,
                        COALESCE(cl.client_name, 'Others') AS client_name,
                        t.status
                    FROM task t
                    JOIN employee e ON t.emp_id = e.emp_id
                    JOIN department d ON e.dept_id = d.dept_id
                    LEFT JOIN employee rm ON e.rm_id = rm.emp_id
                    LEFT JOIN task_category tc ON t.cat_id = tc.cat_id
                    LEFT JOIN client cl ON t.client_id = cl.client_id
                    WHERE e.status = 1
                      AND t.date >= ?
                      AND t.date <= ?
                      AND t.cat_id > 0";

            $params = [$startDate, $endDate];
            $types = 'ss';

            // Apply filters
            if (!empty($employees)) {
                $placeholders = implode(',', array_fill(0, count($employees), '?'));
                $query .= " AND e.emp_id IN ($placeholders)";
                $types .= str_repeat('i', count($employees));
                $params = array_merge($params, $employees);
            }

            if (!empty($departments)) {
                $placeholders = implode(',', array_fill(0, count($departments), '?'));
                $query .= " AND d.dept_id IN ($placeholders)";
                $types .= str_repeat('i', count($departments));
                $params = array_merge($params, $departments);
            }

            if (!empty($repManagers)) {
                $placeholders = implode(',', array_fill(0, count($repManagers), '?'));
                $query .= " AND e.rm_id IN ($placeholders)";
                $types .= str_repeat('i', count($repManagers));
                $params = array_merge($params, $repManagers);
            }

            if (!empty($allowedEmployees)) {
                $placeholders = implode(',', array_fill(0, count($allowedEmployees), '?'));
                $query .= " AND e.emp_id IN ($placeholders)";
                $types .= str_repeat('i', count($allowedEmployees));
                $params = array_merge($params, $allowedEmployees);
            }

            $query .= " ORDER BY t.date DESC, e.emp_id ASC, t.task_id ASC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return [
                    'status' => 'error',
                    'message' => 'Failed to prepare statement.',
                    'error' => $this->conn->error
                ];
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $tasks = [];
            while ($row = $result->fetch_assoc()) {
                $tasks[] = $row;
            }

            return [
                'status' => 'success',
                'message' => empty($tasks) ? 'No submitted tasks found for the specified date range.' : 'Submitted tasks retrieved.',
                'tasks' => $tasks
            ];

        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Unexpected error while fetching submitted tasks.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get all-time cumulative hours per employee broken down by client/project.
     * Applies the same department / employee / repManager / allowedEmployees filters.
     * Rows with no client assigned (client_id = 0 or NULL) are grouped under "No Project Assigned".
     */
    public function getCumulativeHoursByClientEmployee($employees, $departments, $repManagers, $allowedEmployees)
    {
        try {
            $query = "SELECT 
                        e.emp_id,
                        e.name AS emp_name,
                        e.email,
                        d.dept_name,
                        rm.name AS rm_name,
                        COALESCE(cl.client_id, 0) AS client_id,
                        COALESCE(cl.client_name, 'Others') AS client_name,
                        COALESCE(SEC_TO_TIME(SUM(TIME_TO_SEC(t.duration))), '00:00:00') AS total_hours_formatted,
                        ROUND(COALESCE(SUM(TIME_TO_SEC(t.duration)), 0) / 3600.0, 2) AS total_hours_decimal,
                        COUNT(t.task_id) AS task_count
                    FROM employee e
                    JOIN department d ON e.dept_id = d.dept_id
                    LEFT JOIN employee rm ON e.rm_id = rm.emp_id
                    LEFT JOIN task t ON e.emp_id = t.emp_id AND t.cat_id > 0
                    LEFT JOIN client cl ON t.client_id = cl.client_id
                    WHERE e.status = 1";

            $params = [];
            $types = '';

            $this->applyCommonFilters($query, $params, $types, $employees, $departments, $repManagers, [], '', '', '', $allowedEmployees);

            $query .= " GROUP BY e.emp_id, cl.client_id
                        ORDER BY e.name ASC, (client_name = 'Others') ASC, total_hours_decimal DESC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                return ['status' => 'error', 'message' => $this->conn->error];
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            return ['status' => 'success', 'data' => $data];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get project utilization (Total hours per project)
     */
    public function getProjectUtilization($employees, $departments, $repManagers, $allowedEmployees, $startDate, $endDate)
    {
        try {
            $query = "SELECT 
                        cl.client_id,
                        COALESCE(cl.client_name, 'Others') AS client_name,
                        ROUND(COALESCE(SUM(TIME_TO_SEC(t.duration)), 0) / 3600.0, 2) AS total_hours
                    FROM client cl
                    LEFT JOIN task t ON cl.client_id = t.client_id 
                      AND t.date >= ?
                      AND t.date <= ?
                      AND t.cat_id > 0
                    LEFT JOIN employee e ON t.emp_id = e.emp_id
                    LEFT JOIN department d ON e.dept_id = d.dept_id
                    WHERE 1=1";

            $params = [$startDate, $endDate];
            $types = 'ss';

            $this->applyCommonFilters($query, $params, $types, $employees, $departments, $repManagers, [], '', '', '', $allowedEmployees);

            $query .= " GROUP BY cl.client_id
                        ORDER BY (client_name = 'Others') ASC, total_hours DESC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) return ['status' => 'error', 'message' => $this->conn->error];

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            return ['status' => 'success', 'data' => $data];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get resource project matrix (Matrix of employees and projects with hours)
     */
    public function getResourceProjectMatrix($employees, $departments, $repManagers, $allowedEmployees, $startDate, $endDate)
    {
        try {
            $query = "SELECT 
                        e.emp_id,
                        e.name AS emp_name,
                        COALESCE(cl.client_id, 0) AS client_id,
                        COALESCE(cl.client_name, 'Others') AS client_name,
                        ROUND(COALESCE(SUM(TIME_TO_SEC(t.duration)), 0) / 3600.0, 2) AS total_hours
                    FROM employee e
                    JOIN department d ON e.dept_id = d.dept_id
                    LEFT JOIN task t ON e.emp_id = t.emp_id 
                      AND t.date >= ?
                      AND t.date <= ?
                      AND t.cat_id > 0
                    LEFT JOIN client cl ON t.client_id = cl.client_id
                    WHERE e.status = 1";

            $params = [$startDate, $endDate];
            $types = 'ss';

            $this->applyCommonFilters($query, $params, $types, $employees, $departments, $repManagers, [], '', '', '', $allowedEmployees);

            $query .= " GROUP BY e.emp_id, cl.client_id
                        ORDER BY e.name ASC, client_name ASC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) return ['status' => 'error', 'message' => $this->conn->error];

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $matrix = [];
            while ($row = $result->fetch_assoc()) {
                $matrix[] = $row;
            }

            return ['status' => 'success', 'data' => $matrix];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Get daily hours per employee per date within a date range.
     * Returns rows: emp_id, emp_name, date, total_hours
     */
    public function getDailyHours($employees, $departments, $repManagers, $allowedEmployees, $startDate, $endDate)
    {
        try {
            $query = "SELECT
                        e.emp_id,
                        e.name AS emp_name,
                        t.date,
                        ROUND(COALESCE(SUM(TIME_TO_SEC(t.duration)), 0) / 3600.0, 2) AS total_hours
                    FROM employee e
                    JOIN department d ON e.dept_id = d.dept_id
                    LEFT JOIN task t ON e.emp_id = t.emp_id 
                      AND t.date >= ?
                      AND t.date <= ?
                      AND t.cat_id > 0
                    WHERE e.status = 1";

            $params = [$startDate, $endDate];
            $types = 'ss';

            $this->applyCommonFilters($query, $params, $types, $employees, $departments, $repManagers, [], '', '', '', $allowedEmployees);

            $query .= " GROUP BY e.emp_id, t.datex
                        ORDER BY e.name ASC, t.date ASC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) return ['status' => 'error', 'message' => $this->conn->error];

            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[] = $row;
            }

            return ['status' => 'success', 'data' => $data];
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }
}

