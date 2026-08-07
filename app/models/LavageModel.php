<?php
class LavageModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function demarrer(string $qrBouteille, string $cinLogisticien): void {
        // Fermer tout lavage en cours sur cette bouteille d'abord
        $this->db->prepare(
            "UPDATE lavage SET statut = 'TERMINE', date_fin = NOW()
             WHERE qr_code_bouteille = ? AND statut = 'EN_COURS'")
            ->execute([$qrBouteille]);

        $s = $this->db->prepare(
            "INSERT INTO lavage (qr_code_bouteille, CIN_logisticien, date_debut, statut)
             VALUES (?, ?, NOW(), 'EN_COURS')");
        $s->execute([$qrBouteille, $cinLogisticien]);
    }

    public function terminer(string $qrBouteille): bool {
        $s = $this->db->prepare(
            "UPDATE lavage SET statut = 'TERMINE', date_fin = NOW()
             WHERE qr_code_bouteille = ? AND statut = 'EN_COURS'");
        $s->execute([$qrBouteille]);
        return $s->rowCount() > 0;
    }

    public function enCoursParLogisticien(string $cinLogisticien): array {
        $s = $this->db->prepare(
            "SELECT lv.*, b.statut AS bouteille_statut, b.nom_entreprise
             FROM lavage lv
             JOIN bouteille b ON b.qr_code = lv.qr_code_bouteille
             WHERE lv.CIN_logisticien = ? AND lv.statut = 'EN_COURS'");
        $s->execute([$cinLogisticien]);
        return $s->fetchAll();
    }

    public function historiqueByEntreprise(string $nomEntreprise): array {
        $s = $this->db->prepare(
            'SELECT lv.*, lg.nom AS logisticien_nom, lg.prenom AS logisticien_prenom
             FROM lavage lv
             JOIN bouteille   b  ON b.qr_code = lv.qr_code_bouteille
             JOIN logisticien lg ON lg.CIN     = lv.CIN_logisticien
             WHERE b.nom_entreprise = ?
             ORDER BY lv.date_debut DESC
             LIMIT 100');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }
}
