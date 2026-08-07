<?php
class LivraisonModel {
    private PDO $db;
    public function __construct() { 
        $this->db = getDB(); 
    }

    // Créer une nouvelle livraison
    public function creer(string $cinLivreur, string $nomBoutique, string $nomEntreprise, string $dateCommande, string $datePrevue): void {
        $s = $this->db->prepare('INSERT INTO livraison (CIN_livreur, nom_boutique_actuel, nom_entreprise, date_commande, date_livraison_prevue) VALUES (?, ?, ?, ?, ?)');
        $s->execute([$cinLivreur, $nomBoutique, $nomEntreprise, $dateCommande, $datePrevue]);
    }

    //selectionne toutes les livraisons d'un revendeur a effectuer par un livreur 
    public function byLivreur(string $cinLivreur): array {
        $s = $this->db->prepare('SELECT l.*, c.quantite, c.statut AS commande_statut, r.adresse FROM livraison l JOIN commande c ON  c.nom_boutique_actuel = l.nom_boutique_actuel AND c.nom_entreprise = l.nom_entreprise AND c.date_commande = l.date_commande LEFT JOIN revendeur r ON r.nom_boutique = l.nom_boutique_actuel WHERE l.CIN_livreur = ? ORDER BY l.date_livraison_prevue ASC');
        $s->execute([$cinLivreur]);
        return $s->fetchAll();
    }

    //selectionne toutes les livraisons d'une entreprise a effectuer par un livreur
    public function marquerEffectuee(string $cinLivreur, string $nomBoutique, string $nomEntreprise, string $dateCommande): bool {
        $s = $this->db->prepare("UPDATE livraison SET statut = 'EFFECTUEE', date_livraison_effective = NOW() WHERE CIN_livreur = ? AND nom_boutique_actuel  = ? AND nom_entreprise = ? AND date_commande= ? AND statut != 'EFFECTUEE'");
        $s->execute([$cinLivreur, $nomBoutique, $nomEntreprise, $dateCommande]);
        return $s->rowCount() > 0;
    }
}
