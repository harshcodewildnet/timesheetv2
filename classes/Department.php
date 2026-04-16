<?php

class Department
{
    private $conn;
    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getDepartments()
    {
        $departments = [];
        $result = $this->conn->query('SELECT * FROM department');
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row;
        }

        return $departments;
    }

    public function getDepartmentById($id)
    {
        $result = $this->conn->query("SELECT * FROM department WHERE dept_id = $id");
        return $result->fetch_assoc();
    }

    public function addDepartment($department)
    {
        // Check if department with same name exists
        $checkStmt = $this->conn->prepare('SELECT dept_id FROM department WHERE dept_name = ?');
        $checkStmt->bind_param('s', $department['dept_name']);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows > 0) {
            return 'exists'; // Department already exists
        }

        // Insert new department
        $stmt = $this->conn->prepare('INSERT INTO department(dept_name, dept_email) VALUES(?, ?)');
        $stmt->bind_param('ss', $department['dept_name'], $department['dept_email']);

        return $stmt->execute() ? 'success' : 'error';
    }

    public function updateDepartment($id, $department)
    {
        $stmt = $this->conn->prepare("UPDATE department SET dept_name = ? WHERE dept_id = ?");
        $stmt->bind_param('si', $department['dept_name'], $id);
        return $stmt->execute();
    }


    public function deleteDepartment($id)
    {
        $stmt = $this->conn->prepare("DELETE FROM department WHERE dept_id = ?");
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }


}