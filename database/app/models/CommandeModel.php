<?php
class CommandeModel {
    private PDO $db;

    public function __construct() { 
        $this->db = getDB(); 
    }

    /// Créer une nouvelle commande
    public function creer(string $nomBoutique, string $nomEntreprise, int $quantite): void {
        $s = $this->db->prepare('INSERT INTO commande(nom_boutique_actuel, nom_entreprise, date_commande, quantite) VALUES (?, ?, NOW(), ?)');
        $s->execute([$nomBoutique, $nomEntreprise, $quantite]);
    }

    //selectionne toutes les commandes d'un revendeur
    public function byRevendeur(string $nomBoutique): array {
        $s = $this->db->prepare('SELECT * FROM commande WHERE nom_boutique_actuel = ? ORDER BY date_commande DESC');
        $s->execute([$nomBoutique]);
        return $s->fetchAll();
    }

    //selectionne toutes les commandes d'une entreprise
    public function byEntreprise(string $nomEntreprise): array {
        $s = $this->db->prepare('SELECT c.*, r.adresse FROM commande c LEFT JOIN revendeur r ON r.nom_boutique = c.nom_boutique_actuel WHERE c.nom_entreprise = ? ORDER BY c.date_commande DESC');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }

    //definir le status d'une commande 
    public function updateStatut(string $nomBoutique, string $nomEntreprise, string $dateCommande, string $statut): void {
        $s = $this->db->prepare('UPDATE commande SET statut = ? WHERE nom_boutique_actuel = ? AND nom_entreprise = ? AND date_commande = ?');
        $s->execute([$statut, $nomBoutique, $nomEntreprise, $dateCommande]);
    }

    //selectionne une commande spécifique
    public function findOne(string $nomBoutique, string $nomEntreprise, string $dateCommande): ?array {
        $s = $this->db->prepare('SELECT * FROM commande WHERE nom_boutique_actuel = ? AND nom_entreprise = ? AND date_commande = ?');
        $s->execute([$nomBoutique, $nomEntreprise, $dateCommande]);
        return $s->fetch() ?: null;
    }

    // Affecter des bouteilles à une commande
    public function affecterBouteilles(string $nomBoutique, string $nomEntreprise, string $dateCommande, array  $qrCodes): void {
        //INSERT IGNORE si une erreur survient lors d'une insertion pour une ligne deja exsitatnte, ignore cette ligne et continue l'insertion des autres lignes    
        $s = $this->db->prepare('INSERT IGNORE INTO commande_bouteille(nom_boutique_actuel, nom_entreprise, date_commande, qr_code_bouteille) VALUES (?, ?, ?, ?)');
        foreach ($qrCodes as $qr) {
            $s->execute([$nomBoutique, $nomEntreprise, $dateCommande, $qr]);
        }
    }

    // Récupérer les bouteilles affectées à une commande
    public function bouteillesDeCommande(string $nomBoutique, string $nomEntreprise, string $dateCommande): array {
        $s = $this->db->prepare('SELECT b.* FROM bouteille b JOIN commande_bouteille cb ON cb.qr_code_bouteille = b.qr_code WHERE cb.nom_boutique_actuel = ? AND cb.nom_entreprise = ? AND cb.date_commande = ?');
        $s->execute([$nomBoutique, $nomEntreprise, $dateCommande]);
        return $s->fetchAll();
    }
}
