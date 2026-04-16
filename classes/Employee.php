<?php

class Employee
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getAllEmployees()
    {
        $employees = [];
        $sql = 'SELECT e.*, rm.name as rm_name, d.dept_name FROM employee e left join employee rm on e.rm_id = rm.emp_id inner join department d on e.dept_id = d.dept_id order by e.created_at desc';
        $result = $this->conn->query($sql);

        // If the schema differs (e.g., missing created_at), fall back to a safer ordering.
        if ($result === false) {
            $fallbackSql = 'SELECT e.*, rm.name as rm_name, d.dept_name FROM employee e left join employee rm on e.rm_id = rm.emp_id inner join department d on e.dept_id = d.dept_id order by e.emp_id desc';
            $result = $this->conn->query($fallbackSql);

            if ($result === false) {
                error_log('Employee::getAllEmployees query failed: ' . $this->conn->error);
                return [];
            }
        }

        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }

        return $employees;
    }

    public function getEmployeeById($id)
    {
        $stmt = $this->conn->prepare('SELECT e.*, d.dept_name FROM employee e left join department d on e.dept_id = d.dept_id WHERE emp_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getEmployeesByRMId($id)
    {

        $stmt = $this->conn->prepare('SELECT e.*, d.dept_name FROM employee e join department d on e.dept_id = d.dept_id WHERE rm_id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }

        return $employees;
    }


    public function getRMsByDepartment($dept_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM employee WHERE role in ('hod', 'rm') AND dept_id = ?");
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getRMsBySubDepartment($subdept_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM employee WHERE role = 'rm' AND subdept_id = ?");
        $stmt->bind_param("i", $subdept_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }




    public function getEmployeesByDeptId($dept_id)
    {
        $stmt = $this->conn->prepare("SELECT e.*, d.dept_name FROM employee e join department d on e.dept_id = d.dept_id WHERE e.dept_id = ? AND e.role != 'hod' order by name asc");
        $stmt->bind_param('i', $dept_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $employees = [];
        while ($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }

        return $employees;
    }


    // public function addEmployee($employee)
    // {
    //     $stmt = $this->conn->prepare('INSERT INTO employee(emp_id, name, email, password, role, dept_id, subdept_id, rm_id, doj) VALUES(?,?,?,?,?,?,?,?,?)');
    //     $stmt->bind_param('issssiiis', $employee['emp_id'], $employee['name'], $employee['email'], $employee['password'], $employee['role'], $employee['dept_id'], $employee['subdept_id'], $employee['rm_id'], $employee['doj']);
    //     return $stmt->execute();
    // }



    // public function addEmployee($employee): array
    // {
    //     $stmt = $this->conn->prepare('
    //     INSERT INTO employee(emp_id, name, email, password, role, dept_id, subdept_id, rm_id, doj)
    //     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    // ');

    //     if (!$stmt) {
    //         // Error in preparing the statement
    //         return ['success' => false, 'error' => 'Prepare failed: ' . $this->conn->error];
    //     }

    //     $stmt->bind_param(
    //         'issssiiis',
    //         $employee['emp_id'],
    //         $employee['name'],
    //         $employee['email'],
    //         $employee['password'],
    //         $employee['role'],
    //         $employee['dept_id'],
    //         $employee['subdept_id'],
    //         $employee['rm_id'],
    //         $employee['doj']
    //     );

    //     if (!$stmt->execute()) {
    //         // Error in executing the statement
    //         return ['success' => false, 'error' => 'Execute failed: ' . $stmt->error];
    //     }

    //     return ['success' => true];
    // }


    public function addEmployee($employee)
    {
        $stmt = $this->conn->prepare('
        INSERT INTO employee (emp_id, name, email, password, role, dept_id, subdept_id, rm_id, doj)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param(
            'issssiiis',
            $employee['emp_id'],
            $employee['name'],
            $employee['email'],
            $employee['password'],
            $employee['role'],
            $employee['dept_id'],
            $employee['subdept_id'],
            $employee['rm_id'],
            $employee['doj']
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        return true; // Success
    }



    // public function updateEmployee($id, $employee)
    // {
    //     $stmt = $this->conn->prepare('UPDATE employee SET name=?, email=?, password=?, role=?, dept_id=?, rm_id=?, doj=? WHERE emp_id=?');
    //     $stmt->bind_param('ssssiisi', $employee['name'], $employee['email'], $employee['password'], $employee['role'], $employee['dept_id'], $employee['rm_id'], $employee['doj'], $id);
    //     return $stmt->execute();
    // }

    // public function updateEmployee($id, $employee)
    // {
    //     $stmt = $this->conn->prepare('UPDATE employee SET name=?, password=?, contact=? WHERE emp_id=?');
    //     $stmt->bind_param('ssii', $employee['name'], $employee['password'], $employee['contact'], $id);
    //     return $stmt->execute();
    // }


    public function updateEmployee($id, $data)
    {
        // Filter allowed fields to avoid SQL injection
        $allowedFields = ['name', 'email', 'password', 'role', 'dept_id', 'subdept_id', 'rm_id', 'contact'];
        $fieldsToUpdate = [];
        $params = [];
        $types = '';

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $fieldsToUpdate[] = "$key = ?";
                $params[] = $value;
                $types .= is_int($value) ? 'i' : 's';
            }
        }

        if (empty($fieldsToUpdate)) {
            return false;
        }

        $params[] = $id;
        $types .= 'i';

        $sql = "UPDATE employee SET " . implode(', ', $fieldsToUpdate) . " WHERE emp_id = ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }



    public function deleteEmployee($id)
    {
        $stmt = $this->conn->prepare('DELETE FROM employee WHERE emp_id = ?');
        $stmt->bind_param('i', $id);
        return $stmt->execute();
    }

    public function updateProfilePhoto($employeeId, $photoPath)
    {
        $stmt = $this->conn->prepare('UPDATE employee SET profile_photo = ? WHERE emp_id = ?');
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('si', $photoPath, $employeeId);
        return $stmt->execute();
    }


}