<?php

function requireRole($roles = [])
{
    if (!isset($_SESSION['emp_role'])) {
        header('Location: index');
        exit();
    }

    if (!in_array($_SESSION['emp_role'], $roles)) {
        header('Location: unauthorized');
    }
}
