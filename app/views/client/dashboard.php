<?php $pageTitle = 'Mon espace client'; ?>
<?php require APP . '/views/shared/header.php'; ?>

<div class="dashboard client-dash">
    <div class="dash-header">
        <div>
            <h1 class="dash-title">Mon espace</h1>
            <p class="dash-subtitle">👤 <?= htmlspecialchars($client['prenom'].' '.$client['nom']) ?></p>
        </div>
    </div>

    <div class="stats-grid stats-grid--2">
        <div class="stat-card stat--blue">
            <div class="stat-icon">↗</div>
            <div class="stat-value"><?= count($enCours) ?></div>
            <div class="stat-label">Non rendues</div>
        </div>
        <div class="stat-card stat--green">
            <div class="stat-icon">✓</div>
            <div class="stat-value"><?= count($historique) ?></div>
            <div class="stat-label">Rendues</div>
        </div>
    </div>

    <?php if (!empty($enCours)): ?>
    <section class="section card-primary">
        <h2 class="section-title">⏳ Bouteilles non encore rendues</h2>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Bouteille QR</th><th>Revendeur</th><th>Entreprise</th><th>Emprunté le</th></tr></thead>
                <tbody>
                <?php foreach ($enCours as $t): ?>
                    <tr class="row-active">
                        <td><code><?= htmlspecialchars($t['bouteille_qr']) ?></code></td>
                        <td><?= htmlspecialchars($t['nom_boutique']) ?></td>
                        <td><?= htmlspecialchars($t['nom_entreprise']) ?></td>
                        <td><?= substr($t['date_emprunt'],0,16) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if (!empty($historique)): ?>
    <section class="section">
        <h2 class="section-title">Historique des emprunts</h2>
        <div class="table-wrapper">
            <table class="data-table">
                <thead><tr><th>Bouteille QR</th><th>Revendeur</th><th>Emprunté le</th><th>Rendu le</th></tr></thead>
                <tbody>
                <?php foreach ($historique as $t): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($t['bouteille_qr']) ?></code></td>
                        <td><?= htmlspecialchars($t['nom_boutique']) ?></td>
                        <td><?= substr($t['date_emprunt'],0,16) ?></td>
                        <td><?= $t['date_retour'] ? substr($t['date_retour'],0,16) : '—' ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>

    <?php if (empty($enCours) && empty($historique)): ?>
        <p class="empty-state">Vous n'avez aucun emprunt enregistré.</p>
    <?php endif; ?>
</div>

<?php require APP . '/views/shared/footer.php'; ?>
