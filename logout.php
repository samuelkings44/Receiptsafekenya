<?php

session_start();

require_once "db.php";
require_once "audit.php";

// Get the current user's ID before destroying the session
$user_id = $_SESSION["user_id"] ?? null;

if ($user_id !== null) {

    // Record the logout activity
    logAudit(
        $conn,
        $user_id,
        "LOGOUT",
        "User logged out of the system"
    );

}

// Destroy the session
$_SESSION = [];

session_destroy();

// Redirect to login page
header("Location: login.php");
exit();

?>