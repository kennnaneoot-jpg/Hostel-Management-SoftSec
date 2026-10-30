
<?php
require_once "auth_check.php";

require_once 'db.php';

// Check login
if (
    !isset($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true
) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Retrieve student's application
$sql = "SELECT
            applications.application_date,
            applications.status,
            rooms.room_number,
            rooms.block_name,
            rooms.room_type
        FROM applications
        INNER JOIN rooms
        ON applications.room_id = rooms.id
        WHERE applications.student_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_id);
$stmt->execute();

$result = $stmt->get_result();
$application = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Application Status</title>
</head>
<body>

<h2>My Hostel Application</h2>

<a href="dashboard.php">Dashboard</a> |
<a href="rooms.php">View Rooms</a> |
<a href="logout.php">Logout</a>

<hr>

<?php if ($application): ?>

    <h3>Application Details</h3>

    <p>Student ID:
        <?= htmlspecialchars($student_id) ?>
    </p>

    <p>Room Number:
        <?= htmlspecialchars($application['room_number'], ENT_QUOTES, "UTF-8") ?>
    </p>

    <p>Block:
        <?= htmlspecialchars($application['block_name'], ENT_QUOTES, "UTF-8") ?>
    </p>

    <p>Room Type:
        <?= htmlspecialchars($application['room_type'], ENT_QUOTES, "UTF-8") ?>
    </p>

    <p>Application Date:
        <?= htmlspecialchars($application['application_date'], ENT_QUOTES, "UTF-8") ?>
    </p>

    <p>Application Status:
        <strong>
            <?= htmlspecialchars($application['status'], ENT_QUOTES, "UTF-8") ?>
        </strong>
    </p>

    <?php if ($application['status'] === 'Pending'): ?>
        <p>Your application is waiting for administrator approval.</p>
    <?php elseif ($application['status'] === 'Approved'): ?>
        <p>Your hostel application has been approved!</p>
    <?php elseif ($application['status'] === 'Rejected'): ?>
        <p>Your hostel application was rejected.</p>
    <?php endif; ?>

<?php else: ?>

    <p>You have not submitted a hostel application yet.</p>

    <a href="rooms.php">Browse Available Rooms</a>

<?php endif; ?>

</body>
</html>
