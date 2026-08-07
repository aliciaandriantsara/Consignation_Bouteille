<?php

class CommandeModel {
    private PDO $db;
    public function __construct() {
        $this->db = getDB();
    }

    public function creer(string $nomBoutique, string $nomEntreprise, int $quantite): void {
        $s = $this->db->prepare('INSERT INTO commande (nom_boutique_actuel, nom_entreprise, date_commande, quantite) VALUES (?, ?, NOW(), ?)');
        $s->execute([$nomBoutique, $nomEntreprise, $quantite]);

    }

    public function byRevendeur(string $nomBoutique): array {
        $s = $this->db->prepare('SELECT * FROM commande WHERE nom_boutique_actuel = ? ORDER BY date_commande DESC');
        $s->execute([$nomBoutique]);
        return $s->fetchAll();
    }

    public function byEntreprise(string $nomEntreprise): array {
        $s = $this->db->prepare('SELECT c.*, r.adresse FROM commande c LEFT JOIN revendeur r ON r.nom_boutique = c.nom_boutique_actuel WHERE c.nom_entreprise = ? ORDER BY c.date_commande DESC');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }

    public function updateStatut(string $nomBoutique, string $nomEntreprise, string $dateCommande, string $statut): void {
        $s = $this->db->prepare('UPDATE commande SET statut = ? WHERE nom_boutique_actuel = ? AND nom_entreprise = ? AND date_commande = ?');
        $s->execute([$statut, $nomBoutique, $nomEntreprise, $dateCommande]);
    }

    public function findOne(string $nomBoutique, string $nomEntreprise, string $dateCommande): ?array {
        $s = $this->db->prepare('SELECT * FROM commande WHERE nom_boutique_actuel = ? AND nom_entreprise = ? AND date_commande = ?');
        $s->execute([$nomBoutique, $nomEntreprise, $dateCommande]);
        return $s->fetch() ?: null;
    }
}
?>