<?php

declare(strict_types=1);

/**
 * Helpers stock / exemplaires — source de verite partagee membre + admin.
 */

function countActiveEmprunts(PDO $pdo, int $livreId): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM emprunts WHERE livre_id = ? AND statut = 'actif'"
    );
    $stmt->execute([$livreId]);

    return (int) $stmt->fetchColumn();
}

function syncLivreStatut(PDO $pdo, int $livreId): void
{
    $stmt = $pdo->prepare('SELECT stock_total, stock_disponible, statut FROM livres WHERE id = ?');
    $stmt->execute([$livreId]);
    $row = $stmt->fetch();

    if (!$row) {
        return;
    }

    $stockTotal = (int) $row['stock_total'];
    $stockDispo = (int) $row['stock_disponible'];
    $wasReserve = $row['statut'] === 'reserve';

    if ($stockTotal <= 0) {
        $newStatut = 'vendu';
    } elseif ($wasReserve) {
        $newStatut = 'reserve';
    } elseif ($stockDispo <= 0) {
        $newStatut = 'emprunte';
    } else {
        $newStatut = 'disponible';
    }

    $stmt = $pdo->prepare('UPDATE livres SET statut = ? WHERE id = ?');
    $stmt->execute([$newStatut, $livreId]);
}

function recalculateStockDisponible(PDO $pdo, int $livreId): void
{
    $stmt = $pdo->prepare('SELECT stock_total FROM livres WHERE id = ?');
    $stmt->execute([$livreId]);
    $stockTotal = (int) $stmt->fetchColumn();

    $empruntsActifs = countActiveEmprunts($pdo, $livreId);
    $stockDispo = max(0, $stockTotal - $empruntsActifs);

    $stmt = $pdo->prepare('UPDATE livres SET stock_disponible = ? WHERE id = ?');
    $stmt->execute([$stockDispo, $livreId]);

    syncLivreStatut($pdo, $livreId);
}

function validateStockInput(int $stockTotal, int $stockDispo, int $empruntsActifs = 0): array
{
    if ($stockTotal < 0) {
        jsonError('Le nombre d exemplaires ne peut pas etre negatif.');
    }
    if ($stockDispo < 0) {
        jsonError('Le stock disponible ne peut pas etre negatif.');
    }
    if ($stockDispo > $stockTotal) {
        jsonError('Le stock disponible ne peut pas depasser le total d exemplaires.');
    }
    if ($empruntsActifs > $stockTotal) {
        jsonError('Impossible : ' . $empruntsActifs . ' emprunt(s) actif(s) pour seulement ' . $stockTotal . ' exemplaire(s).');
    }
    $minDispo = $stockTotal - $empruntsActifs;
    if ($stockDispo > $minDispo) {
        $stockDispo = $minDispo;
    }

    return ['stock_total' => $stockTotal, 'stock_disponible' => $stockDispo];
}

function isLivreEmpruntable(array $livre): bool
{
    return (int) $livre['stock_disponible'] > 0
        && (int) $livre['stock_total'] > 0
        && $livre['statut'] !== 'reserve'
        && $livre['statut'] !== 'vendu';
}

function isLivreAchetable(array $livre): bool
{
    return isLivreEmpruntable($livre);
}

function countActiveReservations(PDO $pdo, int $livreId): int
{
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM reservations WHERE livre_id = ? AND statut = 'actif'"
    );
    $stmt->execute([$livreId]);

    return (int) $stmt->fetchColumn();
}

function isLivreReservable(array $livre): bool
{
    return (int) $livre['stock_total'] > 0
        && (int) $livre['stock_disponible'] <= 0
        && $livre['statut'] !== 'vendu'
        && $livre['statut'] !== 'reserve';
}
