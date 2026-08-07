CREATE DATABASE IF NOT EXISTS consignation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE consignation;

CREATE TABLE utilisateur (
    email VARCHAR(191) NOT NULL UNIQUE PRIMARY KEY, 
    mot_de_passe VARCHAR(255) NOT NULL, 
    role ENUM('entreprise', 'revendeur', 'client', 'livreur', 'logisticien') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE entreprise (
    email_utilisateur VARCHAR(191) NOT NULL UNIQUE PRIMARY KEY,
    nom_entreprise VARCHAR(191) NOT NULL UNIQUE,
    FOREIGN KEY (email_utilisateur) REFERENCES utilisateur(email) ON DELETE CASCADE
);

CREATE TABLE revendeur (
    nom_boutique VARCHAR(191) NOT NULL PRIMARY KEY,
    email_entreprise VARCHAR(191) NOT NULL,
    email_utilisateur VARCHAR(191) NOT NULL UNIQUE,
    FOREIGN KEY (email_entreprise) REFERENCES entreprise(email_utilisateur) ON DELETE CASCADE,
    FOREIGN KEY (email_utilisateur) REFERENCES utilisateur(email)
);

CREATE TABLE client (
    CIN VARCHAR(50) NOT NULL UNIQUE PRIMARY KEY,
    email_utilisateur VARCHAR(191) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    FOREIGN KEY (email_utilisateur) REFERENCES utilisateur(email) ON DELETE CASCADE
);

CREATE TABLE livreur (
    CIN VARCHAR(50) NOT NULL UNIQUE PRIMARY KEY,
    nom VARCHAR(100) NOT NULL, 
    prenom VARCHAR(100) NOT NULL, 
    email_utilisateur VARCHAR(191) NOT NULL UNIQUE,
    FOREIGN KEY(email_utilisateur) REFERENCES utilisateur(email) ON DELETE CASCADE
);

CREATE TABLE logisticien (
    CIN VARCHAR(50) NOT NULL UNIQUE PRIMARY KEY,
    nom VARCHAR(100) NOT NULL, 
    prenom vARCHAR(100) NOT NULL, 
    email_entreprise VARCHAR(191) NOT NULL,
    email_utilisateur VARCHAR(191) NOT NULL UNIQUE,
    FOREIGN KEY (email_utilisateur) REFERENCES utilisateur(email) ON DELETE CASCADE,
    FOREIGN KEY (email_entreprise) REFERENCES entreprise(email_utilisateur) ON DELETE CASCADE
);

CREATE TABLE stock (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email_entreprise VARCHAR(191) NOT NULL UNIQUE,
    nombre_propres INT NOT NULL DEFAULT 0,
    nombre_lavables INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (email_entreprise) REFERENCES entreprise(email_utilisateur)  
);

CREATE TABLE bouteille (
    qr_code VARCHAR(191) NOT NULL UNIQUE PRIMARY KEY,
    email_entreprise VARCHAR(191) NOT NULL, 
    status ENUM('disponible',  'emprunte', 'rendu', 'en_livraison', 'a_laver', 'lavage_en_cours') NOT NULL DEFAULT 'disponible',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (email_entreprise) REFERENCES entreprise(email_utilisateur)
);

CREATE TABLE transaction_bouteille (
    bouteille_qr_code VARCHAR(191) NOT NULL, 
    CIN_client  VARCHAR(50) NOT NULL,
    nom_boutique_revendeur VARCHAR(191) NOT NULL,
    date_emprunt DATETIME NOT NULL,
    date_rendu DATETIME DEFAULT NULL, 
    FOREIGN KEY (bouteille_qr_code) REFERENCES bouteille(qr_code),
    FOREIGN KEY (nom_boutique_revendeur) REFERENCES revendeur(nom_boutique),
    FOREIGN KEY (CIN_client) REFERENCES client(CIN)
);

CREATE TABLE commande (
    nom_boutique_revendeur VARCHAR(191) NOT NULL,
    nom_entreprise VARCHAR(191) NOT NULL,
    date_commande DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    quantite INT NOT NULL, 
    statut ENUM('en_attente', 'validee', 'en_livraison', 'livree') NOT NULL DEFAULT 'en_attente',
    FOREIGN KEY (nom_boutique_revendeur) REFERENCES revendeur(nom_boutique),
    FOREIGN KEY (nom_entreprise) REFERENCES entreprise(nom_entreprise) 
);

CREATE TABLE livraison (
    livreur_CIN VARCHAR(50) NOT NULL,
    nom_boutique_revendeur VARCHAR(191) NOT NULL UNIQUE,
    id_stock INT NOT NULL UNIQUE,
    type_livraison ENUM('retour_stock', 'livraison_revendeur') NOT NULL,
    date_livraison DATE NOT NULL,
    effectuee TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nom_boutique_revendeur) REFERENCES revendeur(nom_boutique),
    FOREIGN KEY (id_stock) REFERENCES stock(id),
    FOREIGN KEY(livreur_CIN) REFERENCES livreur(CIN)
);

CREATE TABLE collecte (
    livreur_CIN VARCHAR(50) NOT NULL, 
    bouteille_qr_code VARCHAR(191) NOT NULL,
    revendeur_nom_boutique VARCHAR(191) NOT NULL,
    date_collection DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (livreur_CIN) REFERENCES livreur(CIN),
    FOREIGN KEY (bouteille_qr_code) REFERENCES bouteille(qr_code),
    FOREIGN KEY (revendeur_nom_boutique) REFERENCES revendeur(nom_boutique)
);

-- Données de test (mot de passe = "password")
INSERT INTO utilisateur (email, mot_de_passe, role) VALUES
('entreprise@test.com',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'entreprise'),
('revendeur@test.com',   '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'revendeur'),
('client@test.com',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'client'),
('livreur@test.com',     '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'livreur'),
('logisticien@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'logisticien');

INSERT INTO entreprise  (email_utilisateur, nom_entreprise)                              VALUES ('entreprise@test.com', 'AquaConsigne SA');
INSERT INTO revendeur   (nom_boutique, email_entreprise, email_utilisateur)              VALUES ('Boutique Centre', 'entreprise@test.com', 'revendeur@test.com');
INSERT INTO client      (CIN, email_utilisateur, nom, prenom)                            VALUES ('108092020915', 'client@test.com', 'Rakoto', 'Jean');
INSERT INTO livreur     (CIN, nom, prenom, email_utilisateur)                            VALUES ('108092020916', 'Rabe', 'Paul', 'livreur@test.com');
INSERT INTO logisticien (CIN, nom, prenom, email_entreprise, email_utilisateur)          VALUES ('108092020917', 'Rasoa', 'Marie', 'entreprise@test.com', 'logisticien@test.com');
INSERT INTO stock       (email_entreprise, nombre_propres, nombre_lavables)              VALUES ('entreprise@test.com', 50, 10);

INSERT INTO bouteille (qr_code, status, email_entreprise) VALUES
('BTL-0001','disponible', 'entreprise@test.com'),('BTL-0002','disponible', 'entreprise@test.com'),
('BTL-0003','disponible', 'entreprise@test.com'),('BTL-0004','disponible', 'entreprise@test.com'),('BTL-0005','disponible', 'entreprise@test.com');

