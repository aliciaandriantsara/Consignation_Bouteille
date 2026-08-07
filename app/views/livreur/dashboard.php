<?php $pageTitle = 'Mes missions — Livreur'; ?>
<?php require APP . '/views/shared/header.php'; ?>

<div class="dashboard livreur-dash">
    <div class="dash-header">
        <div>
            <h1 class="dash-title">Mes missions</h1>
            <p class="dash-subtitle">🚚 <?= htmlspecialchars($livreur['prenom'].' '.$livreur['nom']) ?> · CIN : <?= htmlspecialchars($livreur['CIN']) ?></p>
        </div>
    </div>

    <div class="stats-grid stats-grid--4">
        <div class="stat-card stat--orange"><div class="stat-icon">📦</div><div class="stat-value"><?= count($livraisonsAFaire) ?></div><div class="stat-label">Livraisons à faire</div></div>
        <div class="stat-card stat--green"> <div class="stat-icon">✓</div> <div class="stat-value"><?= count($livraisonsFaites) ?></div><div class="stat-label">Livraisons faites</div></div>
        <div class="stat-card stat--blue">  <div class="stat-icon">↩</div> <div class="stat-value"><?= count($collectesAFaire) ?></div> <div class="stat-label">Collectes à faire</div></div>
        <div class="stat-card stat--teal">  <div class="stat-icon">✓</div> <div class="stat-value"><?= count($collectesFaites) ?></div> <div class="stat-label">Collectes faites</div></div>
    </div>

    <!-- Livraisons à faire -->
    <section class="section">
        <h2 class="section-title">📦 Livraisons à effectuer <small class="text-muted">(entreprise → revendeur)</small></h2>
        <?php if (empty($livraisonsAFaire)): ?>
            <p class="empty-state">Aucune livraison en attente.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Revendeur</th><th>Adresse</th><th>Qté</th><th>Date prévue</th><th>Statut</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($livraisonsAFaire as $l): ?>
                <tr class="row-active">
                    <td><?= htmlspecialchars($l['nom_boutique_actuel']) ?></td>
                    <td><?= htmlspecialchars($l['adresse'] ?? '—') ?></td>
                    <td><?= $l['quantite'] ?? '—' ?></td>
                    <td><?= htmlspecialchars($l['date_livraison_prevue']) ?></td>
                    <td><span class="badge badge--<?= strtolower($l['statut']) ?>"><?= $l['statut'] ?></span></td>
                    <td>
                        <!-- Clés naturelles composites en POST -->
                        <form method="POST" action="/index.php/livreur/cocherLivraison">
                            <input type="hidden" name="nom_boutique"   value="<?= htmlspecialchars($l['nom_boutique_actuel']) ?>">
                            <input type="hidden" name="nom_entreprise" value="<?= htmlspecialchars($l['nom_entreprise']) ?>">
                            <input type="hidden" name="date_commande"  value="<?= htmlspecialchars($l['date_commande']) ?>">
                            <button type="submit" class="btn-sm btn-primary"
                                    onclick="return confirm('Marquer cette livraison comme effectuée ?')">
                                ✓ Effectuée
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- Collectes à faire -->
    <section class="section">
        <h2 class="section-title">↩ Collectes à effectuer <small class="text-muted">(revendeur → entreprise)</small></h2>
        <?php if (empty($collectesAFaire)): ?>
            <p class="empty-state">Aucune collecte en attente.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Revendeur</th><th>Adresse</th><th>Bouteilles</th><th>Date prévue</th><th>Statut</th><th>Action</th></tr></thead>
                <tbody>
                <?php foreach ($collectesAFaire as $c): ?>
                <tr class="row-active">
                    <td><?= htmlspecialchars($c['nom_boutique_actuel']) ?></td>
                    <td><?= htmlspecialchars($c['adresse'] ?? '—') ?></td>
                    <td><?= $c['nb_bouteilles'] ?></td>
                    <td><?= htmlspecialchars($c['date_collecte_prevue']) ?></td>
                    <td><span class="badge badge--<?= strtolower($c['statut']) ?>"><?= $c['statut'] ?></span></td>
                    <td>
                        <form method="POST" action="/index.php/livreur/cocherCollecte">
                            <input type="hidden" name="nom_boutique"         value="<?= htmlspecialchars($c['nom_boutique_actuel']) ?>">
                            <input type="hidden" name="nom_entreprise"       value="<?= htmlspecialchars($c['nom_entreprise']) ?>">
                            <input type="hidden" name="date_collecte_prevue" value="<?= htmlspecialchars($c['date_collecte_prevue']) ?>">
                            <button type="submit" class="btn-sm btn-secondary"
                                    onclick="return confirm('Marquer cette collecte comme effectuée ?')">
                                ✓ Collectée
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>

    <!-- Historiques -->
    <?php if (!empty($livraisonsFaites) || !empty($collectesFaites)): ?>
    <section class="section">
        <h2 class="section-title">Historique effectué</h2>
        <div class="dash-grid-2">
            <?php if (!empty($livraisonsFaites)): ?>
            <div>
                <h3 class="subsection-title">Livraisons</h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>Revendeur</th><th>Qté</th><th>Date effective</th></tr></thead>
                        <tbody>
                        <?php foreach ($livraisonsFaites as $l): ?>
                        <tr>
                            <td><?= htmlspecialchars($l['nom_boutique_actuel']) ?></td>
                            <td><?= $l['quantite'] ?? '—' ?></td>
                            <td><?= $l['date_livraison_effective'] ? substr($l['date_livraison_effective'],0,16):'—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
            <?php if (!empty($collectesFaites)): ?>
            <div>
                <h3 class="subsection-title">Collectes</h3>
                <div class="table-wrapper">
                    <table class="data-table">
                        <thead><tr><th>Revendeur</th><th>Bouteilles</th><th>Date effective</th></tr></thead>
                        <tbody>
                        <?php foreach ($collectesFaites as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['nom_boutique_actuel']) ?></td>
                            <td><?= $c['nb_bouteilles'] ?></td>
                            <td><?= $c['date_collecte_effective'] ? substr($c['date_collecte_effective'],0,16):'—' ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require APP . '/views/shared/footer.php'; ?>
