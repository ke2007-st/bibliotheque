<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/stock_helper.php';

const EMPRUNT_DUREE_JOURS = 21;

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();
$user = requireMember();

try {
    if ($method === 'GET') {
        handleGet($pdo, $user);
    } elseif ($method === 'POST') {
        requireMemberCsrf();
        handlePost($pdo, $user);
    } else {
        jsonError('Methode non autorisee', 405);
    }
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}

function handleGet(PDO $pdo, array $user): void
{
    $stmt = $pdo->prepare(
        'SELECT e.id, e.livre_id, e.date_emprunt, e.date_retour_prevue, e.date_retour, e.statut,
                l.titre, l.auteur, l.categorie, l.isbn
         FROM emprunts e
         INNER JOIN livres l ON l.id = e.livre_id
         WHERE e.utilisateur_id = ?
         ORDER BY e.date_emprunt DESC'
    );
    $stmt->execute([(int) $user['id']]);

    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

function handlePost(PDO $pdo, array $user): void
{
    $data = getJsonInput();
    $livreId = (int) ($data['livre_id'] ?? 0);

    if ($livreId <= 0) {
        jsonError('ID du livre requis.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare('SELECT id, titre, statut, stock_total, stock_disponible FROM livres WHERE id = ? FOR UPDATE');
    $stmt->execute([$livreId]);
    $livre = $stmt->fetch();

    if (!$livre) {
        $pdo->rollBack();
        jsonError('Livre introuvable.', 404);
    }

    if (!isLivreEmpruntable($livre)) {
        $pdo->rollBack();
        jsonError('Ce livre n est pas disponible a l emprunt (stock epuise ou reserve).');
    }

    $stmt = $pdo->prepare(
        'SELECT id FROM achats WHERE utilisateur_id = ? AND livre_id = ?'
    );
    $stmt->execute([(int) $user['id'], $livreId]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonError('Vous possedez deja ce livre.');
    }

    $stmt = $pdo->prepare(
        "SELECT id FROM emprunts WHERE utilisateur_id = ? AND livre_id = ? AND statut = 'actif'"
    );
    $stmt->execute([(int) $user['id'], $livreId]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonError('Vous avez deja emprunte ce livre.');
    }

    $stmt = $pdo->prepare(
        'INSERT INTO emprunts (utilisateur_id, livre_id, date_retour_prevue) VALUES (?, ?, DATE_ADD(CURDATE(), INTERVAL ? DAY))'
    );
    $stmt->execute([(int) $user['id'], $livreId, EMPRUNT_DUREE_JOURS]);

    $stmt = $pdo->prepare('UPDATE livres SET stock_disponible = stock_disponible - 1 WHERE id = ?');
    $stmt->execute([$livreId]);
    syncLivreStatut($pdo, $livreId);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Emprunt confirme : ' . $livre['titre'],
        'data' => [
            'emprunt_id' => (int) $pdo->lastInsertId(),
            'livre_id' => $livreId,
            'titre' => $livre['titre'],
        ],
    ], 201);
}
