<?php
// app/controllers/EntrepriseController.php

class EntrepriseController {

    private EntrepriseModel  $entrepriseM;
    private BouteilleModel   $bouteilleM;
    private TransactionModel $transactionM;
    private CommandeModel    $commandeM;
    private CollecteModel    $collecteM;
    private LivraisonModel   $livraisonM;

    public function __construct() {
        requireRole('entreprise');
        foreach (['EntrepriseModel','BouteilleModel','TransactionModel',
                  'CommandeModel','CollecteModel','LivraisonModel'] as $m) {
            require_once APP.'/models/'.$m.'.php';
        }
        $this->entrepriseM  = new EntrepriseModel();
        $this->bouteilleM   = new BouteilleModel();
        $this->transactionM = new TransactionModel();
        $this->commandeM    = new CommandeModel();
        $this->collecteM    = new CollecteModel();
        $this->livraisonM   = new LivraisonModel();
    }

    private function getEntreprise(): array {
        $e = $this->entrepriseM->findByEmail(currentUser()['email']);
        if (!$e) die('Entreprise introuvable.');
        return $e;
    }

    public function dashboard(?string $p = null): void {
        $e            = $this->getEntreprise();
        $statsStatut  = $this->bouteilleM->statsParStatut($e['nom_entreprise']);
        $statsRev     = $this->bouteilleM->statsParRevendeur($e['nom_entreprise']);
        $transactions = $this->transactionM->byEntreprise($e['nom_entreprise']);
        $commandes    = $this->commandeM->byEntreprise($e['nom_entreprise']);
        $collectes    = $this->collecteM->byEntreprise($e['nom_entreprise']);
        view('entreprise/dashboard',
            compact('e','statsStatut','statsRev','transactions','commandes','collectes'));
    }

    /* Valider une commande → créer la livraison */
    public function validerCommande(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('entreprise/dashboard');
        $e            = $this->getEntreprise();
        $nomBoutique  = $_POST['nom_boutique']  ?? '';
        $dateCommande = $_POST['date_commande'] ?? '';
        $cinLivreur   = $_POST['CIN_livreur']   ?? '';
        $datePrevue   = $_POST['date_prevue']   ?? date('Y-m-d');

        $cmd = $this->commandeM->findOne(
            $nomBoutique, $e['nom_entreprise'], $dateCommande);
        if (!$cmd || $cmd['statut'] !== 'EN_ATTENTE') {
            flashSet('error','Commande introuvable ou déjà traitée.');
            redirect('entreprise/dashboard');
        }
        $this->commandeM->updateStatut(
            $nomBoutique, $e['nom_entreprise'], $dateCommande, 'EN_LIVRAISON');
        $this->livraisonM->creer(
            $cinLivreur, $nomBoutique, $e['nom_entreprise'], $dateCommande, $datePrevue);
        flashSet('success','Commande validée — livraison assignée.');
        redirect('entreprise/dashboard');
    }

    /* Créer une collecte */
    public function creerCollecte(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('entreprise/dashboard');
        $e           = $this->getEntreprise();
        $nomBoutique = $_POST['nom_boutique'] ?? '';
        $cinLivreur  = $_POST['CIN_livreur']  ?? '';
        $datePrevue  = $_POST['date_prevue']  ?? date('Y-m-d');
        $this->collecteM->creer(
            $nomBoutique, $e['nom_entreprise'], $cinLivreur, $datePrevue);
        flashSet('success','Collecte créée et assignée au livreur.');
        redirect('entreprise/dashboard');
    }

    /* API JSON : liste des livreurs */
    public function livreurs(?string $p = null): void {
        jsonResponse($this->entrepriseM->allLivreurs());
    }

    /* API JSON : liste des revendeurs */
    public function revendeurs(?string $p = null): void {
        $e = $this->getEntreprise();
        jsonResponse($this->entrepriseM->allRevendeurs($e['nom_entreprise']));
    }
}
