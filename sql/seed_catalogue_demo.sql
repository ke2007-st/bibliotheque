-- Jeu de demonstration : ~10 livres par scenario (80 titres)
-- Compte test : demo@biblio.local / Demo1234
-- Executer apres migrate_reservations.sql

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

SET @pwd = '$2y$10$b/tVfgyomM003YPBrGZ22.KbhsdfD8j9ezRr2Sst9AgyyvuimFxy.';

SET FOREIGN_KEY_CHECKS = 0;
DELETE FROM reservations;
DELETE FROM emprunts;
DELETE FROM achats;
DELETE FROM livres;
DELETE FROM utilisateurs WHERE role = 'membre';
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES
('Demo', 'Membre', 'demo@biblio.local', @pwd, 'membre'),
('Martin', 'Alice', 'alice@biblio.local', @pwd, 'membre'),
('Durand', 'Bob', 'bob@biblio.local', @pwd, 'membre');

SET @demo = (SELECT id FROM utilisateurs WHERE email = 'demo@biblio.local');
SET @alice = (SELECT id FROM utilisateurs WHERE email = 'alice@biblio.local');
SET @bob = (SELECT id FROM utilisateurs WHERE email = 'bob@biblio.local');

-- 1. Disponibles : empruntables + achetables (10)
INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, stock_total, stock_disponible, prix, description) VALUES
('[Dispo] Le Petit Prince', 'Saint-Exupery', '978-9000000001', 'Demo-Dispo', 1943, 'disponible', 3, 3, 12.50, 'Empruntable et achetable.'),
('[Dispo] Harry Potter T1', 'J.K. Rowling', '978-9000000002', 'Demo-Dispo', 1997, 'disponible', 4, 4, 16.99, 'Empruntable et achetable.'),
('[Dispo] Dune', 'Frank Herbert', '978-9000000003', 'Demo-Dispo', 1965, 'disponible', 3, 3, 21.50, 'Empruntable et achetable.'),
('[Dispo] Le Seigneur des Anneaux', 'J.R.R. Tolkien', '978-9000000004', 'Demo-Dispo', 1954, 'disponible', 5, 5, 24.90, 'Empruntable et achetable.'),
('[Dispo] Le Comte de Monte-Cristo', 'Alexandre Dumas', '978-9000000005', 'Demo-Dispo', 1844, 'disponible', 3, 3, 19.90, 'Empruntable et achetable.'),
('[Dispo] Les Fourmis', 'Bernard Werber', '978-9000000006', 'Demo-Dispo', 1991, 'disponible', 4, 4, 13.90, 'Empruntable et achetable.'),
('[Dispo] L Alchimiste', 'Paulo Coelho', '978-9000000007', 'Demo-Dispo', 1988, 'disponible', 3, 3, 11.90, 'Empruntable et achetable.'),
('[Dispo] Le Hobbit', 'J.R.R. Tolkien', '978-9000000008', 'Demo-Dispo', 1937, 'disponible', 4, 4, 15.90, 'Empruntable et achetable.'),
('[Dispo] Fahrenheit 451', 'Ray Bradbury', '978-9000000009', 'Demo-Dispo', 1953, 'disponible', 3, 3, 14.50, 'Empruntable et achetable.'),
('[Dispo] Le Vieil Homme et la Mer', 'Hemingway', '978-9000000010', 'Demo-Dispo', 1952, 'disponible', 3, 3, 10.90, 'Empruntable et achetable.');

