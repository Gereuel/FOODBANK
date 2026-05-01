<?php
session_start();

$_SESSION = array();

// Destroy the session cookie on the browser side
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Completely session
session_destroy();

// Redirect the user back to the login page
header("Location: ../../../login.php?status=logged_out");
exit();
?>