<?php
require_once "auth_check.php";
require_once "db.php";

// Check whether the student has completed authentication.
if (
    !isset($_SESSION["authenticated"]) ||
    $_SESSION["authenticated"] !== true ||
    !isset($_SESSION["student_id"])
) {
    header("Location: login.php");
    exit;
}

// Basic session inactivity timeout: 15 minutes.
$timeout = 900;

if (
    isset($_SESSION["last_activity"]) &&
    time() - $_SESSION["last_activity"] > $timeout
) {
    $_SESSION = [];
    session_destroy();

    header("Location: login.php");
    exit;
}

$_SESSION["last_activity"] = time();

$studentId = $_SESSION["student_id"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Student Dashboard</title>
</head>

<body>

    <h1>Welcome to the Hostel System</h1>

    <p>
        You are logged in as student:
        <?= htmlspecialchars($studentId, ENT_QUOTES, "UTF-8") ?>
    </p>

    <p>Your authentication was successful.</p>

    <h3>Student Features</h3>

    <ul>
    <li><a href="rooms.php">View Available Rooms</a></li>
    <li><a href="apply.php">Apply for Hostel Room</a></li>
    <li><a href="allocation_result.php">Check Application Status</a></li>
    </ul>

    <nav>
        <a href="rooms.php">Search Hostel Rooms</a>
        <br><br>

        <a href="apply.php">Apply for Hostel</a>
        <br><br>

        <a href="allocation_result.php">View Allocation Result</a>
        <br><br>

        <a href="logout.php">Log Out</a>
    </nav>

</body>
</html>