-- 2. Empruntes par d autres membres (10) — indisponibles pour emprunt
INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, stock_total, stock_disponible, prix, description) VALUES
('[Emprunte] 1984', 'George Orwell', '978-9000000011', 'Demo-Emprunte', 1949, 'emprunte', 1, 0, 14.90, 'Tous les exemplaires sont empruntes.'),
('[Emprunte] Don Quichotte', 'Cervantes', '978-9000000012', 'Demo-Emprunte', 1605, 'emprunte', 1, 0, 22.00, 'Tous les exemplaires sont empruntes.'),
('[Emprunte] La Peste', 'Albert Camus', '978-9000000013', 'Demo-Emprunte', 1947, 'emprunte', 1, 0, 13.20, 'Tous les exemplaires sont empruntes.'),
('[Emprunte] Le Rouge et le Noir', 'Stendhal', '978-9000000014', 'Demo-Emprunte', 1830, 'emprunte', 1, 0, 15.80, 'Tous les exemplaires sont empruntes.'),
('[Emprunte] Germinal', 'Emile Zola', '978-9000000015', 'Demo-Emprunte', 1885, 'emprunte', 1, 0, 17.40, 'Tous les exemplaires sont empruntes.'),
('[Emprunte] Vingt Mille Lieues', 'Jules Verne', '978-9000000016', 'Demo-Emprunte', 1870, 'emprunte', 1, 0, 16.50, 'Tous les exemplaires sont empruntes.'),
('[Emprunte] Les Trois Mousquetaires', 'Alexandre Dumas', '978-9000000017', 'Demo-Emprunte', 1844, 'emprunte', 1, 0, 18.90, 'Tous les exemplaires sont empruntes.'),
('[Emprunte] Notre-Dame de Paris', 'Victor Hugo', '978-9000000018', 'Demo-Emprunte', 1831, 'emprunte', 1, 0, 17.90, 'Tous les exemplaires sont empruntes.'),
('[Emprunte] Candide', 'Voltaire', '978-9000000019', 'Demo-Emprunte', 1759, 'emprunte', 1, 0, 9.90, 'Tous les exemplaires sont empruntes.'),
('[Emprunte] Les Liaisons dangereuses', 'Laclos', '978-9000000020', 'Demo-Emprunte', 1782, 'emprunte', 1, 0, 12.90, 'Tous les exemplaires sont empruntes.');

-- 3. Empruntes par le membre demo (10)
INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, stock_total, stock_disponible, prix, description) VALUES
('[Mon emprunt] Les Miserables', 'Victor Hugo', '978-9000000021', 'Demo-MonEmprunt', 1862, 'emprunte', 1, 0, 18.00, 'Emprunte par le compte demo.'),
('[Mon emprunt] L Etranger', 'Albert Camus', '978-9000000022', 'Demo-MonEmprunt', 1942, 'emprunte', 1, 0, 11.50, 'Emprunte par le compte demo.'),
('[Mon emprunt] Le Parfum', 'Patrick Suskind', '978-9000000023', 'Demo-MonEmprunt', 1985, 'emprunte', 1, 0, 14.60, 'Emprunte par le compte demo.'),
('[Mon emprunt] Bel-Ami', 'Guy de Maupassant', '978-9000000024', 'Demo-MonEmprunt', 1885, 'emprunte', 1, 0, 11.90, 'Emprunte par le compte demo.'),
('[Mon emprunt] La Nausee', 'Jean-Paul Sartre', '978-9000000025', 'Demo-MonEmprunt', 1938, 'emprunte', 1, 0, 12.90, 'Emprunte par le compte demo.'),
('[Mon emprunt] Le Malade imaginaire', 'Moliere', '978-9000000026', 'Demo-MonEmprunt', 1673, 'emprunte', 1, 0, 10.50, 'Emprunte par le compte demo.'),
('[Mon emprunt] Le Cid', 'Corneille', '978-9000000027', 'Demo-MonEmprunt', 1637, 'emprunte', 1, 0, 9.50, 'Emprunte par le compte demo.'),
('[Mon emprunt] Phèdre', 'Racine', '978-9000000028', 'Demo-MonEmprunt', 1677, 'emprunte', 1, 0, 9.90, 'Emprunte par le compte demo.'),
('[Mon emprunt] Les Fleurs du Mal', 'Baudelaire', '978-9000000029', 'Demo-MonEmprunt', 1857, 'emprunte', 1, 0, 13.50, 'Emprunte par le compte demo.'),
('[Mon emprunt] Madame Bovary', 'Flaubert', '978-9000000030', 'Demo-MonEmprunt', 1857, 'emprunte', 1, 0, 14.90, 'Emprunte par le compte demo.');

