<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Methode non autorisee', 405);
}

$data = getJsonInput();
$email = validateEmail(sanitizeString($data['email'] ?? ''));
$password = $data['mot_de_passe'] ?? '';

if ($password === '') {
    jsonError('Email et mot de passe requis.');
}

try {
    $pdo = getConnection();
    checkLoginRateLimit($pdo, 'user', $email);

    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['mot_de_passe'])) {
        recordLoginAttempt($pdo, 'user', $email, false);
        jsonError('Email ou mot de passe incorrect.', 401);
    }

    if ($user['role'] === 'admin') {
        recordLoginAttempt($pdo, 'user', $email, false);
        jsonError('Les administrateurs doivent utiliser /admin/login.html', 403);
    }

    recordLoginAttempt($pdo, 'user', $email, true);
    loginMember($user);

    jsonResponse([
        'success' => true,
        'message' => 'Connexion reussie.',
        'data' => formatUser($user, false),
        'csrf_token' => getMemberCsrfToken(),
    ]);
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}
