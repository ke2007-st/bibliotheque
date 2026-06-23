<?php

declare(strict_types=1);

require_once __DIR__ . '/../../admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Methode non autorisee', 405);
}

logoutAdmin();

jsonResponse(['success' => true, 'message' => 'Deconnexion administrateur reussie.']);
