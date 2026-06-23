CREATE TABLE IF NOT EXISTS emprunts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    livre_id INT NOT NULL,
    date_emprunt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_retour_prevue DATE NOT NULL,
    date_retour TIMESTAMP NULL DEFAULT NULL,
    statut ENUM('actif', 'retourne') NOT NULL DEFAULT 'actif',
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (livre_id) REFERENCES livres(id) ON DELETE CASCADE,
    INDEX idx_emprunts_user (utilisateur_id),
    INDEX idx_emprunts_livre (livre_id),
    INDEX idx_emprunts_actif (utilisateur_id, livre_id, statut)
);
