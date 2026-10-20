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

$sql = "SELECT audit_trail.*, users.full_name
        FROM audit_trail
        INNER JOIN users ON audit_trail.user_id = users.id
        WHERE audit_trail.user_id = ?
        ORDER BY audit_trail.created_at DESC";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Audit Trail | ReceiptSafe Kenya</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .audit-table-container {
            overflow-x: auto;
            margin-top: 20px;
        }

        .audit-table {
            width: 100%;
            border-collapse: collapse;
        }

        .audit-table th,
        .audit-table td {
            padding: 14px 16px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .audit-table th {
            background: #f5f5f5;
            font-weight: 600;
        }

        .audit-table tr:hover {
            background: #fafafa;
        }

        .audit-action {
            font-weight: 600;
        }

        .empty-audit {
            text-align: center;
            padding: 40px 20px;
            color: #666;
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

        <a href="viewreceipt.php">
            Receipts
        </a>

        <a href="searchreceipt.php">
            Search
        </a>

        <a href="reports.php">
            Reports
        </a>

        <a href="audittrail.php" class="active">
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
                Audit Trail
            </h1>

            <p>
                View activities performed on your ReceiptSafe Kenya account.
            </p>

        </div>

    </div>

    <div class="action-card">

        <h2>
            Activity History
        </h2>

        <p>
            This section records important activities performed within the system.
        </p>

        <?php if ($result->num_rows > 0): ?>

            <div class="audit-table-container">

                <table class="audit-table">

                    <thead>

                        <tr>
                            <th>User</th>
                            <th>Action</th>
                            <th>Description</th>
                            <th>Date and Time</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php while ($log = $result->fetch_assoc()): ?>

                            <tr>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $log["full_name"]
                                    );
                                    ?>
                                </td>

                                <td class="audit-action">
                                    <?php
                                    echo htmlspecialchars(
                                        $log["action"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $log["description"]
                                    );
                                    ?>
                                </td>

                                <td>
                                    <?php
                                    echo htmlspecialchars(
                                        $log["created_at"]
                                    );
                                    ?>
                                </td>

                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <div class="empty-audit">

                <h3>
                    No Activities Yet
                </h3>

                <p>
                    Your system activities will appear here once they are recorded.
                </p>

            </div>

        <?php endif; ?>

    </div>

</main>

</body>

</html>

<?php

$stmt->close();
$conn->close();

?>