<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Methode non autorisee', 405);
}

requireAdmin();

try {
    $pdo = getConnection();

    $totalTitres = (int) $pdo->query('SELECT COUNT(*) FROM livres')->fetchColumn();
    $totalExemplaires = (int) $pdo->query('SELECT COALESCE(SUM(stock_total), 0) FROM livres')->fetchColumn();
    $stockDisponible = (int) $pdo->query('SELECT COALESCE(SUM(stock_disponible), 0) FROM livres')->fetchColumn();
    $empruntsActifs = (int) $pdo->query("SELECT COUNT(*) FROM emprunts WHERE statut = 'actif'")->fetchColumn();
    $totalAchats = (int) $pdo->query('SELECT COUNT(*) FROM achats')->fetchColumn();
    $titresDisponibles = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'disponible'")->fetchColumn();
    $titresEmpruntes = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'emprunte'")->fetchColumn();
    $titresReserves = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'reserve'")->fetchColumn();
    $titresVendus = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'vendu' OR stock_total = 0")->fetchColumn();
    $reservationsActives = (int) $pdo->query("SELECT COUNT(*) FROM reservations WHERE statut = 'actif'")->fetchColumn();
    $totalMembres = (int) $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'membre'")->fetchColumn();
    $totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'")->fetchColumn();

    $categories = $pdo->query(
        'SELECT categorie, COUNT(*) AS total, COALESCE(SUM(stock_total), 0) AS exemplaires
         FROM livres GROUP BY categorie ORDER BY total DESC'
    )->fetchAll();

    $recent = $pdo->query(
        'SELECT id, titre, auteur, statut, stock_total, stock_disponible, date_ajout
         FROM livres ORDER BY date_ajout DESC LIMIT 5'
    )->fetchAll();

    $stmt = $pdo->prepare(
        'SELECT id, nom, prenom, email, date_inscription FROM utilisateurs
         WHERE role = ? ORDER BY date_inscription DESC LIMIT 5'
    );
    $stmt->execute(['membre']);
    $recentMembers = $stmt->fetchAll();

    jsonResponse([
        'success' => true,
        'data' => [
            'livres' => [
                'total_titres' => $totalTitres,
                'total_exemplaires' => $totalExemplaires,
                'stock_disponible' => $stockDisponible,
                'emprunts_actifs' => $empruntsActifs,
                'total_achats' => $totalAchats,
                'disponibles' => $titresDisponibles,
                'empruntes' => $titresEmpruntes,
                'reserves' => $titresReserves,
                'vendus' => $titresVendus,
                'reservations_actives' => $reservationsActives,
            ],
            'utilisateurs' => [
                'membres' => $totalMembres,
                'admins' => $totalAdmins,
            ],
            'categories' => $categories,
            'recents_livres' => $recent,
            'recents_membres' => $recentMembers,
        ],
    ]);
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}
