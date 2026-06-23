<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Methode non autorisee', 405);
}

$user = getCurrentMember();

if (!$user) {
    jsonResponse(['success' => true, 'authenticated' => false, 'data' => null]);
}

jsonResponse([
    'success' => true,
    'authenticated' => true,
    'data' => formatUser($user, false),
    'csrf_token' => getMemberCsrfToken(),
]);
