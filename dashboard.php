
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

$full_name = $_SESSION["full_name"] ?? "User";
$email = $_SESSION["email"] ?? "";


/* TOTAL RECEIPTS */

$sql_total = "SELECT COUNT(*) AS total
              FROM receipts
              WHERE user_id = ?";

$stmt_total = $conn->prepare($sql_total);
$stmt_total->bind_param("i", $user_id);
$stmt_total->execute();

$total_result = $stmt_total->get_result();
$total_receipts = $total_result->fetch_assoc()["total"];


/* TOTAL AMOUNT */

$sql_amount = "SELECT COALESCE(SUM(amount), 0) AS total_amount
               FROM receipts
               WHERE user_id = ?";

$stmt_amount = $conn->prepare($sql_amount);
$stmt_amount->bind_param("i", $user_id);
$stmt_amount->execute();

$amount_result = $stmt_amount->get_result();
$total_amount = $amount_result->fetch_assoc()["total_amount"];


/* RECENT RECEIPTS - LAST 7 DAYS */

$sql_recent = "SELECT COUNT(*) AS total
               FROM receipts
               WHERE user_id = ?
               AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)";

$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bind_param("i", $user_id);
$stmt_recent->execute();

$recent_result = $stmt_recent->get_result();
$recent_receipts = $recent_result->fetch_assoc()["total"];


/* DUPLICATES */

$sql_duplicates = "SELECT COUNT(*) AS total
                   FROM (
                       SELECT receipt_number
                       FROM receipts
                       WHERE user_id = ?
                       GROUP BY receipt_number
                       HAVING COUNT(*) > 1
                   ) AS duplicate_receipts";

$stmt_duplicates = $conn->prepare($sql_duplicates);
$stmt_duplicates->bind_param("i", $user_id);
$stmt_duplicates->execute();

$duplicate_result = $stmt_duplicates->get_result();
$duplicate_receipts = $duplicate_result->fetch_assoc()["total"];


/* REPORTS */

$reports_count = 1;


/* RECENT RECEIPTS LIST */

$sql_recent_list = "SELECT receipt_number,
                           customer_name,
                           business_name,
                           amount,
                           receipt_date,
                           category,
                           created_at
                    FROM receipts
                    WHERE user_id = ?
                    ORDER BY created_at DESC
                    LIMIT 5";

$stmt_recent_list = $conn->prepare($sql_recent_list);
$stmt_recent_list->bind_param("i", $user_id);
$stmt_recent_list->execute();

$recent_list_result = $stmt_recent_list->get_result();


/* RECENT ACTIVITY */

$sql_activity = "SELECT receipt_number,
                        business_name,
                        amount,
                        created_at
                 FROM receipts
                 WHERE user_id = ?
                 ORDER BY created_at DESC
                 LIMIT 5";

$stmt_activity = $conn->prepare($sql_activity);
$stmt_activity->bind_param("i", $user_id);
$stmt_activity->execute();

$activity_result = $stmt_activity->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard | ReceiptSafe Kenya</title>

    <link rel="stylesheet" href="style.css">

</head>

<body>

