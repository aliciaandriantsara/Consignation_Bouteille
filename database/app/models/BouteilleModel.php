<?php
class BouteilleModel {
    const STATUTS = ['DISPONIBLE_REVENDEUR','EMPRUNTEE','RENDUE_REVENDEUR','EN_COLLECTE','EN_STOCK_ENTREPRISE','A_LAVER','PROPRE','DISPONIBLE_STOCK','LIVREE_REVENDEUR'];
    

    //transitions autorisee entre les differents etats d'une bouteille
    const TRANSITIONS = [
        'DISPONIBLE_REVENDEUR' => ['EMPRUNTEE'],
        'EMPRUNTEE' => ['RENDUE_REVENDEUR'],
        'RENDUE_REVENDEUR' => ['EN_COLLECTE'],
        'EN_COLLECTE' => ['EN_STOCK_ENTREPRISE'],
        'EN_STOCK_ENTREPRISE' => ['A_LAVER'],
        'A_LAVER' => ['PROPRE'],
        'PROPRE' => ['DISPONIBLE_STOCK'],
        'DISPONIBLE_STOCK' => ['LIVREE_REVENDEUR'],
        'LIVREE_REVENDEUR' => ['DISPONIBLE_REVENDEUR'],
    ];

    private PDO $db;
    public function __construct() { 
        $this->db = getDB(); 
    }

    //trouver une bouteille par son QR code et retourne un tableau associatif representant la bouteille ou null si la bouteille n'existe pas
    public function findByQr(string $qr): ?array {
        $s = $this->db->prepare('SELECT b.*, r.adresse AS revendeur_adresse FROM bouteille b LEFT JOIN revendeur r ON r.nom_boutique = b.nom_boutique_actuel WHERE b.qr_code = ?');
        $s->execute([$qr]);
        return $s->fetch() ?: null;
    }

    //mettre a jour le statut d'une bouteille et le nom du revendeur actuel si necessaire
    //?string peut etre soit de ce type string soit null dans la definition de la methode
    public function updateStatut(string $qr, string $statut, ?string $nomBoutiqueActuel = null): void {
        if ($nomBoutiqueActuel !== null) {
            $s = $this->db->prepare('UPDATE bouteille SET statut = ?, nom_boutique_actuel = ?, updated_at = NOW() WHERE qr_code = ?');
            $s->execute([$statut, $nomBoutiqueActuel, $qr]);
        } else {
            $s = $this->db->prepare('UPDATE bouteille SET statut = ?, updated_at = NOW() WHERE qr_code = ?');
            $s->execute([$statut, $qr]);
        }
    }

    //liste de toutes les bouteilles d'une entreprise triées par date de mise à jour (updated_at) décroissante
    public function allByEntreprise(string $nomEntreprise): array {
        $s = $this->db->prepare('SELECT * FROM bouteille WHERE nom_entreprise = ? ORDER BY updated_at DESC');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }

    //compter les nombres de statuts de bouteilles pour une entreprise donnnees genre quelle entreprise contient combien de bouteilles dans chaque statut
    public function statsParStatut(string $nomEntreprise): array {
        $s = $this->db->prepare('SELECT statut, COUNT(*) AS total FROM bouteille WHERE nom_entreprise = ? GROUP BY statut');
        $s->execute([$nomEntreprise]);
        $stats = array_fill_keys(self::STATUTS, 0);//cree un tableau ou les elements du tableau deviennent les cles du tableau et la valeur de chaque element est 0
        //self::statuts fait reference a la constante STATUTS de la classe BouteilleModel
        foreach ($s->fetchAll() as $r) {
            //recupere toutes les lignes renvoyees par la requete et les stocke dans un tableau associatif
            //$r parcout chaque ligne du tableau
            
            $stats[$r['statut']] = (int)$r['total'];
            //convertit la valeur de total en entier 

        }
        return $stats;
    }

    //compter les nombres de bouteilles par revendeur pour une entreprise donnée
    public function statsParRevendeur(string $nomEntreprise): array {
        $s = $this->db->prepare('SELECT nom_boutique_actuel, statut, COUNT(*) AS total FROM bouteille WHERE nom_entreprise = ? AND nom_boutique_actuel IS NOT NULL GROUP BY nom_boutique_actuel, statut ORDER BY nom_boutique_actuel');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }

    //liste de toutes les bouteilles rendues chez un revendeur triées par date de mise à jour (updated_at) croissante
    public function renduesChezRevendeur(string $nomBoutique): array {
        $s = $this->db->prepare("SELECT * FROM bouteille WHERE nom_boutique_actuel = ? AND statut = 'RENDUE_REVENDEUR' ORDER BY updated_at");
        $s->execute([$nomBoutique]);
        return $s->fetchAll();
    }

