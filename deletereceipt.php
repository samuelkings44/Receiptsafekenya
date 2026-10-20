<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";
require_once "audit.php";

$user_id = $_SESSION["user_id"];


/* CHECK RECEIPT ID */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid receipt ID.");
}

$receipt_id = (int) $_GET["id"];


/* GET RECEIPT DETAILS BEFORE DELETING */

$sql = "SELECT receipt_number
        FROM receipts
        WHERE id = ?
        AND user_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $receipt_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Receipt not found.");
}

$receipt = $result->fetch_assoc();

$receipt_number = $receipt["receipt_number"];

$stmt->close();


/* DELETE RECEIPT */

$sql = "DELETE FROM receipts
        WHERE id = ?
        AND user_id = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "ii",
    $receipt_id,
    $user_id
);


if ($stmt->execute()) {

    /* RECORD AUDIT */

    logAudit(
        $conn,
        $user_id,
        "DELETE_RECEIPT",
        "Deleted receipt " . $receipt_number
    );

    $stmt->close();

    header("Location: viewreceipt.php");
    exit();

} else {

    die("Failed to delete receipt: " . $stmt->error);

}

?>