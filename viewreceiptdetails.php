
<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

require_once "db.php";

$user_id = (int) $_SESSION["user_id"];

/* CHECK RECEIPT ID */

if (!isset($_GET["id"]) || !ctype_digit((string) $_GET["id"])) {
    http_response_code(400);
    die("Invalid receipt ID. Please select a valid receipt.");
}

$receipt_id = (int) $_GET["id"];

try {

    /* GET RECEIPT */

    $sql = "SELECT *
            FROM receipts
            WHERE id = ?
            AND user_id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param("ii", $receipt_id, $user_id);

    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        http_response_code(404);
        $stmt->close();
        die("Receipt not found or you do not have permission to view it.");
    }

    $receipt = $result->fetch_assoc();

    $stmt->close();

} catch (Throwable $e) {

    error_log("View receipt error: " . $e->getMessage());

    http_response_code(500);

    die("Unable to load receipt details. Check your PHP error log for details.");
}

function safeValue($value) {
    return htmlspecialchars(
        (string) ($value ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Receipt Details | ReceiptSafe Kenya</title>

    <link rel="stylesheet" href="style.css">

    <style>

        .details-page {
            max-width: 1100px;
            margin: 0 auto;
            padding: 35px 25px 60px;
            color: #172033;
        }

        .details-heading {
            margin-bottom: 28px;
        }

        .details-heading .section-label {
            color: #2563eb;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            margin-bottom: 10px;
        }

        .details-heading h1 {
            font-size: 32px;
            margin: 0 0 10px;
            color: #111827;
        }

        .details-heading p:last-child {
            color: #64748b;
            font-size: 14px;
            line-height: 1.7;
        }

        .receipt-details-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 6px 24px rgba(15, 23, 42, 0.05);
        }

        .receipt-details-header {
            padding: 25px 28px;
            background: #f8fbff;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
            flex-wrap: wrap;
        }

        .receipt-details-header h2 {
            margin: 0 0 7px;
            font-size: 21px;
            color: #172033;
        }

        .receipt-details-header p {
            margin: 0;
            color: #64748b;
            font-size: 13px;
        }

        .receipt-status {
            display: inline-block;
            background: #dcfce7;
            color: #166534;
            border-radius: 20px;
            padding: 8px 13px;
            font-size: 12px;
            font-weight: 700;
        }

        .receipt-details-body {
            padding: 28px;
        }

        .receipt-info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0 30px;
        }

        .receipt-info-item {
            min-width: 0;
            padding: 19px 0;
            border-bottom: 1px solid #edf2f7;
        }

        .receipt-info-item.full-width {
            grid-column: 1 / -1;
        }

        .receipt-info-label {
            display: block;
            margin-bottom: 9px;
            color: #64748b;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .receipt-info-value {
            display: block;
            color: #172033;
            font-size: 15px;
            font-weight: 600;
            line-height: 1.7;
            overflow-wrap: anywhere;
            white-space: normal;
        }

        .receipt-info-value.amount {
            color: #0f766e;
            font-size: 23px;
            font-weight: 700;
        }

        .receipt-info-value.description {
            font-weight: 400;
            color: #475569;
        }

        .receipt-details-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 12px;
            padding-top: 27px;
            margin-top: 8px;
        }

        .receipt-details-actions a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 44px;
            padding: 11px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
            transition: background 0.2s, transform 0.2s;
        }

        .receipt-details-actions .edit-link {
            background: #2563eb;
            color: #ffffff;
        }

        .receipt-details-actions .edit-link:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .receipt-details-actions .back-link {
            background: #ffffff;
            color: #334155;
            border: 1px solid #cbd5e1;
        }

        .receipt-details-actions .back-link:hover {
            background: #f8fafc;
        }

        @media (max-width: 650px) {

            .details-page {
                padding: 24px 14px 40px;
            }

            .details-heading h1 {
                font-size: 27px;
            }

            .receipt-details-header,
            .receipt-details-body {
                padding: 20px;
            }

            .receipt-info-grid {
                grid-template-columns: 1fr;
            }

            .receipt-info-item.full-width {
                grid-column: auto;
            }

            .receipt-details-actions {
                flex-direction: column;
                align-items: stretch;
            }

            .receipt-details-actions a {
                width: 100%;
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

        <a href="viewreceipt.php" class="active">Receipts</a>

        <a href="searchreceipt.php">Search</a>

        <a href="reports.php">Reports</a>

        <a href="logout.php">Logout</a>

    </nav>

</header>

<main class="dashboard details-page">

    <div class="details-heading">

        <p class="section-label">
            RECEIPT MANAGEMENT SYSTEM
        </p>

        <h1>Receipt Details</h1>

        <p>
            Review the complete information for your selected receipt.
        </p>

    </div>

    <section class="receipt-details-card">

        <div class="receipt-details-header">

            <div>
                <h2>Receipt Information</h2>

                <p>
                    Receipt No:
                    <?php echo safeValue($receipt["receipt_number"]); ?>
                </p>
            </div>

            <span class="receipt-status">Record Available</span>

        </div>

        <div class="receipt-details-body">

            <div class="receipt-info-grid">

                <div class="receipt-info-item">

                    <span class="receipt-info-label">Receipt Number</span>

                    <span class="receipt-info-value">
                        <?php echo safeValue($receipt["receipt_number"]); ?>
                    </span>

                </div>

                <div class="receipt-info-item">

                    <span class="receipt-info-label">Customer Name</span>

                    <span class="receipt-info-value">
                        <?php echo safeValue($receipt["customer_name"]); ?>
                    </span>

                </div>

                <div class="receipt-info-item">

                    <span class="receipt-info-label">Business Name</span>

                    <span class="receipt-info-value">
                        <?php echo safeValue($receipt["business_name"]); ?>
                    </span>

                </div>

                <div class="receipt-info-item">

                    <span class="receipt-info-label">Amount</span>

                    <span class="receipt-info-value amount">
                        KSh <?php echo number_format((float) $receipt["amount"], 2); ?>
                    </span>

                </div>

                <div class="receipt-info-item">

                    <span class="receipt-info-label">Receipt Date</span>

                    <span class="receipt-info-value">
                        <?php echo safeValue($receipt["receipt_date"]); ?>
                    </span>

                </div>

                <div class="receipt-info-item">

                    <span class="receipt-info-label">Category</span>

                    <span class="receipt-info-value">
                        <?php echo safeValue($receipt["category"]); ?>
                    </span>

                </div>

                <div class="receipt-info-item full-width">

                    <span class="receipt-info-label">Description</span>

                    <span class="receipt-info-value description">
                        <?php
                        if (!empty($receipt["description"])) {
                            echo nl2br(safeValue($receipt["description"]));
                        } else {
                            echo "No description provided.";
                        }
                        ?>
                    </span>

                </div>

                <div class="receipt-info-item full-width">

                    <span class="receipt-info-label">Record Created</span>

                    <span class="receipt-info-value">
                        <?php echo safeValue($receipt["created_at"]); ?>
                    </span>

                </div>

            </div>

            <div class="receipt-details-actions">

                <a
                    href="editreceipt.php?id=<?php echo (int) $receipt["id"]; ?>"
                    class="edit-link"
                >
                    Edit Receipt
                </a>

                <a href="viewreceipt.php" class="back-link">
                    Back to Receipts
                </a>

            </div>

        </div>

    </section>

</main>

</body>

</html>

<?php
$conn->close();
?>
