<?php

class ClientModel
{
    private PDO $db;
    public function __construct()
    {
        $this->db = getDB();
    }

    //trouver un client par son email et retourne un tableau associatif representant le client ou null si le client n'existe pas
    public function findByEmail(string $email): ?array
    {
        $s = $this->db->prepare('SELECT * FROM client WHERE email_utilisateur = ?');
        $s->execute([$email]);
        return $s->fetch() ?: null;
    }

    //trouver un client par son CIN et retourne un tableau associatif representant le client ou null si le client n'existe pas
    public function findByCIN(string $CIN): ?array
    {
        $s = $this->db->prepare('SELECT * FROM client WHERE CIN = ?');
        $s->execute([$CIN]);
        return $s->fetch() ?: null;
    }

    //Recherche pour le revendeur lors d'un emprunt
    public function search(string $q): array
    {
        //% signifie n'importe quel suite de caractere
        $like = '%' . $q . '%';
        //on ne recupere que les 20 premiers resultat
        $s = $this->db->prepare('SELECT c.CIN, c.nom, c.prenom, c.telephone, c.email_utilisateur FROM client c WHERE c.nom LIKE ? OR c.prenom LIKE ? OR c.CIN LIKE ? OR c.email_utilisateur LIKE ? LIMIT 20');
        $s->execute([$like, $like, $like, $like]);
        return $s->fetchAll();
    }

    // Créer une nouvelle fiche client
    public function creer(string $CIN, string $email, string $nom, string $prenom, ?string $telephone): void
    {
        $s = $this->db->prepare('INSERT INTO client (CIN, email_utilisateur, nom, prenom, telephone) VALUES (?, ?, ?, ?, ?)');
        $s->execute([$CIN, $email, $nom, $prenom, $telephone]);
    }
}
