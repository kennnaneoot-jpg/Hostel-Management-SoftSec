
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

    $application_id = filter_input(
        INPUT_POST,
        'application_id',
        FILTER_VALIDATE_INT
    );

    $action = $_POST['action'] ?? '';

    if (
        !$application_id ||
        !in_array($action, ['Approved', 'Rejected'], true)
    ) {
        $error = "Invalid application request.";
    } else {

        // Start transaction to keep both updates consistent
        $conn->begin_transaction();

        try {

            // Lock application record
            $stmt = $conn->prepare(
                "SELECT room_id, status
                 FROM applications
                 WHERE id = ?
                 FOR UPDATE"
            );

            $stmt->bind_param("i", $application_id);
            $stmt->execute();

            $application = $stmt->get_result()->fetch_assoc();

            if (!$application) {
                throw new Exception("Application not found.");
            }

            if ($application['status'] !== 'Pending') {
                throw new Exception("This application has already been processed.");
            }

            $room_id = (int)$application['room_id'];

            if ($action === 'Approved') {

                // Lock room and check available capacity
                $room_stmt = $conn->prepare(
                    "SELECT capacity, occupied
                     FROM rooms
                     WHERE id = ?
                     AND is_available = 1
                     FOR UPDATE"
                );

                $room_stmt->bind_param("i", $room_id);
                $room_stmt->execute();

                $room = $room_stmt->get_result()->fetch_assoc();

                if (
                    !$room ||
                    (int)$room['occupied'] >= (int)$room['capacity']
                ) {
                    throw new Exception("Room is full or unavailable.");
                }

                // Increase room occupancy
                $update_room = $conn->prepare(
                    "UPDATE rooms
                     SET occupied = occupied + 1
                     WHERE id = ?
                     AND occupied < capacity
                     AND is_available = 1"
                );

                $update_room->bind_param("i", $room_id);
                $update_room->execute();

                if ($update_room->affected_rows !== 1) {
                    throw new Exception("Room availability changed. Please try again.");
                }
            }

            // Update application status
            $update_app = $conn->prepare(
                "UPDATE applications
                 SET status = ?
                 WHERE id = ? AND status = 'Pending'"
            );

            $update_app->bind_param(
                "si",
                $action,
                $application_id
            );

            $update_app->execute();

            if ($update_app->affected_rows !== 1) {
                throw new Exception("Application could not be updated.");
            }

            $conn->commit();

            $success = "Application successfully " .
                strtolower($action) . ".";

        } catch (Throwable $e) {

            $conn->rollback();

            error_log($e->getMessage());

            $error = "Unable to process application. Please check its status and room availability.";
        }
    }
}

// Retrieve all applications
$sql = "SELECT
            applications.id,
            applications.student_id,
            applications.application_date,
            applications.status,
            rooms.room_number,
            rooms.block_name,
            rooms.room_type
        FROM applications
        INNER JOIN rooms
        ON applications.room_id = rooms.id
        ORDER BY applications.application_date DESC";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Applications</title>
</head>
<body>

<h2>Manage Student Applications</h2>

<a href="admin_dashboard.php">Back to Dashboard</a>

<hr>

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

<table border="1" cellpadding="10">

<tr>
    <th>Application ID</th>
    <th>Student ID</th>
    <th>Room</th>
    <th>Block</th>
    <th>Room Type</th>
    <th>Date</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php while ($app = $result->fetch_assoc()): ?>

<tr>

    <td><?= (int)$app['id'] ?></td>

    <td><?= htmlspecialchars($app['student_id'], ENT_QUOTES, "UTF-8") ?></td>

    <td><?= htmlspecialchars($app['room_number'], ENT_QUOTES, "UTF-8") ?></td>

    <td><?= htmlspecialchars($app['block_name'], ENT_QUOTES, "UTF-8") ?></td>

    <td><?= htmlspecialchars($app['room_type'], ENT_QUOTES, "UTF-8") ?></td>

    <td><?= htmlspecialchars($app['application_date'], ENT_QUOTES, "UTF-8") ?></td>

    <td><?= htmlspecialchars($app['status'], ENT_QUOTES, "UTF-8") ?></td>

    <td>

    <?php if ($app['status'] === 'Pending'): ?>

        <form method="POST">
            <input
            type="hidden"
            name="csrf_token"
            value="<?= htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES, "UTF-8") ?>"
            >

            <input type="hidden"
                   name="application_id"
                   value="<?= (int)$app['id'] ?>">

            <button type="submit"
                    name="action"
                    value="Approved">
                Approve
            </button>

            <button type="submit"
                    name="action"
                    value="Rejected">
                Reject
            </button>

        </form>

    <?php else: ?>

        Already processed

    <?php endif; ?>

    </td>

</tr>

<?php endwhile; ?>

</table>

</body>
</html>
