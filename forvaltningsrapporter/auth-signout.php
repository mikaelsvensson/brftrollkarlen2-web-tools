<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$referrer = $_SERVER['HTTP_REFERER'];

// Initialize the session.
// If you are using session_name("something"), don't forget it now!
session_start();

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();
$thisFileName = substr(__FILE__, strlen(__DIR__));

if (strpos($referrer, $thisFileName) === false) {
    header("Location: " . $referrer);
} else {
    echo "Utloggad";
}
?>