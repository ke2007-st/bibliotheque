<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function getCurrentAdmin(): ?array
{
    startSession(ADMIN_SESSION);

    if (empty($_SESSION['admin_id'])) {
        return null;
    }

    $user = fetchUserById(getConnection(), (int) $_SESSION['admin_id']);

    if (!$user || $user['role'] !== 'admin') {
        return null;
    }

    return $user;
}

function requireAdmin(): array
{
    $user = getCurrentAdmin();

    if (!$user) {
        jsonError('Authentification administrateur requise.', 401);
    }

    return $user;
}

function loginAdmin(array $user): void
{
    destroySession(USER_SESSION);
    startSession(ADMIN_SESSION);
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $user['id'];
    $_SESSION[CSRF_KEY] = generateCsrfToken();
}

function logoutAdmin(): void
{
    destroySession(ADMIN_SESSION);
}

function requireAdminCsrf(): void
{
    verifyCsrfToken(ADMIN_SESSION);
}

function getAdminCsrfToken(): string
{
    return storeCsrfToken(ADMIN_SESSION);
}
