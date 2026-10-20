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

$error = "";
$message = "";


/* GET RECEIPT ID */

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    die("Invalid receipt ID.");
}

$receipt_id = (int) $_GET["id"];


/* GET RECEIPT */

$sql = "SELECT * FROM receipts
        WHERE id = ?
        AND user_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param(
    "ii",
    $receipt_id,
    $user_id
);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Receipt not found.");
}

$receipt = $result->fetch_assoc();

$stmt->close();


/* UPDATE RECEIPT */

if ($_SERVER["REQUEST_METHOD"] == "POST") {

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

        /* CHECK DUPLICATE RECEIPT NUMBER */

        $check_sql = "SELECT id FROM receipts
                      WHERE user_id = ?
                      AND receipt_number = ?
                      AND id != ?";

        $check_stmt = $conn->prepare($check_sql);

        if (!$check_stmt) {

            $error = "Database error: " . $conn->error;

        } else {

            $check_stmt->bind_param(
                "isi",
                $user_id,
                $receipt_number,
                $receipt_id
            );

            $check_stmt->execute();

            $check_result = $check_stmt->get_result();

            if ($check_result->num_rows > 0) {

                $error = "Another receipt with this receipt number already exists.";

            } else {

                /* UPDATE RECEIPT */

                $update_sql = "UPDATE receipts
                               SET receipt_number = ?,
                                   customer_name = ?,
                                   business_name = ?,
                                   amount = ?,
                                   receipt_date = ?,
                                   category = ?,
                                   description = ?
                               WHERE id = ?
                               AND user_id = ?";

                $update_stmt = $conn->prepare($update_sql);

                if (!$update_stmt) {

                    $error = "Database error: " . $conn->error;

                } else {

                    $update_stmt->bind_param(
                        "sssdsssii",
                        $receipt_number,
                        $customer_name,
                        $business_name,
                        $amount,
                        $receipt_date,
                        $category,
                        $description,
                        $receipt_id,
                        $user_id
                    );

                    if ($update_stmt->execute()) {

                        /*
                         * RECORD AUDIT TRAIL
                         */

                        logAudit(
                            $conn,
                            $user_id,
                            "EDIT_RECEIPT",
                            "Edited receipt " . $receipt_number
                        );

                        $message = "Receipt updated successfully.";

                        /* REFRESH DISPLAYED DATA */

                        $receipt["receipt_number"] = $receipt_number;
                        $receipt["customer_name"] = $customer_name;
                        $receipt["business_name"] = $business_name;
                        $receipt["amount"] = $amount;
                        $receipt["receipt_date"] = $receipt_date;
                        $receipt["category"] = $category;
                        $receipt["description"] = $description;

                    } else {

                        $error = "Failed to update receipt: " . $update_stmt->error;

                    }

                    $update_stmt->close();
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

    <title>Edit Receipt | ReceiptSafe Kenya</title>

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

        <a href="viewreceipt.php">
            Receipts
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
                Edit Receipt
            </h1>

            <p>
                Update the information of the selected receipt.
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
                    value="<?php echo htmlspecialchars($receipt["receipt_number"]); ?>"
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
                    value="<?php echo htmlspecialchars($receipt["customer_name"]); ?>"
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
                    value="<?php echo htmlspecialchars($receipt["business_name"]); ?>"
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
                    value="<?php echo htmlspecialchars($receipt["amount"]); ?>"
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
                    value="<?php echo htmlspecialchars($receipt["receipt_date"]); ?>"
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

                    <option value="Food" <?php if ($receipt["category"] == "Food") echo "selected"; ?>>
                        Food
                    </option>

                    <option value="Transport" <?php if ($receipt["category"] == "Transport") echo "selected"; ?>>
                        Transport
                    </option>

                    <option value="Shopping" <?php if ($receipt["category"] == "Shopping") echo "selected"; ?>>
                        Shopping
                    </option>

                    <option value="Utilities" <?php if ($receipt["category"] == "Utilities") echo "selected"; ?>>
                        Utilities
                    </option>

                    <option value="Medical" <?php if ($receipt["category"] == "Medical") echo "selected"; ?>>
                        Medical
                    </option>

                    <option value="Other" <?php if ($receipt["category"] == "Other") echo "selected"; ?>>
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
                ><?php echo htmlspecialchars($receipt["description"]); ?></textarea>

            </p>


            <button
                type="submit"
                class="primary-btn"
            >
                Update Receipt
            </button>


            <a
                href="viewreceipt.php"
                class="secondary-btn"
            >
                Cancel
            </a>

        </form>

    </div>

</main>

</body>

</html>