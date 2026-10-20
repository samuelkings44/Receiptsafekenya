
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
$search = "";

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);
}

/* SEARCH RECEIPTS */

if ($search !== "") {

    $sql = "SELECT *
            FROM receipts
            WHERE user_id = ?
            AND (
                receipt_number LIKE ?
                OR customer_name LIKE ?
                OR business_name LIKE ?
                OR category LIKE ?
            )
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $searchTerm = "%" . $search . "%";

    $stmt->bind_param(
        "issss",
        $user_id,
        $searchTerm,
        $searchTerm,
        $searchTerm,
        $searchTerm
    );

} else {

    $sql = "SELECT *
            FROM receipts
            WHERE user_id = ?
            ORDER BY created_at DESC";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Search Receipts | ReceiptSafe Kenya</title>

    <link rel="stylesheet" href="style.css">

    <style>
        /* Search Receipts page only */

        .search-page {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 35px 24px 60px;
            box-sizing: border-box;
        }

        .search-page .dashboard-header {
            margin-bottom: 25px;
        }

        .search-page .action-card {
            width: 100%;
            max-width: 100%;
            min-width: 0;
            box-sizing: border-box;
            padding: 25px 30px;
            margin-bottom: 22px;
            overflow: hidden;
        }

        .search-form {
            display: flex;
            align-items: center;
            gap: 12px;
            width: 100%;
        }

        .search-form input {
            flex: 1;
            min-width: 0;
            width: auto !important;
            padding: 14px 16px !important;
            border: 1px solid #cbd5e1;
            border-radius: 7px;
            font-size: 15px;
            box-sizing: border-box;
            outline: none;
        }

        .search-form input:focus {
            border-color: #2563eb;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
        }

        .search-form .primary-btn {
            padding: 14px 24px;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            white-space: nowrap;
        }

        .clear-search {
            color: #2563eb;
            text-decoration: none;
            padding: 10px 5px;
            white-space: nowrap;
        }

        .clear-search:hover {
            text-decoration: underline;
        }

        .results-count {
            color: #64748b;
            margin: 10px 0 20px;
        }

        .search-table-wrapper {
            width: 100%;
            overflow-x: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
        }

        .search-results-table {
            width: 100%;
            min-width: 760px;
            border-collapse: collapse;
            text-align: left;
        }

        .search-results-table th,
        .search-results-table td {
            padding: 16px 20px;
            text-align: left;
            vertical-align: middle;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .search-results-table th {
            background: #f8fafc;
            color: #334155;
            font-size: 14px;
            font-weight: 600;
        }

        .search-results-table td {
            color: #475569;
            font-size: 14px;
        }

        .search-results-table tbody tr:last-child td {
            border-bottom: none;
        }

        .search-results-table tbody tr:hover {
            background: #f8fafc;
        }

        .receipt-number {
            color: #1d4ed8 !important;
            font-weight: 600;
        }

        .receipt-amount {
            font-weight: 600;
            color: #0f172a !important;
        }

        .no-results {
            padding: 25px 0;
            color: #64748b;
        }

        @media (max-width: 600px) {
            .search-page {
                padding: 24px 14px 40px;
            }

            .search-page .action-card {
                padding: 18px 14px;
            }

            .search-form {
                flex-wrap: wrap;
            }

            .search-form input {
                flex-basis: 100%;
            }

            .search-form .primary-btn {
                flex: 1;
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
        <a href="searchreceipt.php" class="active">Search</a>
        <a href="reports.php">Reports</a>
        <a href="audittrail.php">Audit Trail</a>
        <a href="logout.php">Logout</a>
    </nav>

</header>

<main class="dashboard search-page">

    <div class="dashboard-header">
        <div>
            <p class="section-label">RECEIPT MANAGEMENT SYSTEM</p>
            <h1>Search Receipts</h1>
            <p>Find stored receipts quickly using receipt details.</p>
        </div>
    </div>

    <!-- SEARCH FORM -->

    <div class="action-card">

        <form method="GET" action="searchreceipt.php" class="search-form">

            <input
                type="text"
                name="search"
                placeholder="Search receipt number, customer, business or category..."
                value="<?php echo htmlspecialchars($search, ENT_QUOTES, 'UTF-8'); ?>"
            >

            <button type="submit" class="primary-btn">
                Search
            </button>

            <?php if ($search !== ""): ?>
                <a href="searchreceipt.php" class="clear-search">Clear</a>
            <?php endif; ?>

        </form>

    </div>

    <!-- SEARCH RESULTS -->

    <div class="action-card">

        <h2>
            <?php echo $search !== "" ? "Search Results" : "All Receipts"; ?>
        </h2>

        <?php if ($result->num_rows > 0): ?>

            <p class="results-count">
                <?php echo $result->num_rows; ?>
                receipt(s) found.
            </p>

            <div class="search-table-wrapper">

                <table class="search-results-table">

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

                        <?php while ($row = $result->fetch_assoc()): ?>

                            <tr>
                                <td class="receipt-number">
                                    <?php echo htmlspecialchars($row["receipt_number"], ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["customer_name"], ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["business_name"], ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <td class="receipt-amount">
                                    KSh <?php echo number_format((float) $row["amount"], 2); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["receipt_date"], ENT_QUOTES, 'UTF-8'); ?>
                                </td>

                                <td>
                                    <?php echo htmlspecialchars($row["category"], ENT_QUOTES, 'UTF-8'); ?>
                                </td>
                            </tr>

                        <?php endwhile; ?>

                    </tbody>

                </table>

            </div>

        <?php else: ?>

            <p class="no-results">
                No receipts found. Try another receipt number, customer, business or category.
            </p>

        <?php endif; ?>

    </div>

</main>

</body>
</html>
```