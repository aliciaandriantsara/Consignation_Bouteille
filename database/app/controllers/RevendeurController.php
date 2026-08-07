<?php
// app/controllers/RevendeurController.php

class RevendeurController {

    private RevendeurModel $revendeurM;
    private BouteilleModel $bouteilleM;
    private TransactionModel $transactionM;
    private CommandeModel $commandeM;
    private ClientModel $clientM;

    public function __construct() {
        requireRole('revendeur');
        foreach (['RevendeurModel','BouteilleModel','TransactionModel',
                  'CommandeModel','ClientModel'] as $m) {
            require_once APP.'/models/'.$m.'.php';
        }
        $this->revendeurM = new RevendeurModel();
        $this->bouteilleM = new BouteilleModel();
        $this->transactionM = new TransactionModel();
        $this->commandeM = new CommandeModel();
        $this->clientM = new ClientModel();
    }

    private function getRevendeur(): array {
        $r = $this->revendeurM->findByEmail(currentUser()['email']);
        if (!$r) die('Revendeur introuvable.');
        return $r;
    }

    public function dashboard(?string $p = null): void {
        $r = $this->getRevendeur();
        $trans = $this->transactionM->byRevendeur($r['nom_boutique']);
        $commandes = $this->commandeM->byRevendeur($r['nom_boutique']);
        $enCours = array_filter($trans, fn($t) => $t['statut'] === 'EN_COURS');
        //array_filter est une fonction qui permet de filtrer un tableau
        //fn($t) est une fonction anonyme qui vaut ceci 
        //function($t) {
        //     return $t['statut'] === 'EN_COURS';
        // }
        $terminees = array_filter($trans, fn($t) => $t['statut'] !== 'EN_COURS');
        view('revendeur/dashboard', compact('r','enCours','terminees','commandes'));
    }

    /* API : scanner une bouteille */
    public function scanner(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            jsonResponse(['error'=>'POST requis'], 405); return;
            //arrete l'executino de la fonction et renvoie un code d'erreur si la methode n'est pas POST
        }
        $r = $this->getRevendeur();
        $qr = trim($_POST['qr_code'] ?? '');
        $action = $_POST['action'] ?? '';   // '' | 'emprunter' | 'rendre'

        $bouteille = $this->bouteilleM->findByQr($qr);
        if (!$bouteille) {
            jsonResponse(['error'=>'Bouteille introuvable : '.$qr], 404); return;
        }

        switch ($action) {

            case 'emprunter':
                if ($bouteille['statut'] !== 'DISPONIBLE_REVENDEUR') {
                    jsonResponse(['error'=>
                        'Statut actuel : '.$bouteille['statut'].'. Emprunt impossible.'], 409);
                    return;
                }
                $cinClient = trim($_POST['CIN_client'] ?? '');
                if (!$cinClient) {
                    jsonResponse(['error'=>'Veuillez sélectionner un client.'], 422); return;
                }
                $this->bouteilleM->updateStatut(
                    $qr, 'EMPRUNTEE', $r['nom_boutique']);
                $this->transactionM->emprunter($qr, $cinClient, $r['nom_boutique']);
                jsonResponse([
                    'success' => true,
                    'message' => 'Emprunt enregistré.',
                    'nouveau_statut' => 'EMPRUNTEE',
                ]);
                break;

            case 'rendre':
                if ($bouteille['statut'] !== 'EMPRUNTEE') {
                    jsonResponse(['error'=>
                        'Statut actuel : '.$bouteille['statut'].'. Retour impossible.'], 409);
                    return;
                }
                $this->bouteilleM->updateStatut($qr, 'RENDUE_REVENDEUR');
                $this->transactionM->terminer($qr);
                jsonResponse([
                    'success' => true,
                    'message' => 'Retour enregistré.',
                    'nouveau_statut' => 'RENDUE_REVENDEUR',
                ]);
                break;

            default:
                $trans = $this->transactionM->transactionEnCours($qr);
                jsonResponse(['bouteille' => $bouteille, 'transaction' => $trans]);
        }
    }

    /* API : recherche client (retourne CIN comme identifiant) */
    public function rechercheClient(?string $p = null): void {
        $q = trim($_GET['q'] ?? '');
        if (strlen($q) < 2) { jsonResponse([]); return; }
        jsonResponse($this->clientM->search($q));
    }

    /* Passer une commande */
    public function commander(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('revendeur/dashboard');
        $r        = $this->getRevendeur();
        $quantite = max(1, (int)($_POST['quantite'] ?? 1));
        //quantite contient la plus grande valeur car max retourne la plus grande valeur entre 1 et la valeur de quantite de POST
        $this->commandeM->creer(
            $r['nom_boutique'], $r['nom_entreprise'], $quantite);
        flashSet('success','Commande envoyée à l\'entreprise.');
        redirect('revendeur/dashboard');
    }
}
