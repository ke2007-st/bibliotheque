<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/stock_helper.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();
$user = requireMember();

try {
    if ($method === 'GET') {
        handleGet($pdo, $user);
    } elseif ($method === 'POST') {
        requireMemberCsrf();
        handlePost($pdo, $user);
    } elseif ($method === 'DELETE') {
        requireMemberCsrf();
        handleDelete($pdo, $user);
    } else {
        jsonError('Methode non autorisee', 405);
    }
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}

function handleGet(PDO $pdo, array $user): void
{
    $stmt = $pdo->prepare(
        "SELECT r.id, r.livre_id, r.date_reservation, r.statut,
                l.titre, l.auteur, l.categorie, l.statut AS livre_statut,
                l.stock_total, l.stock_disponible
         FROM reservations r
         INNER JOIN livres l ON l.id = r.livre_id
         WHERE r.utilisateur_id = ? AND r.statut = 'actif'
         ORDER BY r.date_reservation DESC"
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

    if (!isLivreReservable($livre)) {
        $pdo->rollBack();
        jsonError('Ce livre ne peut pas etre reserve (disponible, rupture ou bloque par la bibliotheque).');
    }

    $stmt = $pdo->prepare('SELECT id FROM achats WHERE utilisateur_id = ? AND livre_id = ?');
    $stmt->execute([(int) $user['id'], $livreId]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonError('Vous possedez deja ce livre.');
    }

    $stmt = $pdo->prepare("SELECT id FROM emprunts WHERE utilisateur_id = ? AND livre_id = ? AND statut = 'actif'");
    $stmt->execute([(int) $user['id'], $livreId]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonError('Vous avez deja emprunte ce livre.');
    }

    $stmt = $pdo->prepare("SELECT id FROM reservations WHERE utilisateur_id = ? AND livre_id = ? AND statut = 'actif'");
    $stmt->execute([(int) $user['id'], $livreId]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        jsonError('Vous avez deja reserve ce livre.');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO reservations (utilisateur_id, livre_id) VALUES (?, ?)"
    );
    $stmt->execute([(int) $user['id'], $livreId]);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Reservation enregistree : ' . $livre['titre'],
        'data' => [
            'reservation_id' => (int) $pdo->lastInsertId(),
            'livre_id' => $livreId,
            'titre' => $livre['titre'],
        ],
    ], 201);
}

function handleDelete(PDO $pdo, array $user): void
{
    $data = getJsonInput();
    $livreId = (int) ($data['livre_id'] ?? 0);
    $reservationId = (int) ($data['reservation_id'] ?? 0);

    if ($livreId <= 0 && $reservationId <= 0) {
        jsonError('ID de reservation ou de livre requis.');
    }

    if ($reservationId > 0) {
        $stmt = $pdo->prepare(
            "UPDATE reservations SET statut = 'annule'
             WHERE id = ? AND utilisateur_id = ? AND statut = 'actif'"
        );
        $stmt->execute([$reservationId, (int) $user['id']]);
    } else {
        $stmt = $pdo->prepare(
            "UPDATE reservations SET statut = 'annule'
             WHERE livre_id = ? AND utilisateur_id = ? AND statut = 'actif'"
        );
        $stmt->execute([$livreId, (int) $user['id']]);
    }

    if ($stmt->rowCount() === 0) {
        jsonError('Reservation introuvable.', 404);
    }

    jsonResponse(['success' => true, 'message' => 'Reservation annulee.']);
}
