<?php

class EntrepriseModel {
    private PDO $db;
    public function __construct() {
        $this->db = getDB();
    }

    public function findByEmail(string $email): ?array {
        $s = $this->db->prepare('SELECT * FROM entreprise WHERE email_entreprise = ?');
        $s->execute([$email]);

        return $s->fetch() ?: null;
    }

    public function findByNom(string $nom): ?array {
        $s = $this->db->prepare('SELECT * FROM entreprise WHERE nom_entreprise = ?');
        $s->execute([$nom]);
        return $s->fetch() ?: null;
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