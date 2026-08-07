<?php
class TransactionModel {
    private PDO $db;

    public function __construct() {
        $this->db = getDB();
    }

    // CRUD operations for transaction_consignation table
    // Create a new transaction
    public function emprunter(string $qrBouteille, string $cinClient, string $nomBoutique): void {
        $s = $this->db->prepare("INSERT INTO transaction_consignation (qr_code_bouteille, CIN_client, nom_boutique, statut) VALUES (?, ?, ?, 'EN_COURS')");
        $s->execute([$qrBouteille, $cinClient, $nomBoutique]);
    }

    // Update the transaction to mark it as completed
    public function terminer(string $qrBouteille): bool {
        $s = $this->db->prepare("UPDATE transaction_consignation SET date_retour = NOW(), statut = 'TERMINEE' WHERE qr_code_bouteille = ? AND statut = 'EN_COURS'");
        $s->execute([$qrBouteille]);
        return $s->rowCount() > 0;
    }

    // Get a transaction that is currently in progress
    public function transactionEnCours(string $qrBouteille): ?array {
        $s = $this->db->prepare("SELECT t.*, c.nom, c.prenom, c.telephone FROM transaction_consignation t JOIN client c ON c.CIN = t.CIN_client WHERE t.qr_code_bouteille = ? AND t.statut = 'EN_COURS' LIMIT 1");
        $s->execute([$qrBouteille]);
        return $s->fetch() ?: null;
    }

    //selectionne toutes les transactions d'un revendeur
    public function byRevendeur(string $nomBoutique): array {
        $s = $this->db->prepare('SELECT t.*, c.nom, c.prenom, c.CIN, b.nom_entreprise FROM transaction_consignation t JOIN client c ON c.CIN = t.CIN_client JOIN bouteille b ON b.qr_code  = t.qr_code_bouteille WHERE t.nom_boutique = ? ORDER BY t.date_emprunt DESC');
        $s->execute([$nomBoutique]);
        return $s->fetchAll();
    }

    //selectionne toutes les transactions d'un client
    // public function byClient(string $cinClient): array {
    //     $s = $this->db->prepare('SELECT t.*, b.nom_entreprise FROM transaction_consignation t JOIN bouteille b ON b.qr_code = t.qr_code_bouteille WHERE t.CIN_client = ? ORDER BY t.date_emprunt DESC');
    //     $s->execute([$cinClient]);
    //     return $s->fetchAll();
    // }
    //selectionne toutes les transactions d'un client
    public function byClient(string $cinClient): array {
        $s = $this->db->prepare('SELECT t.*, t.qr_code_bouteille AS bouteille_qr, b.nom_entreprise FROM transaction_consignation t JOIN bouteille b ON b.qr_code = t.qr_code_bouteille WHERE t.CIN_client = ? ORDER BY t.date_emprunt DESC');
        $s->execute([$cinClient]);
        return $s->fetchAll();
    }

    ///selectionne toutes les transactions d'une entreprise
    public function byEntreprise(string $nomEntreprise): array {
        $s = $this->db->prepare('SELECT t.*, c.nom, c.prenom FROM transaction_consignation t JOIN bouteille b ON b.qr_code = t.qr_code_bouteille JOIN client c ON c.CIN = t.CIN_client WHERE b.nom_entreprise = ? ORDER BY t.date_emprunt DESC');
        $s->execute([$nomEntreprise]);
        return $s->fetchAll();
    }
}
