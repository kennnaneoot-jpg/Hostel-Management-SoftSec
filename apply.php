
<?php
require_once "auth_check.php";

require_once "csrf.php";
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
$error = "";
$success = "";

// Get selected room
$room_id = filter_input(INPUT_GET, 'room_id', FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $room_id = filter_input(INPUT_POST, 'room_id', FILTER_VALIDATE_INT);

    if (!$room_id) {
        $error = "Invalid room selection.";
    } else {
        // Check whether student already applied
        $check = $conn->prepare(
            "SELECT id FROM applications WHERE student_id = ?"
        );
        $check->bind_param("s", $student_id);
        $check->execute();
        $existing = $check->get_result();

        if ($existing->num_rows > 0) {
            $error = "You have already submitted an application.";
        } else {
            // Check room availability
            $room_check = $conn->prepare(
                "SELECT id FROM rooms
                 WHERE id = ?
                 AND is_available = 1
                 AND occupied < capacity"
            );

            $room_check->bind_param("i", $room_id);
            $room_check->execute();
            $available = $room_check->get_result();

            if ($available->num_rows === 0) {
                $error = "This room is no longer available.";
            } else {
                // Submit application
                $insert = $conn->prepare(
                    "INSERT INTO applications (student_id, room_id)
                     VALUES (?, ?)"
                );

                $insert->bind_param("si", $student_id, $room_id);

                if ($insert->execute()) {
                    header("Location: allocation_result.php");
                    exit();
                } else {
                    $error = "Application could not be submitted.";
                }
            }
        }
    }
}

// Retrieve selected room information
$room = null;

if ($room_id) {
    $stmt = $conn->prepare(
        "SELECT * FROM rooms
         WHERE id = ?
         AND is_available = 1
         AND occupied < capacity"
    );

    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $room = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Apply for Hostel Room</title>
</head>
<body>

<h2>Hostel Room Application</h2>

<a href="rooms.php">Back to Available Rooms</a> |
<a href="dashboard.php">Dashboard</a>

<hr>

<?php if ($error !== ""): ?>
    <p style="color:red;">
        <?= htmlspecialchars($error) ?>
    </p>
<?php endif; ?>

<?php if ($room): ?>

    <h3>Selected Room</h3>

    <p>Room Number:
        <?= htmlspecialchars($room['room_number']) ?>
    </p>

    <p>Block:
        <?= htmlspecialchars($room['block_name']) ?>
    </p>

    <p>Room Type:
        <?= htmlspecialchars($room['room_type']) ?>
    </p>

    <form method="POST">
        <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"
        >

        <input type="hidden"
               name="room_id"
               value="<?= (int)$room['id'] ?>">

        <p>
            Are you sure you want to apply for this room?
        </p>

        <button type="submit">Submit Application</button>

    </form>

<?php else: ?>

    <p>Selected room is invalid or unavailable.</p>

<?php endif; ?>

</body>
</html>
