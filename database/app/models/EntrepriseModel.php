<?php

class EntrepriseModel {
    private PDO $db;
    //PHP Data Object: bibliotheque php qui sert a communiquer avec une base de donnes
    //la securite contre les injections sql
    //support de plusieurs base de donnees
    //requete preparees
    //gestion propre des erreurs

    public function __construct() {
        $this->db = getDB();
        //getDB() est une fonction definie dans app/helpers/db.php qui retourne un objet PDO representant la connexion a la base de donnees
    }

    public function findByEmail(string $email): ?array {
        //trouver une entreprise par son email et retourne un tableau associatif representant l'entreprise ou null si l'entreprise n'existe pas
        $s = $this->db->prepare('SELECT * FROM entreprise WHERE email_entreprise = ?');
        $s->execute([$email]);

        return $s->fetch() ?: null;//fetch recupere une ligne de la resultat du requete mysql
    }

    public function findByNom(string $nom): ?array {
        //trouver une entreprise par son nom et retourne un tableau associatif representant l'entreprise ou null si l'entreprise n'existe pas
        $s = $this->db->prepare('SELECT * FROM entreprise WHERE nom_entreprise = ?');
        $s->execute([$nom]);
        return $s->fetch() ?: null;//recupere une ligne de la resultat du requete mysql
    }

    //liste de tous les livreurs d'une entreprise 
    public function allLivreurs() : array {
        return $this->db->query("SELECT l.CIN, l.nom, l.prenom FROM livreur l JOIN utilisateur u ON u.email = l.email_utilisateur WHERE u.statut_compte = 'actif' ORDER BY l.nom") -> fetchAll();
    }

    //liste tous les revendeurs d'une entreprise 
    public function allRevendeurs(string $nomEntreprise): array {
        $s = $this->db->prepare('SELECT nom_boutique, adresse FROM revendeur WHERE nom_entreprise = ?');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }
}
?>