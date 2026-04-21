<?php

/**
 * Project.php
 * OOP data-access class for Project Time Tracking & Classification module.
 * Follows the same constructor/dependency injection pattern as Employee.php and Client.php.
 */
class Project
{
    private $conn;

    // Performance classification thresholds (can be tuned here)
    const FIXED_HIGHLY_EFFICIENT_MAX = 85;   // ≤ 85%  → Highly Efficient
    const FIXED_EFFECTIVE_MAX        = 100;  // ≤ 100% → Effective
    const TM_HIGHLY_EFFICIENT_MAX    = 90;   // ≤ 90%  → Highly Efficient
    const TM_EFFECTIVE_MAX           = 100;  // ≤ 100% → Effective

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    // =========================================================
    // 1. createProject($data)
    //    INSERT new project. Locks original_estimated_hours at creation.
    // =========================================================
    public function createProject(array $data): array
    {
        $required = ['client_id', 'project_name', 'project_type'];
        foreach ($required as $field) {
            if (empty($data[$field])) {
                return ['success' => false, 'message' => "Field '$field' is required."];
            }
        }

        $type = $data['project_type'];
        if ($type === 'fixed_cost' && empty($data['total_estimated_hours'])) {
            return ['success' => false, 'message' => 'Total Estimated Hours is required for Fixed Cost projects.'];
        }
        if (in_array($type, ['time_material', 'staff_augmentation']) && empty($data['monthly_allocated_hours'])) {
            return ['success' => false, 'message' => 'Monthly Allocated Hours is required for T&M / Staff Augmentation projects.'];
        }

        // original_estimated_hours = copy of total_estimated_hours — write-once
        $originalHours = $data['total_estimated_hours'] ?? null;

        $stmt = $this->conn->prepare("
            INSERT INTO project
              (client_id, project_name, project_type, contact_email,
               total_estimated_hours, original_estimated_hours,
               project_duration_days, project_start_date, project_end_date,
               monthly_allocated_hours, created_by, managed_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if (!$stmt) {
            return ['success' => false, 'message' => 'DB prepare error: ' . $this->conn->error];
        }

        $stmt->bind_param(
            'ssssddissdii',
            $data['client_id'],
            $data['project_name'],
            $data['project_type'],
            $data['contact_email'],
            $data['total_estimated_hours'],
            $originalHours,
            $data['project_duration_days'],
            $data['project_start_date'],
            $data['project_end_date'],
            $data['monthly_allocated_hours'],
            $data['created_by'],
            $data['managed_by']
        );

        if (!$stmt->execute()) {
            if ($this->conn->errno === 1062) {
                return ['success' => false, 'message' => 'A project with this name already exists for this client.'];
            }
            return ['success' => false, 'message' => 'Failed to create project: ' . $stmt->error];
        }

        $newId = $this->conn->insert_id;
        $stmt->close();
        return ['success' => true, 'project_id' => $newId];
    }

    // =========================================================
    // 2. updateProject($id, $data)
    //    Dynamic UPDATE. original_estimated_hours is NOT in the allowlist.
    // =========================================================
    public function updateProject(int $id, array $data): array
    {
        $allowlist = [
            'project_name', 'project_type', 'contact_email',
            'total_estimated_hours', 'project_duration_days',
            'project_start_date', 'project_end_date',
            'monthly_allocated_hours', 'managed_by', 'is_active'
        ];

        $setClauses = [];
        $params     = [];
        $types      = '';

        foreach ($allowlist as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[] = "`$field` = ?";
                $params[]     = $data[$field];
                $types       .= 's';
            }
        }

        if (empty($setClauses)) {
            return ['success' => false, 'message' => 'No valid fields to update.'];
        }

        $types   .= 'i';
        $params[] = $id;

        $sql  = 'UPDATE project SET ' . implode(', ', $setClauses) . ' WHERE project_id = ?';
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['success' => false, 'message' => 'DB prepare error: ' . $this->conn->error];
        }

        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();

        return $result
            ? ['success' => true]
            : ['success' => false, 'message' => 'Update failed: ' . $stmt->error];
    }

