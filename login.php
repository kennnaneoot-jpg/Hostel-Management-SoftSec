<?php
session_start();

require_once "db.php";

// Redirect users who have already completed login.
if (isset($_SESSION["student_id"])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $studentId = trim($_POST["student_id"] ?? "");
    $password = $_POST["password"] ?? "";

    // Syntactical validation.
    if (
        $studentId === "" ||
        $password === "" ||
        strlen($studentId) > 20 ||
        strlen($password) > 1024
    ) {
        $error = "Please enter valid login details.";

    } else {

        try {
            // Retrieve the student using a prepared statement.
            $stmt = $conn->prepare(
                "SELECT id, student_id, password_hash, role, is_active
                 FROM students
                 WHERE student_id = ?
                 LIMIT 1"
            );

            $stmt->bind_param("s", $studentId);
            $stmt->execute();

            $result = $stmt->get_result();
            $student = $result->fetch_assoc();

            $validPassword = (
                $student &&
                password_verify($password, $student["password_hash"])
            );

            $validAccount = (
                $student &&
                (int)$student["is_active"] === 1
            );

            // Record the result of the password attempt.
            $success = (
                $validPassword && $validAccount
            ) ? 1 : 0;

            $logStmt = $conn->prepare(
                "INSERT INTO login_attempts
                (student_id, success)
                VALUES (?, ?)"
            );

            $logStmt->bind_param(
                "si",
                $studentId,
                $success
            );

            $logStmt->execute();

            if ($validPassword && $validAccount) {

                // Regenerate the session ID.
                session_regenerate_id(true);

                /*
                 * Temporary development stage:
                 * MFA must be completed before we create
                 * the final authenticated session.
                 *
                 * We will add the OTP flow next.
                 */
                $_SESSION["pending_student_id"] =
                    (int)$student["id"];

                $_SESSION["pending_student_code"] =
                    $student["student_id"];

                $_SESSION["pending_role"] =
                    $student["role"];

                header("Location: verify_otp.php");
                exit;

            } else {
                $error = "Invalid student ID or password.";
            }

        } catch (mysqli_sql_exception $e) {

            error_log("Login processing failed: " . $e->getMessage());

            http_response_code(500);
            $error = "The system is temporarily unavailable.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Login | Secured Hostel System</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f1f5f9;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .login-box {
            background: white;
            padding: 30px;
            width: 340px;
            border-radius: 12px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #1e3a8a;
        }

        label {
            display: block;
            margin-top: 15px;
        }

        input {
            box-sizing: border-box;
            width: 100%;
            padding: 11px;
            margin-top: 6px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 22px;
            border: none;
            border-radius: 6px;
            background: #1e40af;
            color: white;
            cursor: pointer;
        }

        .error {
            color: #b91c1c;
            text-align: center;
        }
    </style>
</head>

<body>

<div class="login-box">

    <h2>Student Login</h2>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?= htmlspecialchars($error, ENT_QUOTES, "UTF-8") ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="login.php">

        <label for="student_id">Student ID</label>

        <input
            type="text"
            id="student_id"
            name="student_id"
            maxlength="20"
            autocomplete="username"
            required
        >

        <label for="password">Password</label>

        <input
            type="password"
            id="password"
            name="password"
            maxlength="1024"
            autocomplete="current-password"
            required
        >

        <button type="submit">Log In</button>

    </form>

</div>

</body>
</html>