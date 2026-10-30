
<?php
require_once "auth_check.php";

if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}

require_once 'db.php';

// Admin access control
if (
    !isset($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit();
}

// Count rooms
$room_count = $conn->query(
    "SELECT COUNT(*) AS total FROM rooms"
)->fetch_assoc()['total'];

// Count applications
$app_count = $conn->query(
    "SELECT COUNT(*) AS total FROM applications"
)->fetch_assoc()['total'];

// Count pending applications
$pending_count = $conn->query(
    "SELECT COUNT(*) AS total
     FROM applications
     WHERE status = 'Pending'"
)->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
</head>
<body>

<h2>Hostel Admin Dashboard</h2>

<p>
    Welcome, Administrator:
    <?= htmlspecialchars($_SESSION['student_id'], ENT_QUOTES, "UTF-8") ?>
</p>

<hr>

<h3>System Overview</h3>

<p>Total Rooms: <?= (int)$room_count ?></p>
<p>Total Applications: <?= (int)$app_count ?></p>
<p>Pending Applications: <?= (int)$pending_count ?></p>

<hr>

<h3>Admin Functions</h3>

<ul>
    <li><a href="manage_rooms.php">Manage Hostel Rooms</a></li>
    <li><a href="manage_applications.php">Manage Student Applications</a></li>
    <li><a href="logout.php">Logout</a></li>
</ul>

</body>
</html>
