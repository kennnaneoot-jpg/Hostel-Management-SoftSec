<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (
    !isset($_SESSION["authenticated"]) ||
    $_SESSION["authenticated"] !== true
) {
    header("Location: login.php");
    exit;
}

// Session timeout: 15 minutes.
if (
    isset($_SESSION["last_activity"]) &&
    time() - $_SESSION["last_activity"] > 900
) {
    session_unset();
    session_destroy();

    header("Location: login.php");
    exit;
}

$_SESSION["last_activity"] = time();
?>