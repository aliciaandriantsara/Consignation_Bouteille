<?php

class LogisticienModel {
    private PDO $db;
    public function __construct() {
        $this->db = getDB();
        //getDB() est une fonction definie dans app/helpers/db.php qui retourne un objet PDO representant la connexion a la base de donnees
    }

    //trouver un logisticien par son email et retourne un tableau associatif representant le logisticien ou null si le logisticien n'existe pas
    public function findByEmail(string $email): ?array {
        $s = $this->db->prepare('SELECT * FROM logisticien WHERE email_utilisateur = ?');
        $s->execute([$email]);
        return $s->fetch() ?: null;
    }

    public function findByCIN(string $CIN): ?array {
        $s = $this->db->prepare('SELECT * FROM logisticien WHERE CIN = ?');
        $s->execute([$CIN]);
        return $s->fetch() ?: null;
    }

    public function creer(string $CIN, string $email, string $nomEntreprise, string $nom, string $prenom): void {
        $s = $this->db->prepare('INSERT INTO logisticien (CIN, email_utilisateur, nom_entreprise, nom, prenom) VALUES (?, ?, ?, ?, ?)');
        $s->execute([$CIN, $email, $nomEntreprise, $nom, $prenom]);
    }
}

?>