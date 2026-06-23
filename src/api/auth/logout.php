<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Methode non autorisee', 405);
}

logoutMember();

jsonResponse(['success' => true, 'message' => 'Deconnexion reussie.']);
