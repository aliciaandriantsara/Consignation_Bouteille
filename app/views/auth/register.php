<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un compte — Consignation</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/css/app.css">
</head>

<body class="login-page">

    <div class="login-wrapper">
        <div class="login-art">
            <div class="art-orb art-orb--1"></div>
            <div class="art-orb art-orb--2"></div>
            <div class="art-orb art-orb--3"></div>
            <div class="login-art-text">
                <span class="art-logo">◈</span>
                <h1 class="art-headline">Consignation</h1>
                <p class="art-sub">Rejoignez le cycle de vie intelligent des bouteilles réutilisables</p>
                <div class="art-cycle">
                    <span class="cycle-item">Disponible</span>
                    <span class="cycle-arrow">→</span>
                    <span class="cycle-item">Emprunté</span>
                    <span class="cycle-arrow">→</span>
                    <span class="cycle-item">Rendu</span>
                    <span class="cycle-arrow">→</span>
                    <span class="cycle-item">Collecté</span>
                    <span class="cycle-arrow">→</span>
                    <span class="cycle-item">Lavé</span>
                    <span class="cycle-arrow">→</span>
                    <span class="cycle-item cycle-item--accent">Prêt</span>
                </div>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="login-form-inner">
                <h2 class="form-title">Créer un compte</h2>
                <p class="form-sub">Inscription client — suivez vos emprunts de bouteilles</p>

                <?php $flash = flashGet();
                if ($flash): ?>
                    <div class="flash flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
                <?php endif; ?>

                <form method="POST" action="/index.php?url=auth/register" class="auth-form" autocomplete="off">
                    <div class="field-group">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" required autocomplete="off"
                            value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" required autocomplete="off"
                            value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="cin">CIN</label>
                        <input type="text" id="cin" name="cin" required autocomplete="off"
                            value="<?= htmlspecialchars($_POST['cin'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="telephone" autocomplete="off">Téléphone (optionnel)</label>
                        <input type="text" id="telephone" name="telephone"
                            value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="email" autocomplete="off">Adresse e-mail</label>
                        <input type="email" id="email" name="email" required
                            placeholder="vous@exemple.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="password">Mot de passe</label>
                        <input type="password" id="password" name="password" required minlength="8"
                            placeholder="8 caractères minimum">
                    </div>
                    <div class="field-group">
                        <label for="password_confirm">Confirmer le mot de passe</label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="8"
                            placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn-primary btn-full">Créer mon compte →</button>
                </form>

                <p class="auth-switch">Déjà un compte ? <a href="/index.php?url=auth/login">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script src="/js/app.js"></script>
</body>

</html>