    // =========================================================
    // 3. getProjectById($id)
    //    Fetch single project with client name and manager name.
    // =========================================================
    public function getProjectById(int $id): ?array
    {
        $stmt = $this->conn->prepare("
            SELECT p.*,
                   c.client_name,
                   e_cb.name AS created_by_name,
                   e_mb.name AS managed_by_name
            FROM project p
            LEFT JOIN client     c    ON p.client_id  = c.client_id
            LEFT JOIN employee   e_cb ON p.created_by = e_cb.emp_id
            LEFT JOIN employee   e_mb ON p.managed_by = e_mb.emp_id
            WHERE p.project_id = ?
        ");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();
        $stmt->close();
        return $row ?: null;
    }

    // =========================================================
    // 4. getAllProjects($filters)
    //    Paginated list with optional filters: client_id, type, is_active, managed_by, created_by
    // =========================================================
    public function getAllProjects(array $filters = [], int $page = 1, int $limit = 50): array
    {
        $offset = ($page - 1) * $limit;
        $where  = [];
        $params = [];
        $types  = '';

        if (!empty($filters['client_id'])) {
            $where[]  = 'p.client_id = ?';
            $params[] = $filters['client_id'];
            $types   .= 's';
        }
        if (!empty($filters['project_type'])) {
            $where[]  = 'p.project_type = ?';
            $params[] = $filters['project_type'];
            $types   .= 's';
        }
        if (isset($filters['is_active'])) {
            $where[]  = 'p.is_active = ?';
            $params[] = (int) $filters['is_active'];
            $types   .= 'i';
        }
        if (!empty($filters['managed_by'])) {
            $where[]  = 'p.managed_by = ?';
            $params[] = (int) $filters['managed_by'];
            $types   .= 'i';
        }
        if (!empty($filters['created_by'])) {
            $where[]  = 'p.created_by = ?';
            $params[] = (int) $filters['created_by'];
            $types   .= 'i';
        }

        $whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT SQL_CALC_FOUND_ROWS
                    p.*,
                    c.client_name,
                    e_mb.name AS managed_by_name,
                    e_cb.name AS created_by_name
                FROM project p
                LEFT JOIN client   c    ON p.client_id  = c.client_id
                LEFT JOIN employee e_mb ON p.managed_by = e_mb.emp_id
                LEFT JOIN employee e_cb ON p.created_by = e_cb.emp_id
                $whereClause
                ORDER BY p.created_at DESC
                LIMIT ? OFFSET ?";

        $types   .= 'ii';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            return ['projects' => [], 'total' => 0];
        }

        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result   = $stmt->get_result();
        $projects = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $totalResult = $this->conn->query('SELECT FOUND_ROWS() AS total');
        $total       = $totalResult ? (int) $totalResult->fetch_assoc()['total'] : 0;

        return ['projects' => $projects, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    // =========================================================
    // 5. getProjectsByClientId($clientId)
    // =========================================================
    public function getProjectsByClientId(string $clientId): array
    {
        $stmt = $this->conn->prepare("
            SELECT p.*, e.name AS managed_by_name
            FROM project p
            LEFT JOIN employee e ON p.managed_by = e.emp_id
            WHERE p.client_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param('s', $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // =========================================================
    // 6. getProjectsByManager($empId) — PM's projects
    // =========================================================
    public function getProjectsByManager(int $empId): array
    {
        $stmt = $this->conn->prepare("
            SELECT p.*, c.client_name
            FROM project p
            LEFT JOIN client c ON p.client_id = c.client_id
            WHERE p.managed_by = ? AND p.is_active = 1
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param('i', $empId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // =========================================================
    // 7. getProjectsByCreator($empId) — Sales Manager's projects
    // =========================================================
    public function getProjectsByCreator(int $empId): array
    {
        $stmt = $this->conn->prepare("
            SELECT p.*, c.client_name, e.name AS managed_by_name
            FROM project p
            LEFT JOIN client   c ON p.client_id  = c.client_id
            LEFT JOIN employee e ON p.managed_by = e.emp_id
            WHERE p.created_by = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->bind_param('i', $empId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // =========================================================
    // 8. toggleProjectStatus($id, $status)
    // =========================================================
    public function toggleProjectStatus(int $id, int $status): bool
    {
        $stmt = $this->conn->prepare("UPDATE project SET is_active = ? WHERE project_id = ?");
        $stmt->bind_param('ii', $status, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // =========================================================
    // 9. addProjectMember($projectId, $empId, $allocatedHours, $role)
    // =========================================================
    public function addProjectMember(int $projectId, int $empId, ?float $allocatedHours, ?string $role): array
    {
        // Check employee exists + is active
        $chk = $this->conn->prepare("SELECT emp_id FROM employee WHERE emp_id = ? AND status = 1");
        $chk->bind_param('i', $empId);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows === 0) {
            $chk->close();
            return ['success' => false, 'message' => 'Employee not found or inactive.'];
        }
        $chk->close();

        // Check for over-budget (Fixed Cost projects only) — Hard Block
        $project = $this->getProjectById($projectId);
        if ($project && $project['project_type'] === 'fixed_cost' && $project['total_estimated_hours']) {
            $currentAllocated = $this->getTotalAllocatedHours($projectId);
            if (($currentAllocated + $allocatedHours) > (float)$project['total_estimated_hours']) {
                return [
                    'success' => false, 
                    'message' => "Cannot assign: Total allocation would reach " . ($currentAllocated + $allocatedHours) . " hrs, exceeding the project budget of " . $project['total_estimated_hours'] . " hrs."
                ];
            }
        }

        $today = date('Y-m-d');
        $stmt = $this->conn->prepare("
            INSERT INTO project_member (project_id, emp_id, allocated_hours, role_in_project, assigned_at)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param('iidss', $projectId, $empId, $allocatedHours, $role, $today);

        if (!$stmt->execute()) {
            if ($this->conn->errno === 1062) {
                return ['success' => false, 'message' => 'This employee is already a member of this project.'];
            }
            return ['success' => false, 'message' => 'Failed to add member: ' . $stmt->error];
        }
        $stmt->close();
        return ['success' => true];
    }

    // =========================================================
    // 10. updateProjectMember($id, $data)
    // =========================================================
    public function updateProjectMember(int $id, array $data): array
    {
        // If updating allocated_hours, check against project budget (Fixed Cost only)
        if (isset($data['allocated_hours'])) {
            $stmtM = $this->conn->prepare("SELECT project_id, allocated_hours FROM project_member WHERE id = ?");
            $stmtM->bind_param('i', $id);
            $stmtM->execute();
            $member = $stmtM->get_result()->fetch_assoc();
            $stmtM->close();

            if ($member) {
                $pid = (int)$member['project_id'];
                $project = $this->getProjectById($pid);
                if ($project && $project['project_type'] === 'fixed_cost' && $project['total_estimated_hours']) {
                    $otherAllocated = $this->getTotalAllocatedHours($pid) - (float)$member['allocated_hours'];
                    $newTotal = $otherAllocated + (float)$data['allocated_hours'];
                    if ($newTotal > (float)$project['total_estimated_hours']) {
                        return [
                            'success' => false,
                            'message' => "Cannot update: Total allocation would reach " . $newTotal . " hrs, exceeding budget of " . $project['total_estimated_hours'] . " hrs."
                        ];
                    }
                }
            }
        }

        $allowlist = ['allocated_hours', 'role_in_project', 'is_active'];
        $setClauses = [];
        $params     = [];
        $types      = '';

        foreach ($allowlist as $field) {
            if (array_key_exists($field, $data)) {
                $setClauses[] = "`$field` = ?";
                $params[]     = $data[$field];
                $types       .= 's';
            }
        }

        if (empty($setClauses)) {
            return ['success' => false, 'message' => 'No valid fields to update.'];
        }

        $types   .= 'i';
        $params[] = $id;

        $stmt = $this->conn->prepare('UPDATE project_member SET ' . implode(', ', $setClauses) . ' WHERE id = ?');
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        return $result ? ['success' => true] : ['success' => false, 'message' => 'Update failed.'];
    }

    // =========================================================
    // 11. removeProjectMember($projectId, $empId)
    // =========================================================
    public function removeProjectMember(int $projectId, int $empId): bool
    {
        $stmt = $this->conn->prepare("DELETE FROM project_member WHERE project_id = ? AND emp_id = ?");
        $stmt->bind_param('ii', $projectId, $empId);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    // =========================================================
    // 12. getProjectMembers($projectId)
    //     Returns members with their allocated + actual hours.
    // =========================================================
    public function getProjectMembers(int $projectId): array
    {
        $stmt = $this->conn->prepare("
            SELECT
                pm.id AS member_row_id,
                pm.emp_id,
                pm.allocated_hours,
                pm.role_in_project,
                pm.assigned_at,
                pm.is_active,
                e.name  AS emp_name,
                e.email AS emp_email,
                e.role  AS emp_role,
                d.dept_name,
                ROUND(
                    COALESCE(
                        (SELECT SUM(TIME_TO_SEC(t.duration)) FROM task t
                         WHERE t.emp_id = pm.emp_id AND t.project_id = pm.project_id),
                        0
                    ) / 3600, 2
                ) AS actual_hours
            FROM project_member pm
            JOIN employee   e ON pm.emp_id  = e.emp_id
            LEFT JOIN department d ON e.dept_id = d.dept_id
            WHERE pm.project_id = ?
            ORDER BY pm.assigned_at ASC
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Add utilization % and status label to each row
        foreach ($rows as &$member) {
            $alloc  = floatval($member['allocated_hours']);
            $actual = floatval($member['actual_hours']);
            $pct    = $alloc > 0 ? round(($actual / $alloc) * 100, 1) : 0;

            $member['utilization_pct'] = $pct;
            $member['status_label']    = $this->getMemberStatus($pct);
        }

        return $rows;
    }

    // =========================================================
    // 13. getActualHoursByProject($projectId)
    //     Total actual hours for a project (all time).
    // =========================================================
    public function getActualHoursByProject(int $projectId): float
    {
        $stmt = $this->conn->prepare("
            SELECT ROUND(COALESCE(SUM(TIME_TO_SEC(duration)), 0) / 3600, 2) AS total_hours
            FROM task
            WHERE project_id = ?
        ");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $total = 0;
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        return (float) $total;
    }

    // =========================================================
    // 14. getActualHoursByMember($projectId, $empId)
    //     Actual hours for a specific member in a project.
    // =========================================================
    public function getActualHoursByMember(int $projectId, int $empId): float
    {
        $stmt = $this->conn->prepare("
            SELECT ROUND(COALESCE(SUM(TIME_TO_SEC(duration)), 0) / 3600, 2) AS total_hours
            FROM task
            WHERE project_id = ? AND emp_id = ?
        ");
        $stmt->bind_param('ii', $projectId, $empId);
        $stmt->execute();
        $total = 0;
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        return (float) $total;
    }

    // =========================================================
    // 15. getMonthlyActualHours($projectId, $month)
    //     Actual hours for a project in a specific month (T&M / Staff Aug).
    //     $month format: 'YYYY-MM'
    // =========================================================
    public function getMonthlyActualHours(int $projectId, string $month): float
    {
        $stmt = $this->conn->prepare("
            SELECT ROUND(COALESCE(SUM(TIME_TO_SEC(duration)), 0) / 3600, 2) AS total_hours
            FROM task
            WHERE project_id = ?
              AND DATE_FORMAT(date, '%Y-%m') = ?
        ");
        $stmt->bind_param('is', $projectId, $month);
        $stmt->execute();
        $total = 0;
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        return (float) $total;
    }

    // =========================================================
    // 16. getProjectPerformance($projectId)
    //     Returns classification: Highly Efficient / Effective / Poorly Managed
    // =========================================================
    public function getProjectPerformance(int $projectId): array
    {
        $project = $this->getProjectById($projectId);
        if (!$project) {
            return ['classification' => 'Unknown', 'utilization_pct' => 0];
        }

        $type = $project['project_type'];

        if ($type === 'fixed_cost') {
            $budget = floatval($project['total_estimated_hours']);
            $actual = $this->getActualHoursByProject($projectId);
            $pct    = $budget > 0 ? round(($actual / $budget) * 100, 1) : 0;

            $label = $this->classifyFixed($pct);

        } else {
            // T&M or Staff Augmentation — compare current month
            $month  = date('Y-m');
            $budget = floatval($project['monthly_allocated_hours']);
            $actual = $this->getMonthlyActualHours($projectId, $month);
            $pct    = $budget > 0 ? round(($actual / $budget) * 100, 1) : 0;

            $label = $this->classifyTM($pct);
        }

        $totalAllocated = $this->getTotalAllocatedHours($projectId);

        return [
            'classification'  => $label,
            'utilization_pct' => $pct,
            'actual_hours'    => $actual,
            'budget_hours'    => $budget,
            'remaining_hours' => round($budget - $actual, 2),
            'total_allocated_hours' => $totalAllocated,
            'remaining_allocation'  => round($budget - $totalAllocated, 2),
            'project_type'    => $type
        ];
    }

    // =========================================================
    // 17. getUtilizationReport($projectId)
    //     Estimated vs Actual + over/under classification for dashboard.
    // =========================================================
    public function getUtilizationReport(int $projectId): array
    {
        $performance = $this->getProjectPerformance($projectId);
        $pct         = $performance['utilization_pct'];

        $status = 'on_track';
        if ($pct > 100)     $status = 'over';
        elseif ($pct < 80)  $status = 'under';

        return array_merge($performance, [
            'status'            => $status,
            'members'           => $this->getProjectMembers($projectId)
        ]);
    }

    // =========================================================
    // 18. getProjectsForDashboard($empId, $role)
    //     Role-aware — SM sees created, PM sees managed, Admin sees all.
    // =========================================================
    public function getProjectsForDashboard(int $empId, string $role): array
    {
        switch ($role) {
            case 'sales_manager':
                $projects = $this->getProjectsByCreator($empId);
                break;
            case 'project_manager':
                $projects = $this->getProjectsByManager($empId);
                break;
            case 'admin':
            default:
                $result   = $this->getAllProjects(['is_active' => 1], 1, 500);
                $projects = $result['projects'];
                break;
        }

        // Attach live performance data to each project
        foreach ($projects as &$p) {
            $p['performance'] = $this->getProjectPerformance((int) $p['project_id']);
        }

        return $projects;
    }

    // =========================================================
    // HELPER: getActiveProjectsForEmployee($empId)
    //         Used by task form — returns projects this employee is a member of.
    // =========================================================
    public function getActiveProjectsForEmployee(int $empId): array
    {
        $stmt = $this->conn->prepare("
            SELECT p.project_id, p.project_name, p.project_type, p.client_id,
                   c.client_name, pm.allocated_hours AS my_allocated_hours
            FROM project_member pm
            JOIN project p ON pm.project_id = p.project_id
            LEFT JOIN client c ON p.client_id = c.client_id
            WHERE pm.emp_id = ? AND p.is_active = 1 AND pm.is_active = 1
            ORDER BY p.project_name ASC
        ");
        $stmt->bind_param('i', $empId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows   = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $rows;
    }

    // =========================================================
    // HELPER: getAllActiveProjects()
    //         Used by SM/PM/Admin task form — all active projects.
    // =========================================================
    public function getAllActiveProjects(): array
    {
        $result = $this->conn->query("
            SELECT p.project_id, p.project_name, p.project_type, p.client_id, c.client_name
            FROM project p
            LEFT JOIN client c ON p.client_id = c.client_id
            WHERE p.is_active = 1
            ORDER BY p.project_name ASC
        ");
        return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    }

    // =========================================================
    // PRIVATE HELPERS
    // =========================================================

    private function getTotalAllocatedHours(int $projectId): float
    {
        $stmt = $this->conn->prepare("SELECT COALESCE(SUM(allocated_hours), 0) FROM project_member WHERE project_id = ?");
        $stmt->bind_param('i', $projectId);
        $stmt->execute();
        $total = 0;
        $stmt->bind_result($total);
        $stmt->fetch();
        $stmt->close();
        return (float) $total;
    }

    private function classifyFixed(float $pct): string
    {
        if ($pct <= self::FIXED_HIGHLY_EFFICIENT_MAX) return 'Highly Efficient';
        if ($pct <= self::FIXED_EFFECTIVE_MAX)        return 'Effective';
        return 'Poorly Managed';
    }

    private function classifyTM(float $pct): string
    {
        if ($pct <= self::TM_HIGHLY_EFFICIENT_MAX) return 'Highly Efficient';
        if ($pct <= self::TM_EFFECTIVE_MAX)        return 'Effective';
        return 'Poorly Managed';
    }

    private function getMemberStatus(float $pct): string
    {
        if ($pct > 100) return 'over';
        if ($pct < 75)  return 'under';
        return 'on_track';
    }
}
