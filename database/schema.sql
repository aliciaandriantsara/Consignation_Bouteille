

CREATE DATABASE IF NOT EXISTS consignation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE consignation;

CREATE TABLE utilisateur (
    email VARCHAR(191) NOT NULL PRIMARY KEY,
    mot_de_passe VARCHAR(255) NOT NULL, 
    role ENUM('entreprise', 'revendeur', 'client', 'livreur', 'logisticien') NOT NULL,
    statut_compte ENUM('actif', 'inactif', 'suspendu') NOT NULL DEFAULT 'actif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE entreprise (
    nom_entreprise VARCHAR(191) NOT NULL PRIMARY KEY,
    email_entreprise VARCHAR(191) NOT NULL,
    FOREIGN KEY (email_entreprise) REFERENCES utilisateur(email) ON DELETE CASCADE
);

CREATE TABLE revendeur (
    nom_boutique VARCHAR(191) NOT NULL PRIMARY KEY,
    email_utilisateur VARCHAR(191) NOT NULL UNIQUE,
    nom_entreprise VARCHAR(191) NOT NULL,
    adresse VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (email_utilisateur) REFERENCES utilisateur(email) ON DELETE CASCADE,
    FOREIGN KEY (nom_entreprise) REFERENCES entreprise(nom_entreprise) ON DELETE CASCADE
);

CREATE TABLE client (
    CIN VARCHAR(50) NOT NULL PRIMARY KEY,
    email_utilisateur VARCHAR(191) NOT NULL UNIQUE, 
    nom VARCHAR(100) NOT NULL, 
    prenom VARCHAR(100) NOT NULL,
    telephone VARCHAR(20) DEFAULT NULL,
    FOREIGN KEY (email_utilisateur) REFERENCES utilisateur(email) ON DELETE CASCADE
);

CREATE TABLE livreur (
    CIN VARCHAR(50) NOT NULL PRIMARY KEY,
    email_utilisateur VARCHAR(191) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL, 
    FOREIGN KEY (email_utilisateur) REFERENCES utilisateur(email) ON DELETE CASCADE
);

CREATE TABLE logisticien (
    CIN VARCHAR(50) NOT NULL PRIMARY KEY,
    email_utilisateur VARCHAR(191) NOT NULL UNIQUE,
    nom_entreprise VARCHAR(191) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL, 
    FOREIGN KEY (email_utilisateur) REFERENCES utilisateur(email) ON DELETE CASCADE,
    FOREIGN KEY (nom_entreprise) REFERENCES entreprise(nom_entreprise) ON DELETE CASCADE
);

CREATE TABLE stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom_entreprise VARCHAR(191) NOT NULL, 
    nombre_bouteilles_propres INT NOT NULL DEFAULT 0,
    nombre_bouteilles_a_laver INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nom_entreprise) REFERENCES entreprise(nom_entreprise) ON DELETE CASCADE
);

CREATE TABLE bouteille (
    qr_code VARCHAR(191) NOT NULL PRIMARY KEY,
    nom_entreprise VARCHAR(191) NOT NULL,
    nom_boutique_actuel VARCHAR(191) DEFAULT NULL,
    statut ENUM('DISPONIBLE_REVENDEUR', 'EMPRUNTEE', 'RENDUE_REVENDEUR', 'EN_COLLECTE', 'EN_STOCK_ENTREPRISE', 'A_LAVER', 'PROPRE', 'DISPONIBLE_STOCK', 'LIVREE_REVENDEUR') NOT NULL DEFAULT 'DISPONIBLE_STOCK',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (nom_entreprise) REFERENCES entreprise(nom_entreprise) ON DELETE CASCADE,
    FOREIGN KEY (nom_boutique_actuel) REFERENCES revendeur(nom_boutique) ON DELETE SET NULL
);

CREATE TABLE commande (
    nom_boutique_actuel VARCHAR(191) NOT NULL,
    nom_entreprise VARCHAR(191) NOT NULL,
    date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    quantite INT NOT NULL CHECK (quantite > 0),
    statut ENUM('EN_ATTENTE', 'VALIDEE', 'LIVREE', 'EN_PREPARATION', 'EN_LIVRAISON', 'ANNULEE') NOT NULL DEFAULT 'EN_ATTENTE',
    PRIMARY KEY (nom_boutique_actuel, nom_entreprise, date_commande),
    FOREIGN KEY (nom_boutique_actuel) REFERENCES revendeur(nom_boutique) ON DELETE CASCADE,
    FOREIGN KEY (nom_entreprise) REFERENCES entreprise(nom_entreprise) ON DELETE CASCADE    
);

-- flux : entreprise -> revendeur
CREATE TABLE livraison (
    CIN_livreur VARCHAR(50) NOT NULL,
    nom_boutique_actuel VARCHAR(191) NOT NULL,
    nom_entreprise VARCHAR(191) NOT NULL,
    date_commande DATETIME NOT NULL,
    date_livraison_prevue DATE NOT NULL, 
    date_livraison_effective DATETIME DEFAULT NULL, 
    statut ENUM('ASSIGNEE', 'EN_COURS', 'EFFECTUEE', 'ECHEC') NOT NULL DEFAULT 'ASSIGNEE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (CIN_livreur, nom_boutique_actuel, nom_entreprise, date_commande),
    FOREIGN KEY (CIN_livreur) REFERENCES livreur(CIN) ON DELETE CASCADE,
    FOREIGN KEY (nom_boutique_actuel) REFERENCES revendeur(nom_boutique) ON DELETE CASCADE,
    FOREIGN KEY (nom_entreprise) REFERENCES entreprise(nom_entreprise) ON DELETE CASCADE,
    FOREIGN KEY (nom_boutique_actuel, nom_entreprise, date_commande) REFERENCES commande(nom_boutique_actuel, nom_entreprise, date_commande) ON DELETE CASCADE
);

