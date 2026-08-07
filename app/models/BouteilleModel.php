<?php
class BouteilleModel {
    const STATUTS = [
        'DISPONIBLE_REVENDEUR','EMPRUNTEE','RENDUE_REVENDEUR',
        'EN_COLLECTE','EN_STOCK_ENTREPRISE','A_LAVER',
        'PROPRE','DISPONIBLE_STOCK','LIVREE_REVENDEUR'
    ];
    const TRANSITIONS = [
        'DISPONIBLE_REVENDEUR' => ['EMPRUNTEE'],
        'EMPRUNTEE'            => ['RENDUE_REVENDEUR'],
        'RENDUE_REVENDEUR'     => ['EN_COLLECTE'],
        'EN_COLLECTE'          => ['EN_STOCK_ENTREPRISE'],
        'EN_STOCK_ENTREPRISE'  => ['A_LAVER'],
        'A_LAVER'              => ['PROPRE'],
        'PROPRE'               => ['DISPONIBLE_STOCK'],
        'DISPONIBLE_STOCK'     => ['LIVREE_REVENDEUR'],
        'LIVREE_REVENDEUR'     => ['DISPONIBLE_REVENDEUR'],
    ];

    private PDO $db;
    public function __construct() { $this->db = getDB(); }

    public function findByQr(string $qr): ?array {
        $s = $this->db->prepare(
            'SELECT b.*, r.adresse AS revendeur_adresse
             FROM bouteille b
             LEFT JOIN revendeur r ON r.nom_boutique = b.nom_boutique_actuel
             WHERE b.qr_code = ?');
        $s->execute([$qr]);
        return $s->fetch() ?: null;
    }

    public function updateStatut(string $qr, string $statut, ?string $nomBoutiqueActuel = null): void {
        if ($nomBoutiqueActuel !== null) {
            $s = $this->db->prepare(
                'UPDATE bouteille SET statut = ?, nom_boutique_actuel = ?, updated_at = NOW()
                 WHERE qr_code = ?');
            $s->execute([$statut, $nomBoutiqueActuel, $qr]);
        } else {
            $s = $this->db->prepare(
                'UPDATE bouteille SET statut = ?, updated_at = NOW() WHERE qr_code = ?');
            $s->execute([$statut, $qr]);
        }
    }

    public function allByEntreprise(string $nomEntreprise): array {
        $s = $this->db->prepare(
            'SELECT * FROM bouteille WHERE nom_entreprise = ? ORDER BY updated_at DESC');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }

    public function statsParStatut(string $nomEntreprise): array {
        $s = $this->db->prepare(
            'SELECT statut, COUNT(*) AS total FROM bouteille
             WHERE nom_entreprise = ? GROUP BY statut');
        $s->execute([$nomEntreprise]);
        $stats = array_fill_keys(self::STATUTS, 0);
        foreach ($s->fetchAll() as $r) {
            $stats[$r['statut']] = (int)$r['total'];
        }
        return $stats;
    }

    public function statsParRevendeur(string $nomEntreprise): array {
        $s = $this->db->prepare(
            'SELECT nom_boutique_actuel, statut, COUNT(*) AS total
             FROM bouteille
             WHERE nom_entreprise = ? AND nom_boutique_actuel IS NOT NULL
             GROUP BY nom_boutique_actuel, statut
             ORDER BY nom_boutique_actuel');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }

    public function renduesChezRevendeur(string $nomBoutique): array {
        $s = $this->db->prepare(
            "SELECT * FROM bouteille
             WHERE nom_boutique_actuel = ? AND statut = 'RENDUE_REVENDEUR'
             ORDER BY updated_at");
        $s->execute([$nomBoutique]);
        return $s->fetchAll();
    }

    public function aTraiterEnStock(string $nomEntreprise): array {
        $s = $this->db->prepare(
            "SELECT * FROM bouteille
             WHERE nom_entreprise = ?
               AND statut IN ('EN_STOCK_ENTREPRISE','A_LAVER','PROPRE')
             ORDER BY updated_at");
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }

    public function transitionAutorisee(string $actuel, string $nouveau): bool {
        return in_array($nouveau, self::TRANSITIONS[$actuel] ?? []);
    }
}