    //liste de toutes les bouteilles à traiter en stock pour une entreprise triées par date de mise à jour (updated_at) croissante
    public function aTraiterEnStock(string $nomEntreprise): array {
        $s = $this->db->prepare("SELECT * FROM bouteille WHERE nom_entreprise = ? AND statut IN ('EN_STOCK_ENTREPRISE','A_LAVER','PROPRE') ORDER BY updated_at");
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }

    //liste de toutes les bouteilles à traiter en collecte pour une entreprise triées par date de mise à jour (updated_at) croissante
    public function allByStatut(string $nomEntreprise, string $statut): array {
        $s = $this->db->prepare("SELECT * FROM bouteille WHERE nom_entreprise = ? AND statut = ? ORDER BY qr_code");
        $s->execute([$nomEntreprise, $statut]);
        return $s->fetchAll();
    }

    //verifie si la transition de statut est autorisee
    public function transitionAutorisee(string $actuel, string $nouveau): bool {
        return in_array($nouveau, self::TRANSITIONS[$actuel] ?? []);
    }

    //  Gestion QR codes 

    // Générer un QR code unique automatiquement
    public function genererQrCode(string $nomEntreprise): string {
        // Format : NOM_ENTREPRISE-TIMESTAMP-RANDOM
        // ex: AQUA-1717000000-A3F2
        $prefix  = strtoupper(substr(preg_replace('/[^A-Z0-9]/i', '', $nomEntreprise), 0, 4));
        //preg_replace('/[^A-Z0-9]/i', '', $nomEntreprise) supprime tous les caracteres qui ne sont pas des lettres ou des chiffres de la chaine nomEntreprise
        //le i signifie insensible a la casse, donc A et a sont consideres comme identiques
        //chaine vide donc les caracteres speciaux sont supprimes 
        //substr(..., 0, 4) prend les 4 premiers caracteres de la chaine resultant de preg_replace
        //strtoupper(...) convertit la chaine resultant de substr en majuscules
        $ts      = time();//sert a recuperer le timestamp actuel 
        $random  = strtoupper(substr(md5(uniqid('', true)), 0, 4));
        //uniqid('', true) genere un identifiant unique base sur le temps actuel en microsecondes et un prefixe vide
        //md5(...) calcule le hash md5 de l'identifiant unique genere par uniqid
        //substr(..., 0, 4) prend les 4 premiers caracteres du hash md5 resultant
        //strtoupper(...) convertit la chaine resultant de substr en majuscules
        $qr      = $prefix . '-' . $ts . '-' . $random;
        //ex: AQUA-1717000000-A3F2 
        //le QR code est compose du prefixe, du timestamp et du random separes par des tirets

        // Vérifier unicité (très peu probable mais sécurisé)
        //creer un nouveau code qr pour remplacer l'ancien si le code qr genere existe deja dans la base de donnees
        ///MISY ZAVATRA TSY MITOMBINA ETO AMIN"ILAY MIGENERER CODE QR
        while ($this->findByQr($qr)) {
            $random = strtoupper(substr(md5(uniqid('', true)), 0, 4));
            $qr     = $prefix . '-' . $ts . '-' . $random;
        }
        return $qr;
    }

    // Créer une nouvelle bouteille avec QR code généré
    public function creer(string $nomEntreprise, string $qrCode): void {
        $s = $this->db->prepare("INSERT INTO bouteille (qr_code, nom_entreprise, statut) VALUES (?, ?, 'DISPONIBLE_STOCK')");
        $s->execute([$qrCode, $nomEntreprise]);
    }

    // Créer plusieurs bouteilles d'un coup
    public function creerEnLot(string $nomEntreprise, int $quantite): array {
        $qrCodes = [];
        for ($i = 0; $i < $quantite; $i++) {
            $qr = $this->genererQrCode($nomEntreprise);
            $this->creer($nomEntreprise, $qr);
            $qrCodes[] = $qr;
        }
        return $qrCodes;
    }

    // Remplacer un QR code abîmé
    public function remplacerQr(string $ancienQr, string $nomEntreprise): string {
        $nouveauQr = $this->genererQrCode($nomEntreprise);
        $s = $this->db->prepare('UPDATE bouteille SET qr_code = ? WHERE qr_code = ?');
        $s->execute([$nouveauQr, $ancienQr]);
        return $nouveauQr;
    }

    // Toutes les bouteilles d'une entreprise pour le logisticien
    public function toutesParEntreprise(string $nomEntreprise): array {
        $s = $this->db->prepare('SELECT b.*, r.nom_boutique AS revendeur_nom FROM bouteille b LEFT JOIN revendeur r ON r.nom_boutique = b.nom_boutique_actuel WHERE b.nom_entreprise = ? ORDER BY b.updated_at DESC');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }
}
