<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin_auth.php';
require_once __DIR__ . '/../stock_helper.php';

$method = $_SERVER['REQUEST_METHOD'];
$pdo = getConnection();
requireAdmin();

try {
    if ($method === 'GET') {
        handleGet($pdo);
    } elseif ($method === 'DELETE') {
        requireAdminCsrf();
        handleReturn($pdo);
    } else {
        jsonError('Methode non autorisee', 405);
    }
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}

function handleGet(PDO $pdo): void
{
    $sql = "SELECT e.id, e.livre_id, e.utilisateur_id, e.date_emprunt, e.date_retour_prevue, e.statut,
                   l.titre, l.auteur, l.categorie, l.stock_total, l.stock_disponible,
                   u.prenom, u.nom, u.email
            FROM emprunts e
            INNER JOIN livres l ON l.id = e.livre_id
            INNER JOIN utilisateurs u ON u.id = e.utilisateur_id
            WHERE e.statut = 'actif'
            ORDER BY e.date_emprunt DESC";

    $stmt = $pdo->query($sql);

    jsonResponse(['success' => true, 'data' => $stmt->fetchAll()]);
}

function handleReturn(PDO $pdo): void
{
    $data = getJsonInput();
    $empruntId = (int) ($data['emprunt_id'] ?? 0);

    if ($empruntId <= 0) {
        jsonError('ID de l emprunt requis.');
    }

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "SELECT e.id, e.livre_id, l.titre
         FROM emprunts e
         INNER JOIN livres l ON l.id = e.livre_id
         WHERE e.id = ? AND e.statut = 'actif'
         FOR UPDATE"
    );
    $stmt->execute([$empruntId]);
    $emprunt = $stmt->fetch();

    if (!$emprunt) {
        $pdo->rollBack();
        jsonError('Emprunt actif introuvable.', 404);
    }

    $livreId = (int) $emprunt['livre_id'];

    $stmt = $pdo->prepare(
        "UPDATE emprunts SET statut = 'retourne', date_retour = NOW() WHERE id = ?"
    );
    $stmt->execute([$empruntId]);

    recalculateStockDisponible($pdo, $livreId);

    $pdo->commit();

    jsonResponse([
        'success' => true,
        'message' => 'Retour enregistre — stock mis a jour : ' . $emprunt['titre'],
        'data' => ['emprunt_id' => $empruntId, 'livre_id' => $livreId],
    ]);
}
