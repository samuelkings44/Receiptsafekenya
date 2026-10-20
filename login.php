<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

require_once "db.php";
require_once "audit.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    $stmt = $conn->prepare(
        "SELECT id, full_name, email, password
         FROM users
         WHERE email = ?
         LIMIT 1"
    );

    if (!$stmt) {
        die("Database error: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 1) {

        $user = $result->fetch_assoc();

        if (password_verify($password, $user["password"])) {

            session_regenerate_id(true);

            $_SESSION["user_id"] = $user["id"];
            $_SESSION["full_name"] = $user["full_name"];
            $_SESSION["email"] = $user["email"];

            // Record successful login in the audit trail
            logAudit(
                $conn,
                $user["id"],
                "LOGIN",
                "User logged into the system"
            );

            header("Location: dashboard.php");
            exit();

        } else {

            $error = "Incorrect email or password.";

        }

    } else {

        $error = "Incorrect email or password.";

    }

    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login | ReceiptSafe Kenya</title>

    <link rel="stylesheet" href="style.css">

</head>

<body class="login-page">

<div class="login-container">

    <div class="login-card">

        <div class="login-header">

            <a href="index.php" class="login-logo">
                ReceiptSafe <span>Kenya</span>
            </a>

            <h1>Welcome Back</h1>

            <p>
                Sign in to manage your receipts securely.
            </p>

        </div>

        <?php if ($error !== ""): ?>

            <div class="error-message">
                <?php echo htmlspecialchars($error); ?>
            </div>

        <?php endif; ?>

        <form action="login.php" method="POST">

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email address"
                    required
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <input
                    type="password"
                    id="password"
                    name="password"
                    placeholder="Enter your password"
                    required
                >

            </div>

            <div class="remember-me">

                <label>
                    <input type="checkbox" name="remember">
                    Remember me
                </label>

            </div>

            <button type="submit" class="login-btn">
                Sign In
            </button>

        </form>

        <div class="login-footer">

            <p>
                Don't have an account?
                <a href="register.php">Create Account</a>
            </p>

            <a href="index.php" class="back-home">
                ← Back to Home
            </a>

        </div>

    </div>

</div>

</body>

</html>