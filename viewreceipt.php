<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$user_id = $_SESSION["user_id"];

$sql = "SELECT * FROM receipts
        WHERE user_id = ?
        ORDER BY created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>View Receipts | ReceiptSafe Kenya</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .receipt-table-container {
            width: 100%;
            overflow-x: auto;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 900px;
        }

        .receipt-table th,
        .receipt-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            white-space: nowrap;
        }

        .receipt-table th {
            font-weight: bold;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: nowrap;
        }

        .action-buttons a {
            text-decoration: none;
            display: inline-block;
        }

    </style>

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

        <a href="viewreceipt.php" class="active">
            Receipts
        </a>

        <a href="searchreceipt.php">
            Search
        </a>

        <a href="reports.php">
            Reports
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
                Stored Receipts
            </h1>

            <p>
                View and manage receipts stored in the system.
            </p>

        </div>

        <div>

            <a href="addreceipt.php" class="primary-btn">
                Add Receipt
            </a>

        </div>

    </div>

    <div class="action-card">

        <?php if ($result->num_rows > 0): ?>

            <div class="receipt-table-container">

                <table class="receipt-table">

                    <thead>

                        <tr>
                            <th>Receipt Number</th>
                            <th>Customer</th>
                            <th>Business</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Category</th>
                            <th>Action</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php while ($receipt = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php echo htmlspecialchars($receipt["receipt_number"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($receipt["customer_name"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($receipt["business_name"]); ?>
                                </td>

                                <td>
                                    KES <?php echo number_format($receipt["amount"], 2); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($receipt["receipt_date"]); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($receipt["category"]); ?>
                                </td>

                                <td>

                                    <div class="action-buttons">

                                        <a
                                            href="viewreceiptdetails.php?id=<?php echo (int) $receipt["id"]; ?>"
                                            class="primary-btn"
                                        >
                                            View Details
                                        </a>

                                        <a
                                            href="editreceipt.php?id=<?php echo (int) $receipt["id"]; ?>"
                                            class="secondary-btn"
                                        >
                                            Edit
                                        </a>

                                        <a
                                            href="deletereceipt.php?id=<?php echo (int) $receipt["id"]; ?>"
                                            class="secondary-btn"
                                            onclick="return confirm('Are you sure you want to delete this receipt? This action cannot be undone.');"
                                        >
                                            Delete
                                        </a>

                                    </div>

                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p>
                No receipts have been stored yet.
            </p>

            <a href="addreceipt.php" class="primary-btn">
                Add Your First Receipt
            </a>

        <?php endif; ?>

    </div>

</main>

</body>

</html>

<?php

$stmt->close();
$conn->close();

?>