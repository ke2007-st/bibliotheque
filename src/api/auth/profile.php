<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET' && $method !== 'PUT') {
    jsonError('Methode non autorisee', 405);
}

$user = requireMember();

if ($method === 'GET') {
    jsonResponse(['success' => true, 'data' => formatUser($user, false)]);
}

requireMemberCsrf();
$data = getJsonInput();

$nom = sanitizeString($data['nom'] ?? $user['nom']);
$prenom = sanitizeString($data['prenom'] ?? $user['prenom']);
$telephone = sanitizeString($data['telephone'] ?? '') ?: null;
$newPassword = $data['mot_de_passe'] ?? '';
$confirm = $data['confirmer_mot_de_passe'] ?? '';

if ($nom === '' || $prenom === '') {
    jsonError('Le nom et le prenom sont obligatoires.');
}

try {
    $pdo = getConnection();

    if ($newPassword !== '') {
        validatePassword($newPassword);
        if ($newPassword !== $confirm) {
            jsonError('Les mots de passe ne correspondent pas.');
        }

        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $pdo->prepare(
            'UPDATE utilisateurs SET nom = ?, prenom = ?, telephone = ?, mot_de_passe = ? WHERE id = ? AND role = ?'
        );
        $stmt->execute([$nom, $prenom, $telephone, $hash, $user['id'], 'membre']);
    } else {
        $stmt = $pdo->prepare(
            'UPDATE utilisateurs SET nom = ?, prenom = ?, telephone = ? WHERE id = ? AND role = ?'
        );
        $stmt->execute([$nom, $prenom, $telephone, $user['id'], 'membre']);
    }

    $updated = fetchUserById($pdo, (int) $user['id']);

    jsonResponse([
        'success' => true,
        'message' => 'Profil mis a jour.',
        'data' => formatUser($updated, false),
    ]);
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}
