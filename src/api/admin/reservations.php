<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin_auth.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();
requireAdmin();

try {
    if ($method === 'GET') {
        handleGet($pdo);
    } elseif ($method === 'DELETE') {
        requireAdminCsrf();
        handleCancel($pdo);
    } else {
        jsonError('Methode non autorisee', 405);
    }
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}

function handleGet(PDO $pdo): void
{
    $sql = "SELECT r.id, r.livre_id, r.utilisateur_id, r.date_reservation, r.statut,
                   l.titre, l.auteur, l.categorie, l.stock_total, l.stock_disponible, l.statut AS livre_statut,
                   u.prenom, u.nom, u.email
            FROM reservations r
            INNER JOIN livres l ON l.id = r.livre_id
            INNER JOIN utilisateurs u ON u.id = r.utilisateur_id
            WHERE r.statut = 'actif'
            ORDER BY r.date_reservation ASC";

    $stmt = $pdo->query($sql);

    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

function handleCancel(PDO $pdo): void
{
    $data = getJsonInput();
    $reservationId = (int) ($data['reservation_id'] ?? 0);

    if ($reservationId <= 0) {
        jsonError('ID de reservation requis.');
    }

    $stmt = $pdo->prepare(
        "UPDATE reservations SET statut = 'annule' WHERE id = ? AND statut = 'actif'"
    );
    $stmt->execute([$reservationId]);

    if ($stmt->rowCount() === 0) {
        jsonError('Reservation introuvable.', 404);
    }

    jsonResponse(['success' => true, 'message' => 'Reservation annulee par l administrateur.']);
}
