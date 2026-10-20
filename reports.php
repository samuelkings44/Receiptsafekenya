```php
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


/* TOTAL RECEIPTS */

$sql_total = "SELECT COUNT(*) AS total
              FROM receipts
              WHERE user_id = ?";

$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();

$total_result = $stmt_total->get_result();
$total_receipts = $total_result->fetch_assoc()["total"];

$stmt_total->close();


/* TOTAL AMOUNT */

$sql_amount = "SELECT SUM(amount) AS total_amount
               FROM receipts
               WHERE user_id = ?";

$stmt_amount = $conn->prepare($sql_amount);
$stmt_amount->bind_param("i", $user_id);
$stmt_amount->execute();

$amount_result = $stmt_amount->get_result();
$total_amount = $amount_result->fetch_assoc()["total_amount"];

if ($total_amount === null) {
    $total_amount = 0;
}

$stmt_amount->close();


/* AVERAGE RECEIPT AMOUNT */

$sql_average = "SELECT AVG(amount) AS average_amount
                FROM receipts
                WHERE user_id = ?";

$stmt_average = $conn->prepare($sql_average);
$stmt_average->bind_param("i", $user_id);
$stmt_average->execute();

$average_result = $stmt_average->get_result();
$average_amount = $average_result->fetch_assoc()["average_amount"];

if ($average_amount === null) {
    $average_amount = 0;
}

$stmt_average->close();


/* RECEIPTS BY CATEGORY */

$sql_category = "SELECT category, COUNT(*) AS total
                 FROM receipts
                 WHERE user_id = ?
                 GROUP BY category
                 ORDER BY total DESC";

$stmt_category = $conn->prepare($sql_category);
$stmt_category->bind_param("i", $user_id);
$stmt_category->execute();

$category_result = $stmt_category->get_result();


/* MONTHLY REPORT */

$sql_monthly = "SELECT
                    DATE_FORMAT(receipt_date, '%Y-%m') AS month,
                    COUNT(*) AS total_receipts,
                    SUM(amount) AS total_amount
                FROM receipts
                WHERE user_id = ?
                GROUP BY DATE_FORMAT(receipt_date, '%Y-%m')
                ORDER BY month DESC";

$stmt_monthly = $conn->prepare($sql_monthly);
$stmt_monthly->bind_param("i", $user_id);
$stmt_monthly->execute();

$monthly_result = $stmt_monthly->get_result();


/* RECENT RECEIPTS */

$sql_recent = "SELECT
                    receipt_number,
                    customer_name,
                    business_name,
                    amount,
                    receipt_date,
                    category
               FROM receipts
               WHERE user_id = ?
               ORDER BY created_at DESC
               LIMIT 10";

$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bind_param("i", $user_id);
$stmt_recent->execute();

$recent_result = $stmt_recent->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reports | ReceiptSafe Kenya</title>

    <link rel="stylesheet" href="style.css">

    <style>
        /* Report page styling */

        .report-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 24px;
            box-sizing: border-box;
        }

        .report-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin: 20px 0 25px;
        }

        .print-btn {
            background: #2563eb;
            color: white;
            border: none;
            border-radius: 7px;
            padding: 12px 22px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
        }

        .print-btn:hover {
            background: #1d4ed8;
        }

        .report-page .action-card {
            margin-bottom: 24px;
            box-sizing: border-box;
            break-inside: avoid;
        }

        .report-page table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-page th,
        .report-page td {
            padding: 12px 14px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }

        .report-page th {
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 600;
        }

        .report-page td {
            color: #334155;
        }

        .report-page .dashboard-card strong {
            overflow-wrap: anywhere;
        }

        /* PRINT SETTINGS */

        @media print {

            @page {
                size: A4 portrait;
                margin: 15mm;
            }

            body {
                background: white !important;
                color: black !important;
                font-size: 11pt;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .navbar,
            .report-actions {
                display: none !important;
            }

            .report-page {
                max-width: none;
                width: 100%;
                padding: 0;
                margin: 0;
            }

            .dashboard-header {
                margin-bottom: 20px;
            }

            .report-page .dashboard-cards {
                display: grid;
                grid-template-columns: repeat(3, 1fr);
                gap: 10px;
            }

            .report-page .dashboard-card,
            .report-page .action-card {
                background: white !important;
                color: black !important;
                border: 1px solid #ccc !important;
                box-shadow: none !important;
                border-radius: 4px;
            }

            .report-page .dashboard-card {
                padding: 12px;
            }

            .report-page .dashboard-card strong {
                font-size: 16pt;
                color: black !important;
            }

            .report-page .action-card {
                padding: 16px;
                margin-bottom: 16px;
                break-inside: auto;
            }

            .report-page h1 {
                font-size: 23pt;
                color: black !important;
            }

            .report-page h2 {
                font-size: 15pt;
                color: black !important;
            }

            .report-page h3,
            .report-page p,
            .report-page td,
            .report-page th,
            .report-page .section-label {
                color: black !important;
            }

            .report-page table {
                width: 100%;
                border-collapse: collapse;
                font-size: 9pt;
            }

            .report-page th,
            .report-page td {
                padding: 7px 6px;
                border: 1px solid #bbb;
                white-space: normal;
                overflow-wrap: anywhere;
            }

            .report-page th {
                background: #eee !important;
            }

            .report-page .table-wrapper {
                overflow: visible !important;
            }

            .report-page tr {
                break-inside: avoid;
            }

            .report-page .action-card,
            .report-page .dashboard-card {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        @media screen and (max-width: 700px) {
            .report-page {
                padding: 20px 14px;
            }

            .report-actions {
                justify-content: flex-start;
            }

            .report-page .table-wrapper {
                overflow-x: auto;
            }
        }
    </style>

</head>

<body>

<header class="navbar">

    <div class="logo">
        ReceiptSafe <span>Kenya</span>
    </div>

    <nav class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="viewreceipt.php">Receipts</a>
        <a href="searchreceipt.php">Search</a>
        <a href="reports.php" class="active">Reports</a>
        <a href="audittrail.php">Audit Trail</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>


<main class="dashboard report-page">

    <div class="dashboard-header">
        <div>
            <p class="section-label">RECEIPT MANAGEMENT SYSTEM</p>
            <h1>Receipt Reports</h1>
            <p>Summary of your stored receipts and business transactions.</p>
        </div>
    </div>

    <!-- PRINT BUTTON -->

    <div class="report-actions">
        <button
            type="button"
            class="print-btn"
            onclick="window.print()"
        >
            Print Report
        </button>
    </div>


    <!-- SUMMARY CARDS -->

    <div class="dashboard-cards">

        <div class="dashboard-card">
            <h3>Total Receipts</h3>
            <strong>
                <?php echo (int) $total_receipts; ?>
            </strong>
            <p>Receipts stored in the system</p>
        </div>

        <div class="dashboard-card">
            <h3>Total Amount</h3>
            <strong>
                KSh <?php echo number_format((float) $total_amount, 2); ?>
            </strong>
            <p>Total value of stored receipts</p>
        </div>

        <div class="dashboard-card">
            <h3>Average Receipt</h3>
            <strong>
                KSh <?php echo number_format((float) $average_amount, 2); ?>
            </strong>
            <p>Average value per receipt</p>
        </div>

    </div>


    <!-- CATEGORY REPORT -->

    <div class="action-card">

        <h2>Receipts by Category</h2>

        <?php if ($category_result->num_rows > 0): ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Number of Receipts</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $category_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row["category"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                </td>
                                <td>
                                    <?php echo (int) $row["total"]; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <p>No category data available.</p>
        <?php endif; ?>

    </div>


    <!-- MONTHLY REPORT -->

    <div class="action-card">

        <h2>Monthly Receipt Summary</h2>

        <?php if ($monthly_result->num_rows > 0): ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Number of Receipts</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $monthly_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row["month"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                </td>
                                <td>
                                    <?php echo (int) $row["total_receipts"]; ?>
                                </td>
                                <td>
                                    KSh <?php echo number_format((float) $row["total_amount"], 2); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <p>No monthly data available.</p>
        <?php endif; ?>

    </div>


    <!-- RECENT RECEIPTS -->

    <div class="action-card">

        <h2>Recent Receipts (Latest 10)</h2>

        <?php if ($recent_result->num_rows > 0): ?>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Receipt Number</th>
                            <th>Customer</th>
                            <th>Business</th>
                            <th>Amount</th>
                            <th>Date</th>
                            <th>Category</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php while ($row = $recent_result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row["receipt_number"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row["customer_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row["business_name"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                </td>
                                <td>
                                    KSh <?php echo number_format((float) $row["amount"], 2); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row["receipt_date"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($row["category"] ?? "", ENT_QUOTES, "UTF-8"); ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

        <?php else: ?>
            <p>No receipts available.</p>
        <?php endif; ?>

    </div>

</main>

</body>
</html>
```