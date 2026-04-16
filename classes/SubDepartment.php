<?php
class SubDepartment
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function addSubDepartment($data)
    {
        $stmt = $this->conn->prepare("INSERT INTO sub_department (subdept_name, dept_id) VALUES (?, ?)");
        $stmt->bind_param("si", $data['subdept_name'], $data['dept_id']);
        return $stmt->execute();
    }

    public function updateSubDepartment($id, $data)
    {
        $stmt = $this->conn->prepare("UPDATE sub_department SET subdept_name = ?, dept_id = ? WHERE subdept_id = ?");
        $stmt->bind_param("sii", $data['subdept_name'], $data['dept_id'], $id);
        return $stmt->execute();
    }

    public function deleteSubDepartment($subdept_id)
    {
        $stmt = $this->conn->prepare("DELETE FROM sub_department WHERE subdept_id = ?");
        $stmt->bind_param("i", $subdept_id);
        return $stmt->execute();
    }

    public function getAllSubDepartments()
    {
        $result = $this->conn->query("SELECT s.*, d.dept_name FROM sub_department s JOIN department d ON s.dept_id = d.dept_id ORDER BY d.dept_name, s.subdept_name");
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getSubDepartmentsByDeptId($dept_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM sub_department WHERE dept_id = ?");
        $stmt->bind_param("i", $dept_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getSubDepartmentById($subdept_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM sub_department WHERE subdept_id = ?");
        $stmt->bind_param("i", $subdept_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }


    public function subdeptExists($subdept_name, $dept_id)
    {
        $query = "SELECT 1 FROM sub_department WHERE subdept_name = ? AND dept_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $subdept_name, $dept_id);
        $stmt->execute();
        $stmt->store_result();

        return $stmt->num_rows > 0;
    }

}
