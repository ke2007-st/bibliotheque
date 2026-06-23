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
        case 'POST':
            requireAdminCsrf();
            handlePost($pdo);
            break;
        case 'PUT':
            requireAdminCsrf();
            handlePut($pdo);
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
        $stmt = $pdo->prepare('SELECT * FROM livres WHERE id = ?');
        $stmt->execute([(int) $_GET['id']]);
        $livre = $stmt->fetch();

        if (!$livre) {
            jsonError('Livre introuvable', 404);
        }

        jsonResponse(['success' => true, 'data' => $livre]);
    }

    $sql = 'SELECT * FROM livres WHERE 1=1';
    $params = [];

    if (!empty($_GET['search'])) {
        $sql .= ' AND (titre LIKE ? OR auteur LIKE ? OR isbn LIKE ?)';
        $term = '%' . sanitizeString($_GET['search']) . '%';
        $params = [$term, $term, $term];
    }

    if (!empty($_GET['categorie'])) {
        $sql .= ' AND categorie = ?';
        $params[] = sanitizeString($_GET['categorie']);
    }

    if (!empty($_GET['statut'])) {
        $sql .= ' AND statut = ?';
        $params[] = validateStatut(sanitizeString($_GET['statut']));
    }

    $sql .= ' ORDER BY date_ajout DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

function handlePost(PDO $pdo): void
{
    $data = getJsonInput();

    $titre = sanitizeString($data['titre'] ?? '');
    $auteur = sanitizeString($data['auteur'] ?? '');

    if ($titre === '' || $auteur === '') {
        jsonError('Le titre et l auteur sont obligatoires.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, prix, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $titre,
        $auteur,
        sanitizeString($data['isbn'] ?? '') ?: null,
        sanitizeString($data['categorie'] ?? '') ?: 'General',
        !empty($data['annee']) ? (int) $data['annee'] : null,
        validateStatut(sanitizeString($data['statut'] ?? '') ?: 'disponible'),
        max(0, (float) ($data['prix'] ?? 9.99)),
        sanitizeString($data['description'] ?? '') ?: null,
    ]);

    $id = (int) $pdo->lastInsertId();
    $stmt = $pdo->prepare('SELECT * FROM livres WHERE id = ?');
    $stmt->execute([$id]);

    jsonResponse(['success' => true, 'message' => 'Livre ajoute.', 'data' => $stmt->fetch()], 201);
}

function handlePut(PDO $pdo): void
{
    $data = getJsonInput();
    $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        jsonError('ID du livre requis.');
    }

    $stmt = $pdo->prepare('SELECT id FROM livres WHERE id = ?');
    $stmt->execute([$id]);

    if (!$stmt->fetch()) {
        jsonError('Livre introuvable', 404);
    }

    $titre = sanitizeString($data['titre'] ?? '');
    $auteur = sanitizeString($data['auteur'] ?? '');

    if ($titre === '' || $auteur === '') {
        jsonError('Le titre et l auteur sont obligatoires.');
    }

    $stmt = $pdo->prepare(
        'UPDATE livres SET titre = ?, auteur = ?, isbn = ?, categorie = ?, annee = ?, statut = ?, prix = ?, description = ?
         WHERE id = ?'
    );

    $stmt->execute([
        $titre,
        $auteur,
        sanitizeString($data['isbn'] ?? '') ?: null,
        sanitizeString($data['categorie'] ?? '') ?: 'General',
        !empty($data['annee']) ? (int) $data['annee'] : null,
        validateStatut(sanitizeString($data['statut'] ?? '') ?: 'disponible'),
        max(0, (float) ($data['prix'] ?? 9.99)),
        sanitizeString($data['description'] ?? '') ?: null,
        $id,
    ]);

    $stmt = $pdo->prepare('SELECT * FROM livres WHERE id = ?');
    $stmt->execute([$id]);

    jsonResponse(['success' => true, 'message' => 'Livre modifie.', 'data' => $stmt->fetch()]);
}

function handleDelete(PDO $pdo): void
{
    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        jsonError('ID du livre requis.');
    }

    $stmt = $pdo->prepare('DELETE FROM livres WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        jsonError('Livre introuvable', 404);
    }

    jsonResponse(['success' => true, 'message' => 'Livre supprime.']);
}
