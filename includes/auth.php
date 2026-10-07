<?php
/**
 * Simple session based authentication helpers.
 */

function isLoggedIn(): bool
{
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool
{
    return isLoggedIn() && ($_SESSION['role'] ?? '') === 'admin';
}

function currentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

function currentUserName(): string
{
    return $_SESSION['name'] ?? '';
}

function requireLogin(): void
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireLoginRoot(): void
{
    // Use from pages inside /admin or /api that are one level deep.
    if (!isLoggedIn()) {
        header('Location: ../login.php');
        exit;
    }
}

function requireAdmin(): void
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function requireAdminRoot(): void
{
    requireLoginRoot();
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit;
    }
}

function requireUserOnly(): void
{
    // Regular members only - admin has its own dashboard.
    requireLogin();
    if (isAdmin()) {
        header('Location: admin/dashboard.php');
        exit;
    }
}

function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrfCheck(): bool
{
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}
