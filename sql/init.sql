CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('admin', 'membre') NOT NULL DEFAULT 'membre',
    telephone VARCHAR(20) DEFAULT NULL,
    date_inscription TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Admin', 'Bibliotheque', 'admin@biblio.local', '$2y$10$LDrdlZPAe1hpB1eptJudLOzJ/Jg3VcBAtR5306L7Wvj6L.B29zVgi', 'admin');

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

CREATE TABLE IF NOT EXISTS livres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(255) NOT NULL,
    auteur VARCHAR(255) NOT NULL,
    isbn VARCHAR(20) DEFAULT NULL,
    categorie VARCHAR(100) NOT NULL DEFAULT 'General',
    annee INT DEFAULT NULL,
    statut ENUM('disponible', 'emprunte', 'reserve', 'vendu') NOT NULL DEFAULT 'disponible',
    prix DECIMAL(10, 2) NOT NULL DEFAULT 9.99,
    description TEXT DEFAULT NULL,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, prix, description) VALUES
('Le Petit Prince', 'Antoine de Saint-Exupery', '978-2070612758', 'Fiction', 1943, 'disponible', 12.50, 'Conte poetique sur lamitie et la vie.'),
('1984', 'George Orwell', '978-2070368228', 'Science-Fiction', 1949, 'emprunte', 14.90, 'Dystopie sur la surveillance totalitaire.'),
('Les Miserables', 'Victor Hugo', '978-2253006327', 'Classique', 1862, 'disponible', 18.00, 'Epopée sociale du XIXe siècle.'),
('L Etranger', 'Albert Camus', '978-2070360020', 'Philosophie', 1942, 'reserve', 11.50, 'Roman existentialiste.'),
('Harry Potter a l ecole des sorciers', 'J.K. Rowling', '978-2070541270', 'Fantasy', 1997, 'disponible', 16.99, 'Premier tome de la saga magique.'),
('Don Quichotte', 'Miguel de Cervantes', '978-2070400812', 'Classique', 1605, 'emprunte', 22.00, 'Aventure du chevalier idealiste.'),
('Le Comte de Monte-Cristo', 'Alexandre Dumas', '978-2070413119', 'Aventure', 1844, 'disponible', 19.90, 'Recit de vengeance et de redemption.');

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