-- 4. Rupture de stock (10)
INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, stock_total, stock_disponible, prix, description) VALUES
('[Rupture] Da Vinci Code', 'Dan Brown', '978-9000000031', 'Demo-Rupture', 2003, 'vendu', 0, 0, 15.90, 'Rupture de stock.'),
('[Rupture] Twilight', 'S. Meyer', '978-9000000032', 'Demo-Rupture', 2005, 'vendu', 0, 0, 12.90, 'Rupture de stock.'),
('[Rupture] Hunger Games', 'Suzanne Collins', '978-9000000033', 'Demo-Rupture', 2008, 'vendu', 0, 0, 14.90, 'Rupture de stock.'),
('[Rupture] Fifty Shades', 'E.L. James', '978-9000000034', 'Demo-Rupture', 2011, 'vendu', 0, 0, 13.90, 'Rupture de stock.'),
('[Rupture] Shining', 'Stephen King', '978-9000000035', 'Demo-Rupture', 1977, 'vendu', 0, 0, 16.90, 'Rupture de stock.'),
('[Rupture] Ca', 'Stephen King', '978-9000000036', 'Demo-Rupture', 1986, 'vendu', 0, 0, 18.90, 'Rupture de stock.'),
('[Rupture] Game of Thrones T1', 'G.R.R. Martin', '978-9000000037', 'Demo-Rupture', 1996, 'vendu', 0, 0, 19.90, 'Rupture de stock.'),
('[Rupture] Ready Player One', 'Ernest Cline', '978-9000000038', 'Demo-Rupture', 2011, 'vendu', 0, 0, 15.50, 'Rupture de stock.'),
('[Rupture] Sapiens', 'Yuval Harari', '978-9000000039', 'Demo-Rupture', 2011, 'vendu', 0, 0, 17.90, 'Rupture de stock.'),
('[Rupture] Atomic Habits', 'James Clear', '978-9000000040', 'Demo-Rupture', 2018, 'vendu', 0, 0, 16.50, 'Rupture de stock.');

-- 5. Reserves par la bibliotheque (10) — bloques emprunt/achat
INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, stock_total, stock_disponible, prix, description) VALUES
('[Reserve biblio] Le Nom de la Rose', 'Umberto Eco', '978-9000000041', 'Demo-ReserveBiblio', 1980, 'reserve', 2, 2, 16.90, 'Reserve par l administration.'),
('[Reserve biblio] L Oeuvre au noir', 'Marguerite Yourcenar', '978-9000000042', 'Demo-ReserveBiblio', 1968, 'reserve', 2, 2, 14.90, 'Reserve par l administration.'),
('[Reserve biblio] La Disparition', 'Georges Perec', '978-9000000043', 'Demo-ReserveBiblio', 1969, 'reserve', 1, 1, 13.90, 'Reserve par l administration.'),
('[Reserve biblio] Voyage au bout de la nuit', 'Louis-Ferdinand Celine', '978-9000000044', 'Demo-ReserveBiblio', 1932, 'reserve', 2, 2, 15.90, 'Reserve par l administration.'),
('[Reserve biblio] La Regente de Carthage', 'Yasmina Khadra', '978-9000000045', 'Demo-ReserveBiblio', 2007, 'reserve', 3, 3, 12.90, 'Reserve par l administration.'),
('[Reserve biblio] Les Bienveillantes', 'Jonathan Littell', '978-9000000046', 'Demo-ReserveBiblio', 2006, 'reserve', 2, 2, 22.90, 'Reserve par l administration.'),
('[Reserve biblio] HHhH', 'Laurent Binet', '978-9000000047', 'Demo-ReserveBiblio', 2010, 'reserve', 2, 2, 14.50, 'Reserve par l administration.'),
('[Reserve biblio] L Anomalie', 'Herve Le Tellier', '978-9000000048', 'Demo-ReserveBiblio', 2020, 'reserve', 2, 2, 18.90, 'Reserve par l administration.'),
('[Reserve biblio] Changer l eau des fleurs', 'Valerie Perrin', '978-9000000049', 'Demo-ReserveBiblio', 2018, 'reserve', 3, 3, 13.90, 'Reserve par l administration.'),
('[Reserve biblio] La Tresse', 'Laetitia Colombani', '978-9000000050', 'Demo-ReserveBiblio', 2017, 'reserve', 2, 2, 12.50, 'Reserve par l administration.');

