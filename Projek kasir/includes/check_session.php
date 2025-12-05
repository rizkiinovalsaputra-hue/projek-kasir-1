<?php
// Function untuk check session dengan logging
function checkUserSession($required_role = null) {
    // Log untuk debugging
    error_log("Session check - User ID: " . ($_SESSION['user_id'] ?? 'not set'));
    error_log("Session check - Role: " . ($_SESSION['role'] ?? 'not set'));
    
    if (!isset($_SESSION['user_id'])) {
        error_log("Session check failed - No user_id");
        header('Location: index.php?error=session_expired');
        exit;
    }
    
    if ($required_role && $_SESSION['role'] != $required_role) {
        error_log("Session check failed - Wrong role");
        header('Location: index.php?error=access_denied');
        exit;
    }
    
    return true;
}
?>