<?php
class CollecteModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function creer(string $nomBoutique, string $nomEntreprise, string $cinLivreur, string $datePrevue): void {
        $s = $this->db->prepare(
            'INSERT INTO collecte
             (nom_boutique_actuel, nom_entreprise, CIN_livreur, date_collecte_prevue)
             VALUES (?, ?, ?, ?)');
        $s->execute([$nomBoutique, $nomEntreprise, $cinLivreur, $datePrevue]);
    }

    public function ajouterBouteille(string $qrBouteille, string $nomBoutique, string $nomEntreprise, string $cinLivreur, string $datePrevue): void {
        $s = $this->db->prepare(
            'INSERT IGNORE INTO collecte_bouteille
             (qr_code_bouteille, nom_boutique_actuel, nom_entreprise, CIN_livreur, date_collecte_prevue)
             VALUES (?, ?, ?, ?, ?)');
        $s->execute([$qrBouteille, $nomBoutique, $nomEntreprise, $cinLivreur, $datePrevue]);
    }

    public function byLivreur(string $cinLivreur): array {
        $s = $this->db->prepare(
            'SELECT c.*, r.adresse, COUNT(cb.qr_code_bouteille) AS nb_bouteilles
             FROM collecte c
             LEFT JOIN revendeur r ON r.nom_boutique = c.nom_boutique_actuel
             LEFT JOIN collecte_bouteille cb
                    ON cb.nom_boutique_actuel  = c.nom_boutique_actuel
                   AND cb.nom_entreprise       = c.nom_entreprise
                   AND cb.CIN_livreur          = c.CIN_livreur
                   AND cb.date_collecte_prevue = c.date_collecte_prevue
             WHERE c.CIN_livreur = ?
             GROUP BY c.nom_boutique_actuel, c.nom_entreprise,
                      c.CIN_livreur, c.date_collecte_prevue
             ORDER BY c.date_collecte_prevue ASC');
        $s->execute([$cinLivreur]);
        return $s->fetchAll();
    }

    public function byEntreprise(string $nomEntreprise): array {
        $s = $this->db->prepare(
            'SELECT c.*,
                    lv.nom AS livreur_nom, lv.prenom AS livreur_prenom,
                    COUNT(cb.qr_code_bouteille) AS nb_bouteilles
             FROM collecte c
             JOIN livreur lv ON lv.CIN = c.CIN_livreur
             LEFT JOIN collecte_bouteille cb
                    ON cb.nom_boutique_actuel  = c.nom_boutique_actuel
                   AND cb.nom_entreprise       = c.nom_entreprise
                   AND cb.CIN_livreur          = c.CIN_livreur
                   AND cb.date_collecte_prevue = c.date_collecte_prevue
             WHERE c.nom_entreprise = ?
             GROUP BY c.nom_boutique_actuel, c.nom_entreprise,
                      c.CIN_livreur, c.date_collecte_prevue
             ORDER BY c.date_collecte_prevue DESC');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }

    public function marquerEffectuee(string $nomBoutique, string $nomEntreprise, string $cinLivreur, string $datePrevue): bool {
        $s = $this->db->prepare(
            "UPDATE collecte
             SET statut = 'EFFECTUEE', date_collecte_effective = NOW()
             WHERE nom_boutique_actuel  = ?
               AND nom_entreprise       = ?
               AND CIN_livreur          = ?
               AND date_collecte_prevue = ?
               AND statut              != 'EFFECTUEE'");
        $s->execute([$nomBoutique, $nomEntreprise, $cinLivreur, $datePrevue]);
        return $s->rowCount() > 0;
    }

    public function bouteillesDeCollecte(string $nomBoutique, string $nomEntreprise, string $cinLivreur, string $datePrevue): array {
        $s = $this->db->prepare(
            'SELECT b.* FROM bouteille b
             JOIN collecte_bouteille cb ON cb.qr_code_bouteille = b.qr_code
             WHERE cb.nom_boutique_actuel  = ?
               AND cb.nom_entreprise       = ?
               AND cb.CIN_livreur          = ?
               AND cb.date_collecte_prevue = ?');
        $s->execute([$nomBoutique, $nomEntreprise, $cinLivreur, $datePrevue]);
        return $s->fetchAll();
    }
}