<header class="navbar">

    <div class="logo">
        ReceiptSafe <span>Kenya</span>
    </div>

    <nav class="nav-links">

        <a href="dashboard.php" class="active">
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
                Welcome, <?php echo htmlspecialchars($full_name); ?>
            </h1>

            <p>
                Manage and track your receipts from one place.
            </p>

        </div>

    </div>


    <!-- DASHBOARD CARDS -->

    <div class="dashboard-cards">

        <div class="dashboard-card">

            <h3>
                Total Receipts
            </h3>

            <strong>
                <?php echo $total_receipts; ?>
            </strong>

            <p>
                Receipts stored in the system
            </p>

        </div>


        <div class="dashboard-card">

            <h3>
                Total Amount
            </h3>

            <strong>
                Ksh <?php echo number_format((float)$total_amount, 2); ?>
            </strong>

            <p>
                Total value of stored receipts
            </p>

        </div>


        <div class="dashboard-card">

            <h3>
                Recent Receipts
            </h3>

            <strong>
                <?php echo $recent_receipts; ?>
            </strong>

            <p>
                Receipts added in the last 7 days
            </p>

        </div>


        <div class="dashboard-card">

            <h3>
                Duplicates
            </h3>

            <strong>
                <?php echo $duplicate_receipts; ?>
            </strong>

            <p>
                Potential duplicate records
            </p>

        </div>

    </div>


    <!-- QUICK ACTIONS -->

    <div class="dashboard-actions">

        <div class="action-card">

            <h2>
                Quick Actions
            </h2>

            <p>
                Manage your receipts quickly from here.
            </p>

            <a href="addreceipt.php" class="primary-btn">
                Add Receipt
            </a>

            <a href="viewreceipt.php" class="secondary-btn">
                View Receipts
            </a>

            <a href="searchreceipt.php" class="secondary-btn">
                Search Receipts
            </a>

        </div>


        <div class="action-card">

            <h2>
                Search Receipts
            </h2>

            <p>
                Quickly find receipts using receipt number, customer, business or category.
            </p>

            <a href="searchreceipt.php" class="secondary-btn">
                Search
            </a>

        </div>

    </div>


    <!-- RECENT RECEIPTS -->

    <div class="action-card">

        <h2>
            Recent Receipts
        </h2>

        <p>
            Your five most recently added receipts.
        </p>

        <?php if ($recent_list_result->num_rows > 0): ?>

            <div class="recent-receipts">

                <?php while ($receipt = $recent_list_result->fetch_assoc()): ?>

                    <div class="receipt-item">

                        <h3>
                            Receipt #<?php echo htmlspecialchars($receipt["receipt_number"]); ?>
                        </h3>

                        <p>
                            <strong>Business:</strong>
                            <?php echo htmlspecialchars($receipt["business_name"]); ?>
                        </p>

                        <p>
                            <strong>Customer:</strong>
                            <?php echo htmlspecialchars($receipt["customer_name"]); ?>
                        </p>

                        <p>
                            <strong>Amount:</strong>
                            Ksh <?php echo number_format((float)$receipt["amount"], 2); ?>
                        </p>

                        <p>
                            <strong>Date:</strong>
                            <?php echo htmlspecialchars($receipt["receipt_date"]); ?>
                        </p>

                        <p>
                            <strong>Category:</strong>
                            <?php echo htmlspecialchars($receipt["category"]); ?>
                        </p>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <p>
                No receipts have been added yet.
            </p>

        <?php endif; ?>

    </div>


    <!-- RECENT ACTIVITY -->

    <div class="action-card">

        <h2>
            Recent Activity
        </h2>

        <p>
            Your latest receipt activities.
        </p>

        <?php if ($activity_result->num_rows > 0): ?>

            <div class="recent-activity">

                <?php while ($activity = $activity_result->fetch_assoc()): ?>

                    <div class="activity-item">

                        <p>
                            Receipt
                            <strong>
                                #<?php echo htmlspecialchars($activity["receipt_number"]); ?>
                            </strong>
                            was added for
                            <strong>
                                <?php echo htmlspecialchars($activity["business_name"]); ?>
                            </strong>
                        </p>

                        <p>
                            Amount:
                            <strong>
                                Ksh <?php echo number_format((float)$activity["amount"], 2); ?>
                            </strong>
                        </p>

                        <small>
                            <?php echo htmlspecialchars($activity["created_at"]); ?>
                        </small>

                    </div>

                <?php endwhile; ?>

            </div>

        <?php else: ?>

            <p>
                No recent activity.
            </p>

        <?php endif; ?>

    </div>


    <!-- ACCOUNT INFORMATION -->

    <div class="action-card">

        <h2>
            Account Information
        </h2>

        <p>
            <strong>Name:</strong>
            <?php echo htmlspecialchars($full_name); ?>
        </p>

        <p>
            <strong>Email:</strong>
            <?php echo htmlspecialchars($email); ?>
        </p>

    </div>

</main>

</body>

</html>
```
