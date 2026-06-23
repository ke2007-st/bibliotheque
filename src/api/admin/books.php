<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin_auth.php';
require_once __DIR__ . '/../stock_helper.php';

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

function enrichLivre(PDO $pdo, array $livre): array
{
    $livre['stock_total'] = (int) $livre['stock_total'];
    $livre['stock_disponible'] = (int) $livre['stock_disponible'];
    $livre['emprunts_actifs'] = countActiveEmprunts($pdo, (int) $livre['id']);

    return $livre;
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

        jsonResponse(['success' => true, 'data' => enrichLivre($pdo, $livre)]);
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

    $livres = array_map(function (array $row) use ($pdo) {
        return enrichLivre($pdo, $row);
    }, $stmt->fetchAll());

    jsonResponse(['success' => true, 'data' => $livres]);
}

function handlePost(PDO $pdo): void
{
    $data = getJsonInput();

    $titre = sanitizeString($data['titre'] ?? '');
    $auteur = sanitizeString($data['auteur'] ?? '');

    if ($titre === '' || $auteur === '') {
        jsonError('Le titre et l auteur sont obligatoires.');
    }

    $stockTotal = max(0, (int) ($data['stock_total'] ?? 1));
    $stockDispo = isset($data['stock_disponible'])
        ? max(0, (int) $data['stock_disponible'])
        : $stockTotal;

    $stock = validateStockInput($stockTotal, $stockDispo, 0);
    $statut = sanitizeString($data['statut'] ?? '') ?: 'disponible';
    if ($statut === 'reserve') {
        $statut = 'reserve';
    } else {
        $statut = $stock['stock_total'] <= 0 ? 'vendu' : ($stock['stock_disponible'] > 0 ? 'disponible' : 'emprunte');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, stock_total, stock_disponible, prix, description)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );

    $stmt->execute([
        $titre,
        $auteur,
        sanitizeString($data['isbn'] ?? '') ?: null,
        sanitizeString($data['categorie'] ?? '') ?: 'General',
        !empty($data['annee']) ? (int) $data['annee'] : null,
        validateStatut($statut),
        $stock['stock_total'],
        $stock['stock_disponible'],
        max(0, (float) ($data['prix'] ?? 9.99)),
        sanitizeString($data['description'] ?? '') ?: null,
    ]);

    $id = (int) $pdo->lastInsertId();
    if (sanitizeString($data['statut'] ?? '') === 'reserve') {
        $stmt = $pdo->prepare("UPDATE livres SET statut = 'reserve' WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        syncLivreStatut($pdo, $id);
    }

    $stmt = $pdo->prepare('SELECT * FROM livres WHERE id = ?');
    $stmt->execute([$id]);

    jsonResponse([
        'success' => true,
        'message' => 'Livre ajoute.',
        'data' => enrichLivre($pdo, $stmt->fetch()),
    ], 201);
}

function handlePut(PDO $pdo): void
{
    $data = getJsonInput();
    $id = (int) ($data['id'] ?? $_GET['id'] ?? 0);

    if ($id <= 0) {
        jsonError('ID du livre requis.');
    }

    $stmt = $pdo->prepare('SELECT * FROM livres WHERE id = ?');
    $stmt->execute([$id]);
    $existing = $stmt->fetch();

    if (!$existing) {
        jsonError('Livre introuvable', 404);
    }

    $titre = sanitizeString($data['titre'] ?? '');
    $auteur = sanitizeString($data['auteur'] ?? '');

    if ($titre === '' || $auteur === '') {
        jsonError('Le titre et l auteur sont obligatoires.');
    }

    $empruntsActifs = countActiveEmprunts($pdo, $id);
    $stockTotal = max(0, (int) ($data['stock_total'] ?? $existing['stock_total']));
    $stockDispo = max(0, (int) ($data['stock_disponible'] ?? $existing['stock_disponible']));
    $stock = validateStockInput($stockTotal, $stockDispo, $empruntsActifs);

    $requestedStatut = sanitizeString($data['statut'] ?? $existing['statut']);
    $statut = validateStatut($requestedStatut === '' ? 'disponible' : $requestedStatut);

    $stmt = $pdo->prepare(
        'UPDATE livres SET titre = ?, auteur = ?, isbn = ?, categorie = ?, annee = ?, statut = ?, stock_total = ?, stock_disponible = ?, prix = ?, description = ?
         WHERE id = ?'
    );

    $stmt->execute([
        $titre,
        $auteur,
        sanitizeString($data['isbn'] ?? '') ?: null,
        sanitizeString($data['categorie'] ?? '') ?: 'General',
        !empty($data['annee']) ? (int) $data['annee'] : null,
        $statut,
        $stock['stock_total'],
        $stock['stock_disponible'],
        max(0, (float) ($data['prix'] ?? 9.99)),
        sanitizeString($data['description'] ?? '') ?: null,
        $id,
    ]);

    if ($statut === 'reserve') {
        $stmt = $pdo->prepare("UPDATE livres SET statut = 'reserve' WHERE id = ?");
        $stmt->execute([$id]);
    } else {
        syncLivreStatut($pdo, $id);
    }

    $stmt = $pdo->prepare('SELECT * FROM livres WHERE id = ?');
    $stmt->execute([$id]);

    jsonResponse([
        'success' => true,
        'message' => 'Livre modifie.',
        'data' => enrichLivre($pdo, $stmt->fetch()),
    ]);
}

function handleDelete(PDO $pdo): void
{
    $id = (int) ($_GET['id'] ?? 0);

    if ($id <= 0) {
        jsonError('ID du livre requis.');
    }

    if (countActiveEmprunts($pdo, $id) > 0) {
        jsonError('Impossible de supprimer : emprunts actifs en cours.');
    }

    $stmt = $pdo->prepare('DELETE FROM livres WHERE id = ?');
    $stmt->execute([$id]);

    if ($stmt->rowCount() === 0) {
        jsonError('Livre introuvable', 404);
    }

    jsonResponse(['success' => true, 'message' => 'Livre supprime.']);
}
