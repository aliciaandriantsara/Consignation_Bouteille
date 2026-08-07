<?php
class StockModel {
    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function byEntreprise(string $nomEntreprise): ?array {
        $s = $this->db->prepare('SELECT * FROM stock WHERE nom_entreprise = ?');
        $s->execute([$nomEntreprise]);
        return $s->fetch() ?: null;
    }

    public function compterDepuisStatuts(string $nomEntreprise): array {
        $s = $this->db->prepare(
            "SELECT
               SUM(statut IN ('PROPRE','DISPONIBLE_STOCK'))        AS propres,
               SUM(statut IN ('EN_STOCK_ENTREPRISE','A_LAVER'))    AS a_laver
             FROM bouteille WHERE nom_entreprise = ?");
        $s->execute([$nomEntreprise]);
        $r = $s->fetch();
        return [
            'propres' => (int)($r['propres'] ?? 0),
            'a_laver' => (int)($r['a_laver'] ?? 0),
        ];
    }

    public function mettreAJour(string $nomEntreprise, int $propres, int $aLaver): void {
        $s = $this->db->prepare(
            'INSERT INTO stock (nom_entreprise, nombre_bouteilles_propres, nombre_bouteilles_a_laver)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE
               nombre_bouteilles_propres = ?,
               nombre_bouteilles_a_laver = ?');
        $s->execute([$nomEntreprise, $propres, $aLaver, $propres, $aLaver]);
    }
}
