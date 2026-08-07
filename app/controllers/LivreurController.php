<?php
// app/controllers/LivreurController.php

class LivreurController {

    private LivreurModel   $livreurM;
    private LivraisonModel $livraisonM;
    private CollecteModel  $collecteM;
    private BouteilleModel $bouteilleM;
    private CommandeModel  $commandeM;

    public function __construct() {
        requireRole('livreur');
        foreach (['LivreurModel','LivraisonModel','CollecteModel',
                  'BouteilleModel','CommandeModel'] as $m) {
            require_once APP.'/models/'.$m.'.php';
        }
        $this->livreurM   = new LivreurModel();
        $this->livraisonM = new LivraisonModel();
        $this->collecteM  = new CollecteModel();
        $this->bouteilleM = new BouteilleModel();
        $this->commandeM  = new CommandeModel();
    }

    private function getLivreur(): array {
        $l = $this->livreurM->findByEmail(currentUser()['email']);
        if (!$l) die('Livreur introuvable.');
        return $l;
    }

    public function dashboard(?string $p = null): void {
        $livreur   = $this->getLivreur();
        $livraisons = $this->livraisonM->byLivreur($livreur['CIN']);
        $collectes  = $this->collecteM->byLivreur($livreur['CIN']);

        $livraisonsAFaire = array_filter($livraisons, fn($l) => $l['statut'] !== 'EFFECTUEE');
        $livraisonsFaites = array_filter($livraisons, fn($l) => $l['statut'] === 'EFFECTUEE');
        $collectesAFaire  = array_filter($collectes,  fn($c) => $c['statut'] !== 'EFFECTUEE');
        $collectesFaites  = array_filter($collectes,  fn($c) => $c['statut'] === 'EFFECTUEE');

        view('livreur/dashboard',
            compact('livreur','livraisonsAFaire','livraisonsFaites',
                    'collectesAFaire','collectesFaites'));
    }

    /* Marquer une livraison effectuée
       Paramètres POST : nom_boutique, nom_entreprise, date_commande */
    public function cocherLivraison(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('livreur/dashboard');
        $livreur      = $this->getLivreur();
        $nomBoutique  = $_POST['nom_boutique']  ?? '';
        $nomEntreprise= $_POST['nom_entreprise']?? '';
        $dateCommande = $_POST['date_commande'] ?? '';

        if ($this->livraisonM->marquerEffectuee(
                $livreur['CIN'], $nomBoutique, $nomEntreprise, $dateCommande)) {
            // Mettre la commande à LIVREE
            $this->commandeM->updateStatut(
                $nomBoutique, $nomEntreprise, $dateCommande, 'LIVREE');
            flashSet('success','Livraison marquée effectuée.');
        } else {
            flashSet('error','Impossible de mettre à jour cette livraison.');
        }
        redirect('livreur/dashboard');
    }

    /* Marquer une collecte effectuée
       Paramètres POST : nom_boutique, nom_entreprise, date_collecte_prevue */
    public function cocherCollecte(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('livreur/dashboard');
        $livreur      = $this->getLivreur();
        $nomBoutique  = $_POST['nom_boutique']       ?? '';
        $nomEntreprise= $_POST['nom_entreprise']     ?? '';
        $datePrevue   = $_POST['date_collecte_prevue']?? '';

        if ($this->collecteM->marquerEffectuee(
                $nomBoutique, $nomEntreprise, $livreur['CIN'], $datePrevue)) {
            // Mettre les bouteilles collectées en EN_STOCK_ENTREPRISE
            $bouts = $this->collecteM->bouteillesDeCollecte(
                $nomBoutique, $nomEntreprise, $livreur['CIN'], $datePrevue);
            foreach ($bouts as $b) {
                if ($b['statut'] === 'EN_COLLECTE') {
                    $this->bouteilleM->updateStatut(
                        $b['qr_code'], 'EN_STOCK_ENTREPRISE', null);
                }
            }
            flashSet('success','Collecte effectuée — bouteilles en stock entreprise.');
        } else {
            flashSet('error','Impossible de mettre à jour cette collecte.');
        }
        redirect('livreur/dashboard');
    }
}
