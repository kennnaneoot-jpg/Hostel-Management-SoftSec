<?php
require_once "db.php";

$studentId = "STU001";
$fullName = "Test Student";
$email = "student@example.com";
$password = "Student@123";

// Generate a secure password hash.
$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

try {
    $stmt = $conn->prepare(
        "INSERT INTO students
        (student_id, full_name, email, password_hash, role)
        VALUES (?, ?, ?, ?, 'student')"
    );

    $stmt->bind_param(
        "ssss",
        $studentId,
        $fullName,
        $email,
        $passwordHash
    );

    $stmt->execute();

    echo "Test student created successfully.";

} catch (mysqli_sql_exception $e) {
    error_log("Test student creation failed: " . $e->getMessage());

    http_response_code(500);
    echo "Could not create the test student. Check the server logs.";
}