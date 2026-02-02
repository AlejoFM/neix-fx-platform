<?php

/**
 * Helper de autenticación para vistas PHP.
 * Requiere que la sesión esté iniciada.
 */

function getCurrentUser(): ?array
{
    if (empty($_SESSION['user_id'])) {
        return null;
    }
    return [
        'id' => (int) $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
    ];
}

function requireAuth(): void
{
    if (getCurrentUser() === null) {
        header('Location: /login');
        exit;
    }
}

function requireGuest(): void
{
    if (getCurrentUser() !== null) {
        header('Location: /platform');
        exit;
    }
}

function getLoginError(): ?string
{
    $error = $_SESSION['login_error'] ?? null;
    unset($_SESSION['login_error']);
    return $error;
}
