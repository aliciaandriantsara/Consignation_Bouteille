<?php

class RevendeurModel
{
    private PDO $db;
    public function __construct()
    {
        $this->db = getDB();
    }

    public function findByEmail(string $email): ?array
    {
        $s = $this->db->prepare('SELECT * FROM revendeur WHERE email_utilisateur = ?');
        $s->execute([$email]);
        return $s->fetch() ?: null;
    }

    // public function findByNomBoutique(string $nom): ?array
    // {
    //     $s = $this->db->prepare('SELECT * FROM revendeur WHERE nom_boutique = ?');
    //     $s->execute([$nom]);
    //     return $s->fetch() ?: null;
    // }

    public function findByNomBoutique(string $nomBoutique): ?array
    {
        $s = $this->db->prepare('SELECT * FROM revendeur WHERE nom_boutique = ?');
        $s->execute([$nomBoutique]);
        return $s->fetch() ?: null;
    }

    public function creer(string $nomBoutique, string $email, string $nomEntreprise, ?string $adresse): void
    {
        $s = $this->db->prepare('INSERT INTO revendeur (nom_boutique, email_utilisateur, nom_entreprise, adresse) VALUES (?, ?, ?, ?)');
        $s->execute([$nomBoutique, $email, $nomEntreprise, $adresse]);
    }
}
