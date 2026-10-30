
<?php
require_once 'db.php';

$student_id = 'ADMIN001';
$name = 'System Administrator';
$email = 'admin@example.com';
$password = 'Admin@123';

$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare(
    "INSERT INTO students
    (student_id, full_name, email, password_hash, role)
    VALUES (?, ?, ?, ?, 'admin')"
);

$stmt->bind_param(
    "ssss",
    $student_id,
    $name,
    $email,
    $hash
);

if ($stmt->execute()) {
    echo "Admin account created successfully.";
} else {
    echo "Admin account could not be created.";
}
?>
