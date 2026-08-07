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
            <div class="stat-label">Propres</div>
        </div>
        <div class="stat-card stat--red">
            <div class="stat-icon">〜</div>
            <div class="stat-value"><?= $calcul['a_laver'] ?></div>
            <div class="stat-label">À laver</div>
        </div>
        <div class="stat-card stat--orange">
            <div class="stat-icon">🧴</div>
            <div class="stat-value"><?= count($enLavage) ?></div>
            <div class="stat-label">En lavage</div>
        </div>
        <div class="stat-card stat--blue">
            <div class="stat-icon">📦</div>
            <div class="stat-value"><?= count($toutes) ?></div>
            <div class="stat-label">Total bouteilles</div>
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
                <p class="empty-state">Aucune bouteille en attente.</p>
            <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>QR Code</th><th>Statut</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($bouts as $b): ?>
                    <tr>
                        <td><code><?= htmlspecialchars($b['qr_code']) ?></code></td>
                        <td><span class="badge badge--<?= strtolower($b['statut']) ?>"><?= $b['statut'] ?></span></td>
                        <td>
                            <?php if ($b['statut'] === 'EN_STOCK_ENTREPRISE'): ?>
                            <form method="POST" action="/index.php?url=logisticien/demarrerLavage" class="inline-form">
                                <input type="hidden" name="qr_code" value="<?= htmlspecialchars($b['qr_code']) ?>">
                                <button type="submit" class="btn-sm btn-secondary">→ Démarrer lavage</button>
                            </form>
                            <?php elseif ($b['statut'] === 'A_LAVER'): ?>
                            <form method="POST" action="/index.php?url=logisticien/terminerLavage" class="inline-form">
                                <input type="hidden" name="qr_code" value="<?= htmlspecialchars($b['qr_code']) ?>">
                                <button type="submit" class="btn-sm btn-primary">→ Marquer propre</button>
                            </form>
                            <?php elseif ($b['statut'] === 'PROPRE'): ?>
                            <form method="POST" action="/index.php?url=logisticien/mettreEnStock" class="inline-form">
                                <input type="hidden" name="qr_code" value="<?= htmlspecialchars($b['qr_code']) ?>">
                                <button type="submit" class="btn-sm btn-primary">→ Mettre en stock</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </section>

        <!-- Stock manuel + lavages en cours -->
        <section class="section">
            <?php if (!empty($enLavage)): ?>
            <h2 class="section-title">🧴 Lavages en cours</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead><tr><th>QR Code</th><th>Début</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php foreach ($enLavage as $lv): ?>
                    <tr class="row-active">
                        <td><code><?= htmlspecialchars($lv['qr_code_bouteille']) ?></code></td>
                        <td><?= substr($lv['date_debut'],0,16) ?></td>
                        <td>
                            <form method="POST" action="/index.php?url=logisticien/terminerLavage" class="inline-form">
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
            <form method="POST" action="/index.php?url=logisticien/mettreAJourStock" class="stock-form">
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

    <!-- ── Gestion QR codes ─────────────────────────────── -->
    <section class="section">
        <h2 class="section-title">📱 Gestion des QR codes</h2>
        <div class="dash-grid-2">

            <!-- Ajouter nouvelles bouteilles -->
            <div>
                <h3 class="subsection-title">Ajouter de nouvelles bouteilles</h3>
                <p class="text-muted" style="margin-bottom:1rem">
                    Génère les QR codes et les enregistre en base. Maximum 50 à la fois.
                </p>
                <form method="POST" action="/index.php?url=logisticien/ajouterBouteilles">
                    <div class="field-group">
                        <label>Nombre de bouteilles</label>
                        <input type="number" name="quantite" min="1" max="50" value="1" required>
                    </div>
                    <button type="submit" class="btn-primary">Générer les QR codes →</button>
                </form>
            </div>

            <!-- Remplacer QR code abîmé -->
            <div>
                <h3 class="subsection-title">Remplacer un QR code abîmé</h3>
                <p class="text-muted" style="margin-bottom:1rem">
                    Si le QR code d'une bouteille est illisible, saisissez l'ancien code
                    pour en générer un nouveau.
                </p>
                <form method="POST" action="/index.php?url=logisticien/remplacerQr">
                    <div class="field-group">
                        <label>Ancien QR code</label>
                        <input type="text" name="ancien_qr" placeholder="ex: AQUA-1717000000-A3F2" required>
                    </div>
                    <button type="submit" class="btn-secondary">Remplacer →</button>
                </form>
            </div>
        </div>
    </section>

    <!-- Liste toutes les bouteilles -->
    <section class="section">
        <h2 class="section-title">Liste complète des bouteilles</h2>
        <?php if (empty($toutes)): ?>
            <p class="empty-state">Aucune bouteille enregistrée.</p>
        <?php else: ?>
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr><th>QR Code</th><th>Statut</th><th>Revendeur actuel</th><th>Mis à jour</th></tr>
                </thead>
                <tbody>
                <?php foreach ($toutes as $b): ?>
                <tr>
                    <td><code><?= htmlspecialchars($b['qr_code']) ?></code></td>
                    <td><span class="badge badge--<?= strtolower($b['statut']) ?>"><?= $b['statut'] ?></span></td>
                    <td><?= htmlspecialchars($b['revendeur_nom'] ?? '—') ?></td>
                    <td><?= substr($b['updated_at'],0,16) ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php require APP . '/views/shared/footer.php'; ?>
