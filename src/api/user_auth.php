<?php

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function getCurrentMember(): ?array
{
    startSession(USER_SESSION);

    if (empty($_SESSION['user_id'])) {
        return null;
    }

    return fetchUserById(getConnection(), (int) $_SESSION['user_id']);
}

function requireMember(): array
{
    $user = getCurrentMember();

    if (!$user) {
        jsonError('Connexion requise.', 401);
    }

    if ($user['role'] !== 'membre') {
        jsonError('Acces refuse. Utilisez l espace administrateur.', 403);
    }

    return $user;
}

function loginMember(array $user): void
{
    destroySession(ADMIN_SESSION);
    startSession(USER_SESSION);
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    $_SESSION[CSRF_KEY] = generateCsrfToken();
}

function logoutMember(): void
{
    destroySession(USER_SESSION);
}

function requireMemberCsrf(): void
{
    verifyCsrfToken(USER_SESSION);
}

function getMemberCsrfToken(): string
{
    return storeCsrfToken(USER_SESSION);
}
