-- Stock / exemplaires par titre (executer une seule fois)
ALTER TABLE livres
    ADD COLUMN stock_total INT NOT NULL DEFAULT 1 AFTER statut,
    ADD COLUMN stock_disponible INT NOT NULL DEFAULT 1 AFTER stock_total;

UPDATE livres SET stock_total = 1, stock_disponible = 1 WHERE statut = 'disponible';
UPDATE livres SET stock_total = 1, stock_disponible = 0 WHERE statut IN ('emprunte', 'reserve');
UPDATE livres SET stock_total = 0, stock_disponible = 0 WHERE statut = 'vendu';

UPDATE livres l
SET stock_disponible = GREATEST(0, l.stock_total - (
    SELECT COUNT(*) FROM emprunts e
    WHERE e.livre_id = l.id AND e.statut = 'actif'
));
