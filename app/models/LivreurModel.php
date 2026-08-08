<?php

class LivreurModel
{
    private PDO $db;
    //PHP Data Object: bibliotheque php qui sert a communiquer avec une base de donnes
    //la securite contre les injections sql
    //support de plusieurs base de donnees
    //requete preparees
    //gestion propre des erreurs

    public function __construct()
    {
        $this->db = getDB();
        //getDB() est une fonction definie dans app/helpers/db.php qui retourne un objet PDO representant la connexion a la base de donnees
    }
    //trouver un livreur par son email et retourne un tableau associatif representant le livreur ou null si le livreur n'existe pas

    public function findByEmail(string $email): ?array
    {
        $s = $this->db->prepare('SELECT * FROM livreur WHERE email_utilisateur = ?');
        $s->execute([$email]);
        return $s->fetch() ?: null;
    }

    public function findByCIN(string $CIN): ?array
    {
        $s = $this->db->prepare('SELECT * FROM livreur WHERE CIN = ?');
        $s->execute([$CIN]);
        return $s->fetch() ?: null;
    }

    public function creer(string $CIN, string $email, string $nom, string $prenom): void
    {
        $s = $this->db->prepare('INSERT INTO livreur (CIN, email_utilisateur, nom, prenom) VALUES (?, ?, ?, ?)');
        $s->execute([$CIN, $email, $nom, $prenom]);
    }
}
