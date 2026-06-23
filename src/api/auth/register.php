<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonError('Methode non autorisee', 405);
}

$data = getJsonInput();

$nom = sanitizeString($data['nom'] ?? '');
$prenom = sanitizeString($data['prenom'] ?? '');
$email = validateEmail(sanitizeString($data['email'] ?? ''));
$password = $data['mot_de_passe'] ?? '';
$confirm = $data['confirmer_mot_de_passe'] ?? '';
$telephone = sanitizeString($data['telephone'] ?? '') ?: null;

if ($nom === '' || $prenom === '') {
    jsonError('Le nom et le prenom sont obligatoires.');
}

validatePassword($password);

if ($password !== $confirm) {
    jsonError('Les mots de passe ne correspondent pas.');
}

try {
    $pdo = getConnection();

    $stmt = $pdo->prepare('SELECT id FROM utilisateurs WHERE email = ?');
    $stmt->execute([$email]);

    if ($stmt->fetch()) {
        jsonError('Cet email est deja utilise.', 409);
    }

    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $pdo->prepare(
        'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, telephone, role)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$nom, $prenom, $email, $hash, $telephone, 'membre']);

    $id = (int) $pdo->lastInsertId();
    $user = fetchUserById($pdo, $id);

    loginMember($user);

    jsonResponse([
        'success' => true,
        'message' => 'Inscription reussie.',
        'data' => formatUser($user, false),
        'csrf_token' => getMemberCsrfToken(),
    ], 201);
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}
