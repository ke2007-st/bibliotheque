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
    stock_total INT NOT NULL DEFAULT 1,
    stock_disponible INT NOT NULL DEFAULT 1,
    prix DECIMAL(10, 2) NOT NULL DEFAULT 9.99,
    description TEXT DEFAULT NULL,
    bio_auteur TEXT DEFAULT NULL,
    pages INT DEFAULT NULL,
    editeur VARCHAR(120) DEFAULT NULL,
    note DECIMAL(2, 1) DEFAULT NULL,
    date_ajout TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, prix, description, bio_auteur, pages, editeur, note) VALUES
('Le Petit Prince', 'Antoine de Saint-Exupery', '978-2070612758', 'Fiction', 1943, 'disponible', 12.50,
 'Un pilote perdus dans le desert rencontre un petit prince venu d une autre planete. Un conte poetique sur lamitie, la responsabilite et ce qui compte vraiment.',
 'Antoine de Saint-Exupery, aviateur et ecrivain francais, est l auteur de plusieurs recits celebres melant aventure et reflexion philosophique.',
 96, 'Gallimard', 4.8),
('1984', 'George Orwell', '978-2070368228', 'Science-Fiction', 1949, 'emprunte', 14.90,
 'Dans un Etat totalitaire, Winston Smith tente de preserver sa liberte interieure face a la surveillance omnipresente du Grand Frere.',
 'George Orwell, journaliste et romancier britannique, est connu pour ses dystopies denoncant totalitarisme et manipulation.',
 328, 'Penguin', 4.7),
('Les Miserables', 'Victor Hugo', '978-2253006327', 'Classique', 1862, 'disponible', 18.00,
 'Jean Valjean, ancien forçat, cherche la redemption dans la France du XIXe siecle, entre misere sociale et combats pour la justice.',
 'Victor Hugo, figure majeure du romantisme francais, a marque la litterature par ses oeuvres engagées et epiques.',
 512, 'Livre de Poche', 4.6),
('L Etranger', 'Albert Camus', '978-2070360020', 'Philosophie', 1942, 'reserve', 11.50,
 'Meursault, indifferent aux conventions sociales, voit sa vie basculer apres un acte impulsif sur une plage algeroise.',
 'Albert Camus, prix Nobel de litterature, explore l absurde et la condition humaine dans un style sobre et percutant.',
 184, 'Gallimard', 4.5),
('Harry Potter a l ecole des sorciers', 'J.K. Rowling', '978-2070541270', 'Fantasy', 1997, 'disponible', 16.99,
 'Orphelin eleve par sa tante, Harry decouvre qu il est un sorcier et integre Poudlard, une ecole de magie extraordinaire.',
 'J.K. Rowling est l auteure de la saga Harry Potter, devenue un phenomene litteraire mondial.',
 320, 'Bloomsbury', 4.9),
('Don Quichotte', 'Miguel de Cervantes', '978-2070400812', 'Classique', 1605, 'emprunte', 22.00,
 'Alonso Quixano, obsede par les romans de chevalerie, part en quete d aventures avec son fidèle ecuyer Sancho Panza.',
 'Miguel de Cervantes, ecrivain espagnol, est considere comme l un des fondateurs du roman moderne.',
 863, 'Espasa', 4.4),
('Le Comte de Monte-Cristo', 'Alexandre Dumas', '978-2070413119', 'Aventure', 1844, 'disponible', 19.90,
 'Edmond Dantes, injustement emprisonne, s evade et revient sous une nouvelle identite pour accomplir sa vengeance.',
 'Alexandre Dumas, maitre du roman d aventures, a enchanté des generations de lecteurs par ses recits palpitants.',
 704, 'Folio', 4.7),
('Le Seigneur des Anneaux', 'J.R.R. Tolkien', '978-2267020098', 'Fantasy', 1954, 'disponible', 24.90,
 'Frodon entreprend un voyage epique pour detruire un anneau maléfique et sauver la Terre du Milieu.',
 'J.R.R. Tolkien, philologue et romancier britannique, a cree un univers fantasy de reference.',
 1216, 'Christian Bourgois', 4.9),
('Dune', 'Frank Herbert', '978-2266326047', 'Science-Fiction', 1965, 'disponible', 21.50,
 'Sur la planete desertique Arrakis, plusieurs factions s affrontent pour le controle de l epice.',
 'Frank Herbert est considere comme l un des maitres de la science-fiction moderne.',
 688, 'Pocket', 4.6),
('La Peste', 'Albert Camus', '978-2070360100', 'Philosophie', 1947, 'emprunte', 13.20,
 'A Oran, une epidemie de peste confine la ville et oblige chacun a affronter l absurdite et la solidarite.',
 'Albert Camus developpe ici sa reflexion sur la condition humaine face au malheur collectif.',
 320, 'Gallimard', 4.4),
('Le Rouge et le Noir', 'Stendhal', '978-2253003159', 'Classique', 1830, 'emprunte', 15.80,
 'Julien Sorel, ambitieux et passionne, gravit les echelons de la societe sous la Restauration.',
 'Stendhal, pionnier du roman realiste, decrit avec finesse les passions et les hypocrisies sociales.',
 576, 'Livre de Poche', 4.3),
('Germinal', 'Emile Zola', '978-2253003937', 'Classique', 1885, 'emprunte', 17.40,
 'Dans le nord minier, Etienne Lantier decouvre la misere ouvriere et la force des luttes sociales.',
 'Emile Zola, chef de file du naturalisme, peint avec force la vie des travailleurs du XIXe siecle.',
 592, 'Livre de Poche', 4.5),
('La Nausee', 'Jean-Paul Sartre', '978-2070360027', 'Philosophie', 1938, 'reserve', 12.90,
 'Antoine Roquentin, a Bouville, est traverse par une crise existentielle qui remet en question le reel.',
 'Jean-Paul Sartre, philosophe et ecrivain, est une figure centrale de l existentialisme.',
 256, 'Gallimard', 4.2),
('Le Parfum', 'Patrick Suskind', '978-2253014278', 'Fiction', 1985, 'reserve', 14.60,
 'Jean-Baptiste Grenouille, ne sans odeur, devient un genie olfactif obsede par la creation du parfum parfait.',
 'Patrick Suskind a connu un succes mondial avec ce roman singulier et hypnotique.',
 336, 'Livre de Poche', 4.5),
('Bel-Ami', 'Guy de Maupassant', '978-2253003287', 'Classique', 1885, 'reserve', 11.90,
 'Georges Duroy, journaliste sans scrupules, utilise charme et manipulation pour gravir les echelons parisiennes.',
 'Guy de Maupassant, maitre de la nouvelle, decrit ici la societe parisienne avec mordant.',
 416, 'Livre de Poche', 4.1);

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

CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT NOT NULL,
    livre_id INT NOT NULL,
    date_reservation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('actif', 'annule', 'honore') NOT NULL DEFAULT 'actif',
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id) ON DELETE CASCADE,
    FOREIGN KEY (livre_id) REFERENCES livres(id) ON DELETE CASCADE,
    INDEX idx_reservations_user (utilisateur_id),
    INDEX idx_reservations_livre (livre_id),
    INDEX idx_reservations_actif (livre_id, statut)
);

-- Jeu de demo : executer sql/seed_catalogue_demo.sql apres le premier demarrage
-- Compte test : demo@biblio.local / Demo1234