-- 6. Reservables par le membre demo (10) — empruntes, pas encore reserves
INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, stock_total, stock_disponible, prix, description) VALUES
('[A reserver] Le Grand Meaulnes', 'Alain-Fournier', '978-9000000051', 'Demo-AReserver', 1913, 'emprunte', 1, 0, 11.90, 'Emprunte — vous pouvez reserver.'),
('[A reserver] Le Horla', 'Guy de Maupassant', '978-9000000052', 'Demo-AReserver', 1887, 'emprunte', 1, 0, 9.90, 'Emprunte — vous pouvez reserver.'),
('[A reserver] Le Pere Goriot', 'Balzac', '978-9000000053', 'Demo-AReserver', 1835, 'emprunte', 1, 0, 13.90, 'Emprunte — vous pouvez reserver.'),
('[A reserver] Les Confessions', 'Rousseau', '978-9000000054', 'Demo-AReserver', 1782, 'emprunte', 1, 0, 14.90, 'Emprunte — vous pouvez reserver.'),
('[A reserver] L Education sentimentale', 'Flaubert', '978-9000000055', 'Demo-AReserver', 1869, 'emprunte', 1, 0, 15.90, 'Emprunte — vous pouvez reserver.'),
('[A reserver] La Chartreuse de Parme', 'Stendhal', '978-9000000056', 'Demo-AReserver', 1839, 'emprunte', 1, 0, 16.90, 'Emprunte — vous pouvez reserver.'),
('[A reserver] Le Lys dans la vallee', 'Balzac', '978-9000000057', 'Demo-AReserver', 1835, 'emprunte', 1, 0, 12.90, 'Emprunte — vous pouvez reserver.'),
('[A reserver] Les Enfants du paradis', 'Prevert', '978-9000000058', 'Demo-AReserver', 1949, 'emprunte', 1, 0, 10.90, 'Emprunte — vous pouvez reserver.'),
('[A reserver] Le Diable au corps', 'Raymond Radiguet', '978-9000000059', 'Demo-AReserver', 1923, 'emprunte', 1, 0, 11.50, 'Emprunte — vous pouvez reserver.'),
('[A reserver] Bonjour tristesse', 'Francoise Sagan', '978-9000000060', 'Demo-AReserver', 1954, 'emprunte', 1, 0, 10.50, 'Emprunte — vous pouvez reserver.');

-- 7. Deja reserves par le membre demo (10)
INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, stock_total, stock_disponible, prix, description) VALUES
('[Deja reserve] Le Meilleur des mondes', 'Aldous Huxley', '978-9000000061', 'Demo-DejaReserve', 1932, 'emprunte', 1, 0, 13.90, 'Deja reserve par le compte demo.'),
('[Deja reserve] Brave New World', 'Aldous Huxley', '978-9000000062', 'Demo-DejaReserve', 1932, 'emprunte', 1, 0, 13.90, 'Deja reserve par le compte demo.'),
('[Deja reserve] Solaris', 'Stanislaw Lem', '978-9000000063', 'Demo-DejaReserve', 1961, 'emprunte', 1, 0, 14.90, 'Deja reserve par le compte demo.'),
('[Deja reserve] Fondation', 'Isaac Asimov', '978-9000000064', 'Demo-DejaReserve', 1951, 'emprunte', 1, 0, 15.90, 'Deja reserve par le compte demo.'),
('[Deja reserve] Neuromancien', 'William Gibson', '978-9000000065', 'Demo-DejaReserve', 1984, 'emprunte', 1, 0, 16.90, 'Deja reserve par le compte demo.'),
('[Deja reserve] Ubik', 'Philip K. Dick', '978-9000000066', 'Demo-DejaReserve', 1969, 'emprunte', 1, 0, 14.50, 'Deja reserve par le compte demo.'),
('[Deja reserve] Hyperion', 'Dan Simmons', '978-9000000067', 'Demo-DejaReserve', 1989, 'emprunte', 1, 0, 17.90, 'Deja reserve par le compte demo.'),
('[Deja reserve] La Main gauche', 'Ursula Le Guin', '978-9000000068', 'Demo-DejaReserve', 1969, 'emprunte', 1, 0, 13.50, 'Deja reserve par le compte demo.'),
('[Deja reserve] Les Robots', 'Isaac Asimov', '978-9000000069', 'Demo-DejaReserve', 1950, 'emprunte', 1, 0, 12.90, 'Deja reserve par le compte demo.'),
('[Deja reserve] Ender s Game', 'Orson Scott Card', '978-9000000070', 'Demo-DejaReserve', 1985, 'emprunte', 1, 0, 15.50, 'Deja reserve par le compte demo.');

