<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

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
        'SELECT a.id, a.prix_paye, a.date_achat,
                l.id AS livre_id, l.titre, l.auteur, l.categorie, l.isbn
         FROM achats a
         INNER JOIN livres l ON l.id = a.livre_id
         WHERE a.utilisateur_id = ?
         ORDER BY a.date_achat DESC'
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

    $stmt = $pdo->prepare('SELECT id, titre, statut, prix FROM livres WHERE id = ? FOR UPDATE');
    $stmt->execute([$livreId]);
    $livre = $stmt->fetch();

    if (!$livre) {
        $pdo->rollBack();
        jsonError('Livre introuvable.', 404);
    }

    if ($livre['statut'] !== 'disponible') {
        $pdo->rollBack();
        jsonError('Ce livre n est pas disponible a l achat.');
    }

    $stmt = $pdo->prepare(
        'SELECT id FROM achats WHERE utilisateur_id = ? AND livre_id = ?'
    );
    $stmt->execute([(int) $user['id'], $livreId]);

    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonError('Vous avez deja achete ce livre.', 409);
    }

    $prix = (float) $livre['prix'];

    $stmt = $pdo->prepare(
        'INSERT INTO achats (utilisateur_id, livre_id, prix_paye) VALUES (?, ?, ?)'
    );
    $stmt->execute([(int) $user['id'], $livreId, $prix]);

    $stmt = $pdo->prepare("UPDATE livres SET statut = 'vendu' WHERE id = ?");
    $stmt->execute([$livreId]);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Achat confirme : ' . $livre['titre'],
        'data' => [
            'achat_id' => (int) $pdo->lastInsertId(),
            'livre_id' => $livreId,
            'titre' => $livre['titre'],
            'prix_paye' => $prix,
        ],
    ], 201);
}
