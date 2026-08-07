<?php
class LivreurController {

    private LivreurModel $livreurM;
    private LivraisonModel $livraisonM;
    private CollecteModel $collecteM;
    private BouteilleModel $bouteilleM;
    private CommandeModel $commandeM;

    public function __construct() {
        requireRole('livreur');
        foreach (['LivreurModel','LivraisonModel','CollecteModel',
                  'BouteilleModel','CommandeModel'] as $m) {
            require_once APP.'/models/'.$m.'.php';
        }
        $this->livreurM = new LivreurModel();
        $this->livraisonM = new LivraisonModel();
        $this->collecteM = new CollecteModel();
        $this->bouteilleM = new BouteilleModel();
        $this->commandeM  = new CommandeModel();
    }

    private function getLivreur(): array {
        $l = $this->livreurM->findByEmail(currentUser()['email']);
        if (!$l) die('Livreur introuvable.');
        return $l;
    }

    public function dashboard(?string $p = null): void {
        $livreur = $this->getLivreur();
        $livraisons = $this->livraisonM->byLivreur($livreur['CIN']);
        $collectes = $this->collecteM->byLivreur($livreur['CIN']);

        $livraisonsAFaire = array_filter($livraisons, fn($l) => $l['statut'] !== 'EFFECTUEE');
        $livraisonsFaites = array_filter($livraisons, fn($l) => $l['statut'] === 'EFFECTUEE');
        $collectesAFaire = array_filter($collectes, fn($c) => $c['statut'] !== 'EFFECTUEE');
        $collectesFaites = array_filter($collectes, fn($c) => $c['statut'] === 'EFFECTUEE');

        view('livreur/dashboard',
            compact('livreur','livraisonsAFaire','livraisonsFaites',
                    'collectesAFaire','collectesFaites'));
    }

    /* Livraison effectuée : bouteilles LIVREE_REVENDEUR → DISPONIBLE_REVENDEUR */
    public function cocherLivraison(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('livreur/dashboard');
        $livreur = $this->getLivreur();
        $nomBoutique = $_POST['nom_boutique']   ?? '';
        $nomEntreprise = $_POST['nom_entreprise'] ?? '';
        $dateCommande = $_POST['date_commande']  ?? '';

        if ($this->livraisonM->marquerEffectuee(
                $livreur['CIN'], $nomBoutique, $nomEntreprise, $dateCommande)) {

            // Mettre la commande à LIVREE
            $this->commandeM->updateStatut(
                $nomBoutique, $nomEntreprise, $dateCommande, 'LIVREE');

            // Bouteilles LIVREE_REVENDEUR → DISPONIBLE_REVENDEUR chez ce revendeur
            $bouteilles = $this->commandeM->bouteillesDeCommande(
                $nomBoutique, $nomEntreprise, $dateCommande);
            foreach ($bouteilles as $b) {
                if ($b['statut'] === 'LIVREE_REVENDEUR') {
                    $this->bouteilleM->updateStatut(
                        $b['qr_code'], 'DISPONIBLE_REVENDEUR', $nomBoutique);
                }
            }

            flashSet('success', 'Livraison effectuée — bouteilles disponibles chez le revendeur.');
        } else {
            flashSet('error','Impossible de mettre à jour cette livraison.');
        }
        redirect('livreur/dashboard');
    }

    /* Collecte effectuée : bouteilles EN_COLLECTE → EN_STOCK_ENTREPRISE */
    public function cocherCollecte(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('livreur/dashboard');
        $livreur       = $this->getLivreur();
        $nomBoutique   = $_POST['nom_boutique']         ?? '';
        $nomEntreprise = $_POST['nom_entreprise']       ?? '';
        $datePrevue    = $_POST['date_collecte_prevue'] ?? '';

        if ($this->collecteM->marquerEffectuee(
                $nomBoutique, $nomEntreprise, $livreur['CIN'], $datePrevue)) {

            // Bouteilles EN_COLLECTE → EN_STOCK_ENTREPRISE
            $bouteilles = $this->collecteM->bouteillesDeCollecte(
                $nomBoutique, $nomEntreprise, $livreur['CIN'], $datePrevue);
            foreach ($bouteilles as $b) {
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