-- 8. Deja achetes par le membre demo (10)
INSERT INTO livres (titre, auteur, isbn, categorie, annee, statut, stock_total, stock_disponible, prix, description) VALUES
('[Deja achete] Le K', 'Bernard Werber', '978-9000000071', 'Demo-DejaAchete', 2003, 'vendu', 0, 0, 14.90, 'Achete par le compte demo.'),
('[Deja achete] Les Thanatonautes', 'Bernard Werber', '978-9000000072', 'Demo-DejaAchete', 1994, 'vendu', 0, 0, 13.90, 'Achete par le compte demo.'),
('[Deja achete] Le Papillon des etoiles', 'Bernard Werber', '978-9000000073', 'Demo-DejaAchete', 2006, 'vendu', 0, 0, 15.90, 'Achete par le compte demo.'),
('[Deja achete] L Empire des Anges', 'Bernard Werber', '978-9000000074', 'Demo-DejaAchete', 2000, 'vendu', 0, 0, 14.50, 'Achete par le compte demo.'),
('[Deja achete] Nos amis les humains', 'Bernard Werber', '978-9000000075', 'Demo-DejaAchete', 2003, 'vendu', 0, 0, 12.90, 'Achete par le compte demo.'),
('[Deja achete] Le Cycle des dieux T1', 'Bernard Werber', '978-9000000076', 'Demo-DejaAchete', 2004, 'vendu', 0, 0, 16.90, 'Achete par le compte demo.'),
('[Deja achete] Le Livre des voyages', 'Bernard Werber', '978-9000000077', 'Demo-DejaAchete', 2009, 'vendu', 0, 0, 15.50, 'Achete par le compte demo.'),
('[Deja achete] Le Mystere des dieux', 'Bernard Werber', '978-9000000078', 'Demo-DejaAchete', 2007, 'vendu', 0, 0, 14.90, 'Achete par le compte demo.'),
('[Deja achete] Le Souffle des dieux', 'Bernard Werber', '978-9000000079', 'Demo-DejaAchete', 2011, 'vendu', 0, 0, 16.50, 'Achete par le compte demo.'),
('[Deja achete] Le Regne des animaux', 'Bernard Werber', '978-9000000080', 'Demo-DejaAchete', 2010, 'vendu', 0, 0, 15.90, 'Achete par le compte demo.');

-- Emprunts actifs
INSERT INTO emprunts (utilisateur_id, livre_id, date_retour_prevue)
SELECT @alice, id, DATE_ADD(CURDATE(), INTERVAL 14 DAY) FROM livres WHERE categorie = 'Demo-Emprunte';

INSERT INTO emprunts (utilisateur_id, livre_id, date_retour_prevue)
SELECT @demo, id, DATE_ADD(CURDATE(), INTERVAL 14 DAY) FROM livres WHERE categorie = 'Demo-MonEmprunt';

INSERT INTO emprunts (utilisateur_id, livre_id, date_retour_prevue)
SELECT @bob, id, DATE_ADD(CURDATE(), INTERVAL 14 DAY) FROM livres WHERE categorie = 'Demo-AReserver';

INSERT INTO emprunts (utilisateur_id, livre_id, date_retour_prevue)
SELECT @alice, id, DATE_ADD(CURDATE(), INTERVAL 14 DAY) FROM livres WHERE categorie = 'Demo-DejaReserve';

-- Reservations actives (demo)
INSERT INTO reservations (utilisateur_id, livre_id)
SELECT @demo, id FROM livres WHERE categorie = 'Demo-DejaReserve';

-- Achats (demo)
INSERT INTO achats (utilisateur_id, livre_id, prix_paye)
SELECT @demo, id, prix FROM livres WHERE categorie = 'Demo-DejaAchete';
