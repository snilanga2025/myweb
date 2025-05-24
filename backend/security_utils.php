<?php
// security_utils.php

if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Ensure session is started
}

/**
 * Generates a CSRF token and stores it in the session.
 * If a token already exists, it returns the existing one unless forced to regenerate.
 * @param bool $force_regenerate If true, a new token will be generated even if one exists.
 * @return string The CSRF token.
 */
function generate_csrf_token($force_regenerate = false) {
    if ($force_regenerate || !isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validates a CSRF token against the one stored in the session.
 * @param string $token The token from the form submission.
 * @return bool True if the token is valid, false otherwise.
 */
function validate_csrf_token($token) {
    if (isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token)) {
        // Token is valid, consume it by removing it or regenerating it (optional, depends on strategy)
        // For simplicity, we can regenerate it after successful validation of a POST request.
        // unset($_SESSION['csrf_token']); // Or regenerate: generate_csrf_token(true);
        return true;
    }
    return false;
}

/**
 * Outputs a hidden input field with the CSRF token.
 * Call this function inside your HTML forms.
 */
function csrf_input_field() {
    $token = generate_csrf_token();
    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>
