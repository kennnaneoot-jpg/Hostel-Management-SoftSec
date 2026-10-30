
<?php
require_once "auth_check.php";

if (($_SESSION["role"] ?? "") !== "admin") {
    http_response_code(403);
    exit("Access denied.");
}
require_once "csrf.php";
require_once 'db.php';

if (
    !isset($_SESSION['authenticated']) ||
    $_SESSION['authenticated'] !== true ||
    ($_SESSION['role'] ?? '') !== 'admin'
) {
    header("Location: login.php");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();

    $room_number = trim($_POST['room_number'] ?? '');
    $block_name = trim($_POST['block_name'] ?? '');
    $room_type = trim($_POST['room_type'] ?? '');
    $capacity = filter_input(INPUT_POST, 'capacity', FILTER_VALIDATE_INT);
    $facilities = trim($_POST['facilities'] ?? '');

    if (
        $room_number === '' ||
        $block_name === '' ||
        $room_type === '' ||
        $facilities === '' ||
        !$capacity ||
        $capacity < 1 ||
        $capacity > 10 ||
        strlen($room_number) > 20 ||
        strlen($block_name) > 50 ||
        strlen($room_type) > 50
    ) {
        $error = "Please enter valid room information.";
    } else {

        $stmt = $conn->prepare(
            "INSERT INTO rooms
            (room_number, block_name, room_type,
             capacity, occupied, facilities)
            VALUES (?, ?, ?, ?, 0, ?)"
        );

        $stmt->bind_param(
            "sssis",
            $room_number,
            $block_name,
            $room_type,
            $capacity,
            $facilities
        );

        if ($stmt->execute()) {
            $success = "Room added successfully.";
        } else {
            $error = "Room could not be added. Check whether the room number already exists.";
        }
    }
}

$result = $conn->query(
    "SELECT * FROM rooms ORDER BY block_name, room_number"
);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Rooms</title>
</head>
<body>

<h2>Manage Hostel Rooms</h2>

<a href="admin_dashboard.php">Back to Dashboard</a>

<hr>

<h3>Add New Room</h3>

<?php if ($error): ?>
    <p style="color:red;">
        <?= htmlspecialchars($error) ?>
    </p>
<?php endif; ?>

<?php if ($success): ?>
    <p style="color:green;">
        <?= htmlspecialchars($success) ?>
    </p>
<?php endif; ?>

<form method="POST">
    <input
    type="hidden"
    name="csrf_token"
    value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"
    >

    <p>Room Number:
        <input type="text" name="room_number" maxlength="20" required>
    </p>

    <p>Block:
        <input type="text" name="block_name" maxlength="50" required>
    </p>

    <p>Room Type:
        <input type="text" name="room_type" maxlength="50" required>
    </p>

    <p>Capacity:
        <input type="number" name="capacity" min="1" max="10" required>
    </p>

    <p>Facilities:
        <input type="text" name="facilities" required>
    </p>

    <button type="submit">Add Room</button>

</form>

<hr>

<h3>Existing Rooms</h3>

<table border="1" cellpadding="10">

<tr>
    <th>Room</th>
    <th>Block</th>
    <th>Type</th>
    <th>Capacity</th>
    <th>Occupied</th>
    <th>Available</th>
</tr>

<?php while ($room = $result->fetch_assoc()): ?>

<tr>
    <td><?= htmlspecialchars($room['room_number'], ENT_QUOTES, "UTF-8") ?></td>
    <td><?= htmlspecialchars($room['block_name'], ENT_QUOTES, "UTF-8") ?></td>
    <td><?= htmlspecialchars($room['room_type'], ENT_QUOTES, "UTF-8") ?></td>
    <td><?= (int)$room['capacity'] ?></td>
    <td><?= (int)$room['occupied'] ?></td>
    <td><?= (int)$room['capacity'] - (int)$room['occupied'] ?></td>
</tr>

<?php endwhile; ?>

</table>

</body>
</html>
