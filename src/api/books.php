<?php

declare(strict_types=1);

require_once __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonError('Methode non autorisee', 405);
}

requireMember();

try {
    $pdo = getConnection();

    if (isset($_GET['id'])) {
        $stmt = $pdo->prepare(
            'SELECT id, titre, auteur, isbn, categorie, annee, statut, prix, description,
                    bio_auteur, pages, editeur, note, date_ajout
             FROM livres WHERE id = ?'
        );
        $stmt->execute([(int) $_GET['id']]);
        $livre = $stmt->fetch();

        if (!$livre) {
            jsonError('Livre introuvable', 404);
        }

        jsonResponse(['success' => true, 'data' => $livre]);
    }

    $sql = 'SELECT id, titre, auteur, isbn, categorie, annee, statut, prix, description, date_ajout FROM livres WHERE 1=1';
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
} catch (PDOException $e) {
    jsonError('Erreur serveur.', 500);
}
