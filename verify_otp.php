
<?php
session_start();

require_once "db.php";

// Check whether password verification was completed.
if (
    !isset($_SESSION["pending_student_id"]) ||
    !isset($_SESSION["pending_student_code"]) ||
    !isset($_SESSION["pending_role"])
) {
    header("Location: login.php");
    exit;
}

// Retrieve temporary login information.
$student_id = $_SESSION["pending_student_code"];
$student_db_id = $_SESSION["pending_student_id"];

// Generate OTP when verification starts.
if (!isset($_SESSION["otp_hash"])) {

    $otp = (string) random_int(100000, 999999);

    $_SESSION["otp_hash"] = password_hash(
        $otp,
        PASSWORD_DEFAULT
    );

    $_SESSION["otp_expiry"] = time() + 300;
    $_SESSION["otp_attempts"] = 0;

    // DEMONSTRATION ONLY: Display OTP instead of emailing it.
    $_SESSION["demo_otp"] = $otp;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Check maximum OTP attempts.
    if ($_SESSION["otp_attempts"] >= 5) {

        $error = "Too many attempts. Please log in again.";

        unset(
            $_SESSION["pending_student_id"],
            $_SESSION["pending_student_code"],
            $_SESSION["pending_role"],
            $_SESSION["otp_hash"],
            $_SESSION["otp_expiry"],
            $_SESSION["otp_attempts"],
            $_SESSION["demo_otp"]
        );

    // Check OTP expiry.
    } elseif (time() > $_SESSION["otp_expiry"]) {

        $error = "OTP has expired. Please log in again.";

        unset(
            $_SESSION["pending_student_id"],
            $_SESSION["pending_student_code"],
            $_SESSION["pending_role"],
            $_SESSION["otp_hash"],
            $_SESSION["otp_expiry"],
            $_SESSION["otp_attempts"],
            $_SESSION["demo_otp"]
        );

    } else {

        $entered_otp = trim($_POST["otp"] ?? "");

        // Count each verification attempt.
        $_SESSION["otp_attempts"]++;

        // Verify OTP format and hash.
        if (
            preg_match('/^\d{6}$/', $entered_otp) &&
            password_verify(
                $entered_otp,
                $_SESSION["otp_hash"]
            )
        ) {

            // Verify account again using the database.
            $stmt = $conn->prepare(
                "SELECT student_id, role
                 FROM students
                 WHERE id = ?
                 AND student_id = ?
                 AND is_active = 1
                 LIMIT 1"
            );

            $stmt->bind_param(
                "is",
                $student_db_id,
                $student_id
            );

            $stmt->execute();

            $user = $stmt->get_result()->fetch_assoc();

            if (!$user) {

                error_log("OTP account verification failed.");

                session_unset();
                session_destroy();

                header("Location: login.php");
                exit;

            }

            // Complete authentication.
            session_regenerate_id(true);

            $_SESSION["student_id"] = $user["student_id"];
            $_SESSION["role"] = $user["role"];
            $_SESSION["authenticated"] = true;
            $_SESSION["last_activity"] = time();

            // Remove temporary login and OTP information.
            unset(
                $_SESSION["pending_student_id"],
                $_SESSION["pending_student_code"],
                $_SESSION["pending_role"],
                $_SESSION["otp_hash"],
                $_SESSION["otp_expiry"],
                $_SESSION["otp_attempts"],
                $_SESSION["demo_otp"]
            );

            // Redirect according to account role.
            if ($_SESSION["role"] === "admin") {

                header("Location: admin_dashboard.php");

            } else {

                header("Location: dashboard.php");

            }

            exit;

        } else {

            $error = "Incorrect OTP. Please try again.";

        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>OTP Verification</title>
</head>

<body>

    <h2>OTP Verification</h2>

    <?php if (isset($_SESSION["demo_otp"])): ?>

        <p>
            Demonstration OTP:
            <strong>
                <?= htmlspecialchars(
                    $_SESSION["demo_otp"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </strong>
        </p>

        <p>This OTP is for assignment testing only.</p>

    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <p style="color: red;">
            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                "UTF-8"
            ) ?>
        </p>

        <?php if (!isset($_SESSION["pending_student_id"])): ?>

            <p>
                <a href="login.php">Return to login</a>
            </p>

        <?php endif; ?>

    <?php endif; ?>

    <?php if (isset($_SESSION["pending_student_id"])): ?>

        <form method="POST" action="verify_otp.php">

            <label for="otp">
                Enter your 6-digit OTP:
            </label>

            <input
                type="text"
                id="otp"
                name="otp"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                required
            >

            <button type="submit">
                Verify OTP
            </button>

        </form>

    <?php endif; ?>

</body>

</html>
