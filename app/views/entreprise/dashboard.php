<?php $pageTitle = 'Dashboard Entreprise — ' . htmlspecialchars($e['nom_entreprise']); ?>
<?php require APP . '/views/shared/header.php'; ?>

<div class="dashboard entreprise-dash">
    <div class="dash-header">
        <div>
            <h1 class="dash-title">Tableau de bord</h1>
            <p class="dash-subtitle">🏭 <?= htmlspecialchars($e['nom_entreprise']) ?></p>
        </div>
    </div>

    <!-- Cycle de vie -->
    <section class="section">
        <h2 class="section-title">Cycle de vie des bouteilles</h2>
        <div class="cycle-dashboard">
            <?php
            $cycle = [
                'DISPONIBLE_REVENDEUR' => ['Dispo. revendeur', '🏪', 'green'],
                'EMPRUNTEE'            => ['Empruntée', '↗', 'blue'],
                'RENDUE_REVENDEUR'     => ['Rendue', '↩', 'teal'],
                'EN_COLLECTE'          => ['En collecte', '🚚', 'orange'],
                'EN_STOCK_ENTREPRISE'  => ['En stock', '📦', 'purple'],
                'A_LAVER'              => ['À laver', '〜', 'red'],
                'PROPRE'               => ['Propre', '✓', 'green'],
                'DISPONIBLE_STOCK'     => ['Dispo. stock', '⬜', 'teal'],
                'LIVREE_REVENDEUR'     => ['Livrée', '✈', 'blue'],
            ];
            foreach ($cycle as $key => [$label, $icon, $color]):
                $val = $statsStatut[$key] ?? 0;
            ?>
                <div class="cycle-card cycle--<?= $color ?>">
                    <div class="cycle-icon"><?= $icon ?></div>
                    <div class="cycle-value"><?= $val ?></div>
                    <div class="cycle-label"><?= $label ?></div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Stats par revendeur -->
    <?php if (!empty($statsRev)): ?>
        <section class="section">
            <h2 class="section-title">Répartition par revendeur</h2>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Revendeur</th>
                            <th>Statut</th>
                            <th>Bouteilles</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($statsRev as $s): ?>
                            <tr>
                                <td><?= htmlspecialchars($s['nom_boutique_actuel']) ?></td>
                                <td><span class="badge badge--<?= strtolower($s['statut']) ?>"><?= $s['statut'] ?></span></td>
                                <td><?= $s['total'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>

    <!-- Commandes -->
    <section class="section">
        <h2 class="section-title">📋 Commandes des revendeurs</h2>
        <?php if (empty($commandes)): ?>
            <p class="empty-state">Aucune commande.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Revendeur</th>
                            <th>Adresse</th>
                            <th>Qté</th>
                            <th>Date</th>
                            <th>Statut</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $cmd): ?>
                            <tr>
                                <td><?= htmlspecialchars($cmd['nom_boutique_actuel']) ?></td>
                                <td><?= htmlspecialchars($cmd['adresse'] ?? '—') ?></td>
                                <td><?= $cmd['quantite'] ?></td>
                                <td><?= substr($cmd['date_commande'], 0, 16) ?></td>
                                <td><span class="badge badge--<?= strtolower($cmd['statut']) ?>"><?= $cmd['statut'] ?></span></td>
                                <td>
                                    <?php if ($cmd['statut'] === 'EN_ATTENTE'): ?>
                                        <button class="btn-sm btn-primary"
                                            onclick="openModalLivraison(
                                '<?= htmlspecialchars($cmd['nom_boutique_actuel'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($cmd['nom_entreprise'], ENT_QUOTES) ?>',
                                '<?= htmlspecialchars($cmd['date_commande'], ENT_QUOTES) ?>',
                                <?= (int)$cmd['quantite'] ?>
                            )">Valider</button>
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

    <!-- Collectes -->
    <section class="section">
        <h2 class="section-title">🚚 Collectes (revendeur → stock)</h2>
        <button class="btn-primary" style="margin-bottom:1rem"
            onclick="openModalCollecte()">+ Créer une collecte</button>
        <?php if (empty($collectes)): ?>
            <p class="empty-state">Aucune collecte planifiée.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Revendeur</th>
                            <th>Livreur</th>
                            <th>Date prévue</th>
                            <th>Bouteilles</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($collectes as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['nom_boutique_actuel']) ?></td>
                                <td><?= htmlspecialchars($c['livreur_prenom'] . ' ' . $c['livreur_nom']) ?></td>
                                <td><?= htmlspecialchars($c['date_collecte_prevue']) ?></td>
                                <td><?= $c['nb_bouteilles'] ?></td>
                                <td><span class="badge badge--<?= strtolower($c['statut']) ?>"><?= $c['statut'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Transactions -->
    <section class="section">
        <h2 class="section-title">Suivi des emprunts clients</h2>
        <?php if (empty($transactions)): ?>
            <p class="empty-state">Aucune transaction.</p>
        <?php else: ?>
            <div class="table-wrapper">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Bouteille QR</th>
                            <th>Client</th>
                            <th>Revendeur</th>
                            <th>Emprunté le</th>
                            <th>Rendu le</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $t): ?>
                            <tr class="<?= $t['statut'] === 'EN_COURS' ? 'row-active' : '' ?>">
                                <td><code><?= htmlspecialchars($t['qr_code_bouteille']) ?></code></td>
                                <td><?= htmlspecialchars($t['prenom'] . ' ' . $t['nom']) ?></td>
                                <td><?= htmlspecialchars($t['nom_boutique']) ?></td>
                                <td><?= substr($t['date_emprunt'], 0, 16) ?></td>
                                <td><?= $t['date_retour'] ? substr($t['date_retour'], 0, 16) : '—' ?></td>
                                <td><span class="badge badge--<?= strtolower($t['statut']) ?>"><?= $t['statut'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- Gestion des comptes -->
    <section class="section">
        <h2 class="section-title">👥 Gestion des comptes</h2>
        <p class="text-muted" style="margin-bottom:1rem">
            Créez les comptes de vos revendeurs, livreurs et logisticiens.
        </p>
        <div style="display:flex;gap:.75rem;flex-wrap:wrap">
            <button class="btn-primary" onclick="openModal('modal-revendeur')">+ Créer un revendeur</button>
            <button class="btn-primary" onclick="openModal('modal-livreur')">+ Créer un livreur</button>
            <button class="btn-primary" onclick="openModal('modal-logisticien')">+ Créer un logisticien</button>
        </div>
    </section>
</div>

<!-- Modal : valider commande + choisir bouteilles -->
<div id="modal-livraison" class="modal hidden">
    <div class="modal-backdrop" onclick="closeModal('modal-livraison')"></div>
    <div class="modal-box" style="max-width:560px">
        <h3>Valider la commande</h3>
        <form method="POST" action="/index.php?url=entreprise/validerCommande" id="form-livraison">
            <input type="hidden" name="nom_boutique" id="m-nom-boutique">
            <input type="hidden" name="nom_entreprise" id="m-nom-entreprise">
            <input type="hidden" name="date_commande" id="m-date-commande">

            <div class="field-group">
                <label>Livreur assigné</label>
                <select name="CIN_livreur" id="sel-livreur" required>
                    <option value="">Chargement…</option>
                </select>
            </div>
            <div class="field-group">
                <label>Date de livraison prévue</label>
                <input type="date" name="date_prevue" value="<?= date('Y-m-d') ?>" required>
            </div>

            <!-- Sélection des bouteilles -->
            <div class="field-group">
                <label>
                    Sélectionner les bouteilles
                    <span id="compteur-selection" class="text-muted">(0 / <span id="qte-requise">0</span> sélectionnées)</span>
                </label>
                <div id="liste-bouteilles" class="bouteilles-list">
                    <p class="text-muted">Chargement des bouteilles disponibles…</p>
                </div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn-secondary"
                    onclick="closeModal('modal-livraison')">Annuler</button>
                <button type="submit" class="btn-primary" id="btn-confirmer" disabled>
                    Confirmer →
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal : créer collecte -->
<div id="modal-collecte" class="modal hidden">
    <div class="modal-backdrop" onclick="closeModal('modal-collecte')"></div>
    <div class="modal-box">
        <h3>Créer une collecte</h3>
        <p class="text-muted" style="margin-bottom:1rem">
            Toutes les bouteilles <strong>RENDUE_REVENDEUR</strong> chez ce revendeur
            seront automatiquement liées à cette collecte.
        </p>
        <form method="POST" action="/index.php?url=entreprise/creerCollecte">
            <div class="field-group">
                <label>Revendeur</label>
                <select name="nom_boutique" id="sel-revendeur" required>
                    <option value="">Chargement…</option>
                </select>
            </div>
            <div class="field-group">
                <label>Livreur</label>
                <select name="CIN_livreur" id="sel-livreur-c" required>
                    <option value="">Chargement…</option>
                </select>
            </div>
            <div class="field-group">
                <label>Date prévue</label>
                <input type="date" name="date_prevue" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary"
                    onclick="closeModal('modal-collecte')">Annuler</button>
                <button type="submit" class="btn-primary">Créer →</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal : créer revendeur -->
<div id="modal-revendeur" class="modal hidden">
    <div class="modal-backdrop" onclick="closeModal('modal-revendeur')"></div>
    <div class="modal-box">
        <h3>Créer un compte revendeur</h3>
        <form method="POST" action="/index.php?url=entreprise/creerRevendeur">
            <div class="field-group">
                <label>Nom de la boutique</label>
                <input type="text" name="nom_boutique" required>
            </div>
            <div class="field-group">
                <label>Adresse (optionnel)</label>
                <input type="text" name="adresse">
            </div>
            <div class="field-group">
                <label>Adresse e-mail</label>
                <input type="email" name="email" required>
            </div>
            <div class="field-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required minlength="8">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('modal-revendeur')">Annuler</button>
                <button type="submit" class="btn-primary">Créer →</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal : créer livreur -->
<div id="modal-livreur" class="modal hidden">
    <div class="modal-backdrop" onclick="closeModal('modal-livreur')"></div>
    <div class="modal-box">
        <h3>Créer un compte livreur</h3>
        <form method="POST" action="/index.php?url=entreprise/creerLivreur">
            <div class="field-group">
                <label>Nom</label>
                <input type="text" name="nom" required>
            </div>
            <div class="field-group">
                <label>Prénom</label>
                <input type="text" name="prenom" required>
            </div>
            <div class="field-group">
                <label>CIN</label>
                <input type="text" name="cin" required>
            </div>
            <div class="field-group">
                <label>Adresse e-mail</label>
                <input type="email" name="email" required>
            </div>
            <div class="field-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required minlength="8">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('modal-livreur')">Annuler</button>
                <button type="submit" class="btn-primary">Créer →</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal : créer logisticien -->
<div id="modal-logisticien" class="modal hidden">
    <div class="modal-backdrop" onclick="closeModal('modal-logisticien')"></div>
    <div class="modal-box">
        <h3>Créer un compte logisticien</h3>
        <form method="POST" action="/index.php?url=entreprise/creerLogisticien">
            <div class="field-group">
                <label>Nom</label>
                <input type="text" name="nom" required>
            </div>
            <div class="field-group">
                <label>Prénom</label>
                <input type="text" name="prenom" required>
            </div>
            <div class="field-group">
                <label>CIN</label>
                <input type="text" name="cin" required>
            </div>
            <div class="field-group">
                <label>Adresse e-mail</label>
                <input type="email" name="email" required>
            </div>
            <div class="field-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required minlength="8">
            </div>
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('modal-logisticien')">Annuler</button>
                <button type="submit" class="btn-primary">Créer →</button>
            </div>
        </form>
    </div>
</div>

<style>
    .bouteilles-list {
        background: var(--bg);
        border: 1px solid var(--border);
        border-radius: var(--radius);
        padding: .75rem;
        max-height: 220px;
        overflow-y: auto;
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
    }

    .bouteille-checkbox {
        display: flex;
        align-items: center;
        gap: .4rem;
        background: var(--bg-card);
        border: 1px solid var(--border);
        border-radius: 6px;
        padding: .3rem .7rem;
        cursor: pointer;
        font-size: .8rem;
        transition: all .2s;
        user-select: none;
    }

    .bouteille-checkbox:hover {
        border-color: var(--accent);
    }

    .bouteille-checkbox.selected {
        border-color: var(--accent);
        background: var(--accent-glow);
        color: var(--accent);
    }

    .bouteille-checkbox input {
        display: none;
    }
</style>

<script>
    let qteRequise = 0;

    async function openModalLivraison(nomBoutique, nomEntreprise, dateCommande, quantite) {
        document.getElementById('m-nom-boutique').value = nomBoutique;
        document.getElementById('m-nom-entreprise').value = nomEntreprise;
        document.getElementById('m-date-commande').value = dateCommande;
        qteRequise = quantite;
        document.getElementById('qte-requise').textContent = quantite;
        document.getElementById('compteur-selection').querySelector('span').textContent = quantite;
        document.getElementById('modal-livraison').classList.remove('hidden');

        // Charger livreurs et bouteilles en parallèle
        const [livreurs, bouteilles] = await Promise.all([
            fetch('/index.php?url=entreprise/livreurs').then(r => r.json()),
            fetch('/index.php?url=entreprise/bouteillesDisponibles').then(r => r.json()),
        ]);

        // Remplir select livreurs
        document.getElementById('sel-livreur').innerHTML =
            livreurs.map(l =>
                `<option value="${l.CIN}">${l.prenom} ${l.nom} (${l.CIN})</option>`
            ).join('');

        // Afficher les bouteilles disponibles comme cases à cocher stylisées
        const liste = document.getElementById('liste-bouteilles');
        if (!bouteilles.length) {
            liste.innerHTML = '<p class="text-muted">Aucune bouteille disponible en stock.</p>';
            return;
        }
        liste.innerHTML = bouteilles.map(b =>
            `<label class="bouteille-checkbox" onclick="toggleBouteille(this)">
            <input type="checkbox" name="qr_codes[]" value="${b.qr_code}">
            <code>${b.qr_code}</code>
         </label>`
        ).join('');
    }

    function toggleBouteille(label) {
        const cb = label.querySelector('input');
        cb.checked = !cb.checked;
        label.classList.toggle('selected', cb.checked);
        mettreAJourCompteur();
    }

    function mettreAJourCompteur() {
        const nb = document.querySelectorAll('.bouteille-checkbox.selected').length;
        document.getElementById('compteur-selection').innerHTML =
            `(${nb} / <span id="qte-requise">${qteRequise}</span> sélectionnées)`;
        // Activer le bouton seulement si la bonne quantité est sélectionnée
        document.getElementById('btn-confirmer').disabled = (nb !== qteRequise);
    }

    async function openModalCollecte() {
        document.getElementById('modal-collecte').classList.remove('hidden');
        const [livreurs, revendeurs] = await Promise.all([
            fetch('/index.php?url=entreprise/livreurs').then(r => r.json()),
            fetch('/index.php?url=entreprise/revendeurs').then(r => r.json()),
        ]);
        document.getElementById('sel-livreur-c').innerHTML =
            livreurs.map(l =>
                `<option value="${l.CIN}">${l.prenom} ${l.nom} (${l.CIN})</option>`
            ).join('');
        document.getElementById('sel-revendeur').innerHTML =
            revendeurs.map(r =>
                `<option value="${r.nom_boutique}">${r.nom_boutique}</option>`
            ).join('');
    }

    function openModal(id) {
        document.getElementById(id).classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }
</script>

<?php require APP . '/views/shared/footer.php'; ?>