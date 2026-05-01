<?php
// PantryChef admin authentication helper

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function admin_is_logged_in(): bool {
    return !empty($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function require_admin(bool $json = false): void {
    if (admin_is_logged_in()) {
        return;
    }

    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Admin login required.'
        ]);
        exit;
    }

    header('Location: login.php');
    exit;
}
?>
