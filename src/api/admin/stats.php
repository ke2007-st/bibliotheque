<?php

declare(strict_types=1);

require_once __DIR__ . '/../admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Methode non autorisee', 405);
}

requireAdmin();

try {
    $pdo = getConnection();

    $totalLivres = (int) $pdo->query('SELECT COUNT(*) FROM livres')->fetchColumn();
    $disponibles = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'disponible'")->fetchColumn();
    $empruntes = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'emprunte'")->fetchColumn();
    $reserves = (int) $pdo->query("SELECT COUNT(*) FROM livres WHERE statut = 'reserve'")->fetchColumn();
    $totalMembres = (int) $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'membre'")->fetchColumn();
    $totalAdmins = (int) $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE role = 'admin'")->fetchColumn();

    $categories = $pdo->query(
        'SELECT categorie, COUNT(*) AS total FROM livres GROUP BY categorie ORDER BY total DESC'
    )->fetchAll();

    $recent = $pdo->query(
        'SELECT id, titre, auteur, statut, date_ajout FROM livres ORDER BY date_ajout DESC LIMIT 5'
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
                'total' => $totalLivres,
                'disponibles' => $disponibles,
                'empruntes' => $empruntes,
                'reserves' => $reserves,
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
