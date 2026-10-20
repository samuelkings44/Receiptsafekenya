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

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_id = $_SESSION["user_id"];

    $receipt_number = trim($_POST["receipt_number"] ?? "");
    $customer_name = trim($_POST["customer_name"] ?? "");
    $business_name = trim($_POST["business_name"] ?? "");
    $amount = trim($_POST["amount"] ?? "");
    $receipt_date = $_POST["receipt_date"] ?? "";
    $category = trim($_POST["category"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if (
        empty($receipt_number) ||
        empty($customer_name) ||
        empty($business_name) ||
        empty($amount) ||
        empty($receipt_date) ||
        empty($category)
    ) {

        $error = "Please fill in all required fields.";

    } else {

        /* CHECK FOR DUPLICATE RECEIPT */

        $check_sql = "SELECT id FROM receipts
                      WHERE user_id = ?
                      AND receipt_number = ?";

        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $check_stmt->bind_param(
                "is",
                $user_id,
                $receipt_number
            );

            $check_stmt->execute();

            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {

                $error = "Duplicate receipt detected. A receipt with this receipt number already exists.";

            } else {

                /* SAVE NEW RECEIPT */

                $sql = "INSERT INTO receipts
                        (
                            user_id,
                            receipt_number,
                            customer_name,
                            business_name,
                            amount,
                            receipt_date,
                            category,
                            description
                        )
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                if (!$stmt) {

                    $error = "Database error: " . $conn->error;

                } else {

                    $stmt->bind_param(
                        "isssdsss",
                        $user_id,
                        $receipt_number,
                        $customer_name,
                        $business_name,
                        $amount,
                        $receipt_date,
                        $category,
                        $description
                    );

                    if ($stmt->execute()) {

                        $receipt_id = $conn->insert_id;

                        /*
                         * RECORD ACTIVITY IN AUDIT TRAIL
                         */

                        logAudit(
                            $conn,
                            $user_id,
                            "ADD_RECEIPT",
                            "Added receipt " . $receipt_number
                        );

                        $message = "Receipt added successfully.";

                    } else {

                        $error = "Failed to save receipt: " . $stmt->error;

                    }

                    $stmt->close();
                }
            }

            $check_stmt->close();
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Add Receipt | ReceiptSafe Kenya</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<header class="navbar">

    <div class="logo">
        ReceiptSafe <span>Kenya</span>
    </div>

    <nav class="nav-links">

        <a href="dashboard.php">
            Dashboard
        </a>

        <a href="addreceipt.php" class="active">
            Add Receipt
        </a>

        <a href="searchreceipt.php">
            Search
        </a>

        <a href="reports.php">
            Reports
        </a>

        <a href="audittrail.php">
            Audit Trail
        </a>

        <a href="logout.php">
            Logout
        </a>

    </nav>

</header>

<main class="dashboard">

    <div class="dashboard-header">

        <div>

            <p class="section-label">
                RECEIPT MANAGEMENT SYSTEM
            </p>

            <h1>
                Add Receipt
            </h1>

            <p>
                Enter the details of the receipt below.
            </p>

        </div>

    </div>

    <?php if ($message != ""): ?>

        <p>
            <?php echo htmlspecialchars($message); ?>
        </p>

    <?php endif; ?>

    <?php if ($error != ""): ?>

        <p>
            <?php echo htmlspecialchars($error); ?>
        </p>

    <?php endif; ?>

    <div class="action-card">

        <form method="POST">

            <p>

                <label for="receipt_number">
                    Receipt Number
                </label>

                <br>

                <input
                    type="text"
                    id="receipt_number"
                    name="receipt_number"
                    required
                >

            </p>

            <p>

                <label for="customer_name">
                    Customer Name
                </label>

                <br>

                <input
                    type="text"
                    id="customer_name"
                    name="customer_name"
                    required
                >

            </p>

            <p>

                <label for="business_name">
                    Business Name
                </label>

                <br>

                <input
                    type="text"
                    id="business_name"
                    name="business_name"
                    required
                >

            </p>

            <p>

                <label for="amount">
                    Amount (KES)
                </label>

                <br>

                <input
                    type="number"
                    id="amount"
                    name="amount"
                    step="0.01"
                    min="0"
                    required
                >

            </p>

            <p>

                <label for="receipt_date">
                    Receipt Date
                </label>

                <br>

                <input
                    type="date"
                    id="receipt_date"
                    name="receipt_date"
                    required
                >

            </p>

            <p>

                <label for="category">
                    Category
                </label>

                <br>

                <select
                    id="category"
                    name="category"
                    required
                >

                    <option value="">
                        Select Category
                    </option>

                    <option value="Food">
                        Food
                    </option>

                    <option value="Transport">
                        Transport
                    </option>

                    <option value="Shopping">
                        Shopping
                    </option>

                    <option value="Utilities">
                        Utilities
                    </option>

                    <option value="Medical">
                        Medical
                    </option>

                    <option value="Other">
                        Other
                    </option>

                </select>

            </p>

            <p>

                <label for="description">
                    Description
                </label>

                <br>

                <textarea
                    id="description"
                    name="description"
                    rows="4"
                ></textarea>

            </p>

            <button
                type="submit"
                class="primary-btn"
            >
                Save Receipt
            </button>

            <a
                href="dashboard.php"
                class="secondary-btn"
            >
                Cancel
            </a>

        </form>

    </div>

</main>

</body>

</html>