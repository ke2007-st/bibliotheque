<?php

declare(strict_types=1);

require_once __DIR__ . '/../../admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Methode non autorisee', 405);
}

$data = getJsonInput();
$email = validateEmail(sanitizeString($data['email'] ?? ''));
$password = $data['mot_de_passe'] ?? '';

if ($password === '') {
    jsonError('Identifiants requis.');
}

try {
    $pdo = getConnection();
    checkLoginRateLimit($pdo, 'admin', $email);

    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ? AND role = ?');
    $stmt->execute([$email, 'admin']);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['mot_de_passe'])) {
        recordLoginAttempt($pdo, 'admin', $email, false);
        jsonError('Identifiants administrateur invalides.', 401);
    }

    recordLoginAttempt($pdo, 'admin', $email, true);
    loginAdmin($user);

    jsonResponse([
        'success' => true,
        'message' => 'Connexion administrateur reussie.',
        'data' => formatUser($user),
        'csrf_token' => getAdminCsrfToken(),
    ]);
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}
