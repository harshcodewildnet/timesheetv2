<?php


class Client
{

    private $conn;

    public function __construct($conn)
    {
        $this->conn = $conn;

    }

    public function addNewClient($client)
    {
        $stmt = $this->conn->prepare('INSERT INTO client(client_id, client_name, email, is_active) VALUES(?,?,?,?)');
        $clientId = $client['client_id'];
        $clientName = $client['client_name'];
        $email = isset($client['email']) && $client['email'] !== '' ? $client['email'] : null;
        $status = $client['status'];
        $stmt->bind_param('sssi', $clientId, $clientName, $email, $status);

        return $stmt->execute();
    }

    public function addClientsToEmployeeId($clientIds, $emp_id)
    {
        if (!$emp_id) {
            return ['status' => 'error', 'message' => 'Invalid employee ID'];
        }

        // If string like "3,7,10" is passed, convert to array
        if (!is_array($clientIds)) {
            $clientIds = array_filter(array_map('trim', explode(',', (string) $clientIds)));
        }

        // Always delete old assignments first
        $deleteStmt = $this->conn->prepare("DELETE FROM employee_client WHERE emp_id = ?");
        $deleteStmt->bind_param('i', $emp_id);
        $deleteStmt->execute();
        $deleteStmt->close();

        // If no new clients to assign, return success after clearing old ones
        if (empty($clientIds)) {
            return ['status' => 'success', 'message' => 'All clients cleared for this employee'];
        }

        // Prepare insert statement
        $stmt = $this->conn->prepare("INSERT INTO employee_client (emp_id, client_id) VALUES (?, ?)");
        $successCount = 0;

        foreach ($clientIds as $cid) {
            $stmt->bind_param('is', $emp_id, $cid);
            if ($stmt->execute()) {
                $successCount++;
            }
        }

        $stmt->close();

        if ($successCount > 0) {
            return ['status' => 'success', 'message' => "$successCount client(s) assigned"];
        } else {
            return ['status' => 'error', 'message' => 'Failed to assign clients'];
        }
    }



    public function getAllClients()
    {
        $clients = [];
        $result = $this->conn->query("SELECT * FROM client WHERE client_id != '-1' order by added desc");
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }

        return $clients;
    }

    public function getActiveClients()
    {
        $clients = [];
        $result = $this->conn->query("SELECT * FROM client where is_active = 1 AND client_id != '-1'");
        while ($row = $result->fetch_assoc()) {
            $clients[] = $row;
        }

        return $clients;
    }

    public function getClientById($client_id)
    {
        $stmt = $this->conn->prepare("SELECT * FROM client WHERE client_id = ?");
        $stmt->bind_param('s', $client_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getClientsByEmployeeId($emp_id)
    {
        $stmt = $this->conn->prepare("SELECT c.* FROM client c inner join employee_client ec on c.client_id = ec.client_id and ec.emp_id = ? where c.is_active = 1");
        // $result = $this->conn->query("SELECT c.* FROM client c inner join employee_client ec on c.client_id = ec.client_id and ec.emp_id = ?");
        $stmt->bind_param('i', $emp_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $employeeClients = [];
        while ($row = $result->fetch_assoc()) {
            $employeeClients[] = $row;
        }

        return $employeeClients;
    }

    public function updateClient($id, $data)
    {
        // Filter allowed fields to avoid SQL injection
        $allowedFields = ['client_id', 'client_name', 'email'];
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
        $types .= 's';

        $sql = "UPDATE client SET " . implode(', ', $fieldsToUpdate) . " WHERE client_id = ?";
        $stmt = $this->conn->prepare($sql);

        if (!$stmt) {
            return false;
        }

        $stmt->bind_param($types, ...$params);
        return $stmt->execute();
    }
    // public function deleteClient($id)
    // {
    //     $stmt = $this->conn->prepare('DELETE FROM client WHERE client_id = ?');
    //     if (!$stmt)
    //         return false;

    //     $stmt->bind_param('s', $id);
    //     $success = $stmt->execute();
    //     $stmt->close();

    //     return $success && $stmt->affected_rows > 0;
    // }

    public function deleteClient($id)
    {
        // Sanitize: remove leading/trailing spaces
        $id = trim($id);

        // Validate allowed characters: letters, numbers, hyphen, underscore
        if (!preg_match('/^[A-Za-z0-9_\-]+$/', $id)) {
            return ['status' => 'error', 'message' => 'Invalid client ID format'];
        }

        $stmt = $this->conn->prepare('DELETE FROM client WHERE client_id = ?');
        if (!$stmt) {
            return ['status' => 'error', 'message' => 'Failed to prepare SQL statement'];
        }

        // Bind as string, not integer
        $stmt->bind_param('s', $id);

        if ($stmt->execute()) {
            $affectedRows = $stmt->affected_rows;
            $stmt->close();

            if ($affectedRows > 0) {
                return ['status' => 'success', 'message' => 'Client deleted successfully'];
            } else {
                return ['status' => 'error', 'message' => 'Client not found'];
            }
        } else {
            $error = $stmt->error;
            $stmt->close();
            return ['status' => 'error', 'message' => 'Delete failed: ' . $error];
        }
    }


}