-- flux : revendeur -> client
CREATE TABLE collecte (
    nom_boutique_actuel VARCHAR(191) NOT NULL,
    nom_entreprise VARCHAR(191) NOT NULL,
    CIN_livreur VARCHAR(50) NOT NULL,
    date_collecte_prevue DATE NOT NULL,
    date_collecte_effective DATETIME DEFAULT NULL,
    statut ENUM('ASSIGNEE', 'EN_COURS', 'EFFECTUEE', 'ECHEC') NOT NULL DEFAULT 'ASSIGNEE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (nom_boutique_actuel, nom_entreprise, CIN_livreur, date_collecte_prevue),
    FOREIGN KEY (nom_boutique_actuel) REFERENCES revendeur(nom_boutique) ON DELETE CASCADE,
    FOREIGN KEY (nom_entreprise) REFERENCES entreprise(nom_entreprise) ON DELETE CASCADE,
    FOREIGN KEY (CIN_livreur) REFERENCES livreur(CIN) ON DELETE CASCADE     
);

-- bouteille concernee par une collecte relation n-n entre collecte et bouteille
CREATE TABLE collecte_bouteille (
    qr_code_bouteille VARCHAR(191) NOT NULL,
    nom_boutique_actuel VARCHAR(191) NOT NULL,
    nom_entreprise VARCHAR(191) NOT NULL,
    CIN_livreur VARCHAR(50) NOT NULL,
    date_collecte_prevue DATE NOT NULL,
    PRIMARY KEY (qr_code_bouteille, nom_boutique_actuel, nom_entreprise, CIN_livreur, date_collecte_prevue),
    FOREIGN KEY (qr_code_bouteille) REFERENCES bouteille(qr_code) ON DELETE CASCADE,
    FOREIGN KEY (nom_boutique_actuel, nom_entreprise, CIN_livreur, date_collecte_prevue) REFERENCES collecte(nom_boutique_actuel, nom_entreprise, CIN_livreur, date_collecte_prevue) ON DELETE CASCADE
);

CREATE TABLE lavage (
    qr_code_bouteille VARCHAR(191) NOT NULL,
    CIN_logisticien VARCHAR(50) NOT NULL,
    date_debut DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    date_fin DATETIME DEFAULT NULL,
    statut ENUM('EN_COURS', 'TERMINE') NOT NULL DEFAULT 'EN_COURS',
    PRIMARY KEY (qr_code_bouteille, CIN_logisticien, date_debut),
    FOREIGN KEY (CIN_logisticien) REFERENCES logisticien(CIN) ON DELETE CASCADE,
    FOREIGN KEY (qr_code_bouteille) REFERENCES bouteille(qr_code) ON DELETE CASCADE
);


INSERT INTO utilisateur (email, mot_de_passe, role, statut_compte) VALUES
('entreprise@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'entreprise',  'actif'),
('revendeur@test.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'revendeur',   'actif'),
('client@test.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client',      'actif'),
('livreur@test.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'livreur',     'actif'),
('logisticien@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'logisticien', 'actif');

INSERT INTO entreprise (nom_entreprise, email_entreprise)
    VALUES ('AquaConsigne SA', 'entreprise@test.com');

INSERT INTO revendeur (nom_boutique, email_utilisateur, nom_entreprise, adresse)
    VALUES ('Boutique Centre', 'revendeur@test.com', 'AquaConsigne SA','12 Rue du Marché, Antananarivo');

INSERT INTO client (CIN, email_utilisateur, nom, prenom, telephone)
    VALUES ('108092020915', 'client@test.com', 'Rakoto', 'Jean', '034 00 000 00');

INSERT INTO livreur (CIN, email_utilisateur, nom, prenom)
    VALUES ('108092020916', 'livreur@test.com', 'Rabe', 'Paul');

INSERT INTO logisticien (CIN, email_utilisateur, nom_entreprise, nom, prenom)
    VALUES ('108092020917', 'logisticien@test.com', 'AquaConsigne SA', 'Rasoa', 'Marie');

INSERT INTO stock (nom_entreprise, nombre_bouteilles_propres, nombre_bouteilles_a_laver)
    VALUES ('AquaConsigne SA', 50, 5);

-- Bouteilles : quelques-unes chez le revendeur, d'autres en stock
INSERT INTO bouteille (qr_code, nom_entreprise, nom_boutique_actuel, statut) VALUES
('BTL-0001', 'AquaConsigne SA', 'Boutique Centre', 'DISPONIBLE_REVENDEUR'),
('BTL-0002', 'AquaConsigne SA', 'Boutique Centre', 'DISPONIBLE_REVENDEUR'),
('BTL-0003', 'AquaConsigne SA', 'Boutique Centre', 'DISPONIBLE_REVENDEUR'),
('BTL-0004', 'AquaConsigne SA', 'Boutique Centre', 'DISPONIBLE_STOCK'),
('BTL-0005', 'AquaConsigne SA', 'Boutique Centre', 'DISPONIBLE_STOCK'),
('BTL-0006', 'AquaConsigne SA', 'Boutique Centre', 'PROPRE'),
('BTL-0007', 'AquaConsigne SA', 'Boutique Centre', 'A_LAVER');