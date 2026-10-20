<?php

function logAudit($conn, $user_id, $action, $description)
{
    $sql = "INSERT INTO audit_trail 
            (user_id, action, description)
            VALUES (?, ?, ?)";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "iss",
        $user_id,
        $action,
        $description
    );

    $success = $stmt->execute();

    $stmt->close();

    return $success;
}