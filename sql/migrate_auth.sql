-- Migration securite + admin separe
CREATE TABLE IF NOT EXISTS login_tentatives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    contexte ENUM('user', 'admin') NOT NULL,
    email VARCHAR(255) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    reussie TINYINT(1) NOT NULL DEFAULT 0,
    date_tentative TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_rate_limit (contexte, email, date_tentative),
    INDEX idx_rate_ip (contexte, ip, date_tentative)
);

INSERT IGNORE INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Admin', 'Bibliotheque', 'admin@biblio.local', '$2y$10$LDrdlZPAe1hpB1eptJudLOzJ/Jg3VcBAtR5306L7Wvj6L.B29zVgi', 'admin');
