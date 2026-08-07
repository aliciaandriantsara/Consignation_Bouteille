<?php
class LogisticienController {

    private LogisticienModel $logisticienM;
    private StockModel $stockM;
    private BouteilleModel $bouteilleM;
    private LavageModel $lavageM;

    public function __construct() {
        requireRole('logisticien');
        foreach (['LogisticienModel','StockModel','BouteilleModel','LavageModel'] as $m) {
            require_once APP.'/models/'.$m.'.php';
        }
        require_once APP.'/helpers/qrcode.php';
        $this->logisticienM = new LogisticienModel();
        $this->stockM = new StockModel();
        $this->bouteilleM = new BouteilleModel();
        $this->lavageM = new LavageModel();
    }

    // Récupérer les informations du logisticien actuellement connecté
    private function getLog(): array {
        $l = $this->logisticienM->findByEmail(currentUser()['email']);
        if (!$l) die('Logisticien introuvable.');
        return $l;
    }

    public function dashboard(?string $p = null): void {
        $log = $this->getLog();
        $stock = $this->stockM->byEntreprise($log['nom_entreprise']);
        $calcul = $this->stockM->compterDepuisStatuts($log['nom_entreprise']);
        $bouts = $this->bouteilleM->aTraiterEnStock($log['nom_entreprise']);
        $enLavage = $this->lavageM->enCoursParLogisticien($log['CIN']);
        $toutes = $this->bouteilleM->toutesParEntreprise($log['nom_entreprise']);
        view('logisticien/dashboard', compact('log','stock','calcul','bouts','enLavage','toutes'));
    }

    /* EN_STOCK_ENTREPRISE → A_LAVER */
    public function demarrerLavage(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('logisticien/dashboard');
        $log = $this->getLog();
        $qr = trim($_POST['qr_code'] ?? '');
        $b = $this->bouteilleM->findByQr($qr);
        if (!$b || $b['nom_entreprise'] !== $log['nom_entreprise']) {
            flashSet('error','Bouteille introuvable.'); redirect('logisticien/dashboard');
        }
        if (!in_array($b['statut'], ['EN_STOCK_ENTREPRISE','A_LAVER'])) {
            flashSet('error','Statut incompatible : '.$b['statut']);
            redirect('logisticien/dashboard');
        }
        $this->bouteilleM->updateStatut($qr, 'A_LAVER');
        $this->lavageM->demarrer($qr, $log['CIN']);
        flashSet('success', $qr.' — lavage démarré.');
        redirect('logisticien/dashboard');
    }

    /* A_LAVER → PROPRE */
    public function terminerLavage(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('logisticien/dashboard');
        $log = $this->getLog();
        $qr = trim($_POST['qr_code'] ?? '');
        $b = $this->bouteilleM->findByQr($qr);
        if (!$b || $b['nom_entreprise'] !== $log['nom_entreprise']) {
            flashSet('error','Bouteille introuvable.'); redirect('logisticien/dashboard');
        }
        $this->bouteilleM->updateStatut($qr, 'PROPRE');
        $this->lavageM->terminer($qr);
        $this->syncStock($log['nom_entreprise']);
        flashSet('success', $qr.' — marquée propre.');
        redirect('logisticien/dashboard');
    }

    /* PROPRE → DISPONIBLE_STOCK */
    public function mettreEnStock(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('logisticien/dashboard');
        $log = $this->getLog();
        $qr = trim($_POST['qr_code'] ?? '');
        $b = $this->bouteilleM->findByQr($qr);
        if (!$b || $b['nom_entreprise'] !== $log['nom_entreprise'] || $b['statut'] !== 'PROPRE') {
            flashSet('error','Action impossible.'); redirect('logisticien/dashboard');
        }
        $this->bouteilleM->updateStatut($qr, 'DISPONIBLE_STOCK', null);
        $this->syncStock($log['nom_entreprise']);
        flashSet('success', $qr.' — disponible en stock.');
        redirect('logisticien/dashboard');
    }

    /* Mise à jour manuelle du stock */
    public function mettreAJourStock(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('logisticien/dashboard');
        $log = $this->getLog();
        $propres = max(0, (int)($_POST['nombre_propres']   ?? 0));
        $aLaver = max(0, (int)($_POST['nombres_lavables'] ?? 0));
        $this->stockM->mettreAJour($log['nom_entreprise'], $propres, $aLaver);
        flashSet('success','Stock mis à jour.');
        redirect('logisticien/dashboard');
    }

    /* Ajouter de nouvelles bouteilles en lot */
    public function ajouterBouteilles(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('logisticien/dashboard');
        $log = $this->getLog();
        $quantite = max(1, min(50, (int)($_POST['quantite'] ?? 1)));

        $qrCodes = $this->bouteilleM->creerEnLot($log['nom_entreprise'], $quantite);

        // Générer les images QR
        $images = [];
        foreach ($qrCodes as $qr) {
            $images[$qr] = genererImageQr($qr, $qr);
        }

        $this->syncStock($log['nom_entreprise']);
        view('logisticien/qrcodes', ['images' => $images, 'log' => $log]);
    }

    /* Remplacer un QR code abîmé */
    public function remplacerQr(?string $p = null): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect('logisticien/dashboard');
        $log = $this->getLog();
        $ancienQr = trim($_POST['ancien_qr'] ?? '');

        $b = $this->bouteilleM->findByQr($ancienQr);
        if (!$b || $b['nom_entreprise'] !== $log['nom_entreprise']) {
            flashSet('error','Bouteille introuvable.'); redirect('logisticien/dashboard');
        }

        $nouveauQr = $this->bouteilleM->remplacerQr($ancienQr, $log['nom_entreprise']);
        $image = genererImageQr($nouveauQr, $nouveauQr);

        view('logisticien/qrcodes', [
            'images' => [$nouveauQr => $image],
            'log' => $log,
            'remplacement' => ['ancien' => $ancienQr, 'nouveau' => $nouveauQr],
        ]);
    }

    private function syncStock(string $nomEntreprise): void {
        $c = $this->stockM->compterDepuisStatuts($nomEntreprise);
        $this->stockM->mettreAJour($nomEntreprise, $c['propres'], $c['a_laver']);
    }
}
