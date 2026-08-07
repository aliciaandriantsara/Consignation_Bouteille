<?php $pageTitle = 'Gestion du stock — Logisticien'; ?>
<?php require APP . '/views/shared/header.php'; ?>

<div class="dashboard logisticien-dash">
    <div class="dash-header">
        <div>
            <h1 class="dash-title">Gestion du stock</h1>
            <p class="dash-subtitle">📦 <?= htmlspecialchars($log['prenom'].' '.$log['nom']) ?> · CIN : <?= htmlspecialchars($log['CIN']) ?></p>
        </div>
    </div>

    <div class="stats-grid stats-grid--4">
        <div class="stat-card stat--green">
            <div class="stat-icon">✓</div>
            <div class="stat-value"><?= $calcul['propres'] ?></div>
            <div class="stat-label">Propres (calculé)</div>
        </div>
        <div class="stat-card stat--red">
            <div class="stat-icon">〜</div>
            <div class="stat-value"><?= $calcul['a_laver'] ?></div>
            <div class="stat-label">À laver (calculé)</div>
        </div>
        <div class="stat-card stat--orange">
            <div class="stat-icon">🧴</div>
            <div class="stat-value"><?= count($enLavage) ?></div>
            <div class="stat-label">En lavage</div>
        </div>
        <div class="stat-card stat--blue">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?= $stock['nombre_bouteilles_propres'] ?? 0 ?></div>
            <div class="stat-label">Stock manuel</div>
        </div>
    </div>

    <div class="dash-grid-2">

        <!-- Cycle de lavage -->
        <section class="section card-primary">
            <h2 class="section-title">🧴 Cycle de lavage</h2>
            <div class="lavage-cycle">
                <div class="lavage-step lavage-step--active">
                    <span class="step-num">1</span>
                    <span>EN_STOCK_ENTREPRISE</span>
                    <span class="step-arrow">→</span>
                </div>
                <div class="lavage-step">
                    <span class="step-num">2</span>
                    <span>A_LAVER</span>
                    <span class="step-arrow">→</span>
                </div>
                <div class="lavage-step">
                    <span class="step-num">3</span>
                    <span>PROPRE</span>
                    <span class="step-arrow">→</span>
                </div>
                <div class="lavage-step lavage-step--end">
                    <span class="step-num">4</span>
                    <span>DISPONIBLE_STOCK</span>
                </div>
            </div>

            <h3 class="subsection-title">Bouteilles à traiter</h3>
            <?php if (empty($bouts)): ?>
                <p class="empty-state">Aucune bouteille en attente de traitement.</p>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr><th>QR Code</th><th>Statut actuel</th><th>Action suivante</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($bouts as $b): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($b['qr_code']) ?></code></td>
                        <td>
                            <span class="badge badge--<?= strtolower($b['statut']) ?>">
                                <?= $b['statut'] ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($b['statut'] === 'EN_STOCK_ENTREPRISE'): ?>
                            <form method="POST" action="/index.php/logisticien/demarrerLavage" class="inline-form">
                                <!-- qr_code est la clé naturelle de bouteille -->
                                <input type="hidden" name="qr_code" value="<?= htmlspecialchars($b['qr_code']) ?>">
                                <button type="submit" class="btn-sm btn-secondary">→ Démarrer lavage</button>
                            </form>

                            <?php elseif ($b['statut'] === 'A_LAVER'): ?>
                            <form method="POST" action="/index.php/logisticien/terminerLavage" class="inline-form">
                                <input type="hidden" name="qr_code" value="<?= htmlspecialchars($b['qr_code']) ?>">
                                <button type="submit" class="btn-sm btn-primary">→ Marquer propre</button>
                            </form>

                            <?php elseif ($b['statut'] === 'PROPRE'): ?>
                            <form method="POST" action="/index.php/logisticien/mettreEnStock" class="inline-form">
                                <input type="hidden" name="qr_code" value="<?= htmlspecialchars($b['qr_code']) ?>">
                                <button type="submit" class="btn-sm btn-primary">→ Mettre en stock</button>
                            </form>

                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>

        <!-- Lavages en cours + mise à jour manuelle -->
        <section class="section">

            <?php if (!empty($enLavage)): ?>
            <h2 class="section-title">🧴 Lavages en cours</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr><th>QR Code</th><th>Début</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($enLavage as $lv): ?>
                    <tr class="row-active">
                        <td><code><?= htmlspecialchars($lv['qr_code_bouteille']) ?></code></td>
                        <td><?= substr($lv['date_debut'], 0, 16) ?></td>
                        <td>
                            <form method="POST" action="/index.php/logisticien/terminerLavage" class="inline-form">
                                <input type="hidden" name="qr_code" value="<?= htmlspecialchars($lv['qr_code_bouteille']) ?>">
                                <button type="submit" class="btn-sm btn-primary">✓ Propre</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <hr style="border-color:var(--border);margin:1.5rem 0">
            <?php endif; ?>

            <h2 class="section-title">Mise à jour manuelle du stock</h2>
            <p class="text-muted" style="margin-bottom:1rem">
                Les valeurs calculées (colonne gauche) sont automatiques.<br>
                Utilisez ce formulaire pour un ajustement manuel si nécessaire.
            </p>
            <form method="POST" action="/index.php/logisticien/mettreAJourStock" class="stock-form">
                <div class="field-group">
                    <label>Bouteilles propres</label>
                    <input type="number" name="nombre_propres" min="0"
                           value="<?= $stock['nombre_bouteilles_propres'] ?? 0 ?>" required>
                </div>
                <div class="field-group">
                    <label>Bouteilles à laver</label>
                    <input type="number" name="nombres_lavables" min="0"
                           value="<?= $stock['nombre_bouteilles_a_laver'] ?? 0 ?>" required>
                </div>
                <button type="submit" class="btn-primary">Mettre à jour</button>
            </form>
        </section>
    </div>
</div>

<?php require APP . '/views/shared/footer.php'; ?>
