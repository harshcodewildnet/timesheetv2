<?php


class TaskCategory
{
    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;
    }

    public function getCategoriesByDept($deptId)
    {
        $stmt = $this->conn->prepare("SELECT * FROM task_category WHERE dept_id = ?");
        $stmt->bind_param("i", $deptId);
        $stmt->execute();
        $result = $stmt->get_result();

        $taskCategories = [];
        while($taskCategory = $result->fetch_assoc()){
            $taskCategories[] = $taskCategory;
        }

        return $taskCategories;
    }

}