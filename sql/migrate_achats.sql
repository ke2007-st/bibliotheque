-- Migration achats (bases existantes)
ALTER TABLE livres
    ADD COLUMN IF NOT EXISTS prix DECIMAL(10, 2) NOT NULL DEFAULT 9.99 AFTER statut;

ALTER TABLE livres
    MODIFY statut ENUM('disponible', 'emprunte', 'reserve', 'vendu') NOT NULL DEFAULT 'disponible';

UPDATE livres SET prix = 12.50 WHERE prix = 9.99 OR prix IS NULL;

CREATE TABLE IF NOT EXISTS achats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    livre_id INT NOT NULL,
    prix_paye DECIMAL(10, 2) NOT NULL,
    date_achat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (livre_id) REFERENCES livres(id) ON DELETE CASCADE,
    UNIQUE KEY unique_achat (utilisateur_id, livre_id)
);
