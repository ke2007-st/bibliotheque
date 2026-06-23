<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin_auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();
requireAdmin();

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo);
            break;
        case 'DELETE':
            requireAdminCsrf();
            handleDelete($pdo);
            break;
        default:
            jsonError('Methode non autorisee', 405);
    }
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}

function handleGet(PDO $pdo): void
{
    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare(
            'SELECT id, nom, prenom, email, role, telephone, date_inscription FROM utilisateurs WHERE id = ?'
        );
        $stmt->execute([(int) $_GET['id']]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonError('Utilisateur introuvable', 404);
        }

        jsonResponse(['success' => true, 'data' => $user]);
    }

    $role = sanitizeString($_GET['role'] ?? '');
    $sql = 'SELECT id, nom, prenom, email, role, telephone, date_inscription FROM utilisateurs WHERE 1=1';
    $params = [];

    if ($role !== '' && in_array($role, ['admin', 'membre'], true)) {
        $sql .= ' AND role = ?';
        $params[] = $role;
    }

    if (!empty($_GET['search'])) {
        $sql .= ' AND (nom LIKE ? OR prenom LIKE ? OR email LIKE ?)';
        $term = '%' . sanitizeString($_GET['search']) . '%';
        $params = array_merge($params, [$term, $term, $term]);
    }

    $sql .= ' ORDER BY date_inscription DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

function handleDelete(PDO $pdo): void
{
    $id = (int) ($_GET['id'] ?? 0);
    $admin = requireAdmin();

    if ($id <= 0) {
        jsonError('ID utilisateur requis.');
    }

    if ($id === (int) $admin['id']) {
        jsonError('Impossible de supprimer votre propre compte.', 403);
    }

    $stmt = $pdo->prepare('SELECT id, role FROM utilisateurs WHERE id = ?');
    $stmt->execute([$id]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonError('Utilisateur introuvable', 404);
    }

    if ($user['role'] === 'admin') {
        jsonError('Impossible de supprimer un administrateur.', 403);
    }

    $stmt = $pdo->prepare('DELETE FROM utilisateurs WHERE id = ? AND role = ?');
    $stmt->execute([$id, 'membre']);

    jsonResponse(['success' => true, 'message' => 'Membre supprime.']);
}
