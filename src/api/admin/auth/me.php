<?php

declare(strict_types=1);

require_once __DIR__ . '/../../admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Methode non autorisee', 405);
}

$admin = getCurrentAdmin();

if (!$admin) {
    jsonResponse(['success' => true, 'authenticated' => false, 'data' => null]);
}

jsonResponse([
    'success' => true,
    'authenticated' => true,
    'data' => formatUser($admin),
    'csrf_token' => getAdminCsrfToken(),
]);
