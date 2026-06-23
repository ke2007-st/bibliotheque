<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Methode non autorisee', 405);
}

$user = requireMember();

try {
    $pdo = getConnection();

    $total = (int) $pdo->query('SELECT COUNT(*) FROM livres')->fetchColumn();
    $disponibles = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'disponible'")->fetchColumn();
    $empruntes = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'emprunte'")->fetchColumn();
    $reserves = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'reserve'")->fetchColumn();
    $vendus = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'vendu'")->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM achats WHERE utilisateur_id = ?');
    $stmt->execute([(int) $user['id']]);
    $mesAchats = (int) $stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COALESCE(SUM(prix_paye), 0) FROM achats WHERE utilisateur_id = ?');
    $stmt->execute([(int) $user['id']]);
    $totalDepense = (float) $stmt->fetchColumn();

    $categories = $pdo->query(
        'SELECT categorie, COUNT(*) AS total FROM livres GROUP BY categorie ORDER BY total DESC'
    )->fetchAll();

    $recent = $pdo->query(
        'SELECT id, titre, auteur, statut, date_ajout FROM livres ORDER BY date_ajout DESC LIMIT 5'
    )->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => [
            'total' => $total,
            'disponibles' => $disponibles,
            'empruntes' => $empruntes,
            'reserves' => $reserves,
            'vendus' => $vendus,
            'mes_achats' => $mesAchats,
            'total_depense' => $totalDepense,
            'categories' => $categories,
            'recents' => $recent,
        ],
    ]);
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}
