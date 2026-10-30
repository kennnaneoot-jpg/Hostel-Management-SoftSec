
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

// Retrieve available rooms
$sql = "SELECT * FROM rooms
        WHERE is_available = 1
        AND occupied < capacity
        ORDER BY block_name, room_number";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Available Hostel Rooms</title>
</head>
<body>

<h2>Available Hostel Rooms</h2>

<p>Welcome, <?= htmlspecialchars($_SESSION['student_id']) ?></p>

<a href="dashboard.php">Dashboard</a> |
<a href="allocation_result.php">My Application</a> |
<a href="logout.php">Logout</a>

<hr>

<?php if ($result && $result->num_rows > 0): ?>

    <table border="1" cellpadding="10">
        <tr>
            <th>Room</th>
            <th>Block</th>
            <th>Room Type</th>
            <th>Capacity</th>
            <th>Available Spaces</th>
            <th>Facilities</th>
            <th>Action</th>
        </tr>

        <?php while ($room = $result->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($room['room_number'], ENT_QUOTES, "UTF-8") ?></td>
            <td><?= htmlspecialchars($room['block_name'], ENT_QUOTES, "UTF-8") ?></td>
            <td><?= htmlspecialchars($room['room_type'], ENT_QUOTES, "UTF-8") ?></td>
            <td><?= (int)$room['capacity'] ?></td>
            <td><?= (int)$room['capacity'] - (int)$room['occupied'] ?></td>
            <td><?= htmlspecialchars($room['facilities'], ENT_QUOTES, "UTF-8") ?></td>
            <td>
                <a href="apply.php?room_id=<?= (int)$room['id'] ?>">
                    Apply for Room
                </a>
            </td>
        </tr>
        <?php endwhile; ?>

    </table>

<?php else: ?>

    <p>No available rooms at the moment.</p>

<?php endif; ?>

</body>
</html>
