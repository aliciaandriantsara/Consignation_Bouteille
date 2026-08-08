<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié — Consignation</title>
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
                <p class="art-sub">Gestion intelligente du cycle de vie des bouteilles réutilisables</p>
            </div>
        </div>

        <div class="login-form-panel">
            <div class="login-form-inner">
                <h2 class="form-title">Mot de passe oublié</h2>
                <p class="form-sub">Vérifiez votre identité pour définir un nouveau mot de passe</p>

                <?php $flash = flashGet();
                if ($flash): ?>
                    <div class="flash flash-<?= $flash['type'] ?>"><?= htmlspecialchars($flash['msg']) ?></div>
                <?php endif; ?>

                <form method="POST" action="/index.php?url=auth/forgotPassword" class="auth-form">
                    <div class="field-group">
                        <label for="email">Adresse e-mail</label>
                        <input type="email" id="email" name="email" required
                            placeholder="vous@exemple.com"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="cin">CIN</label>
                        <input type="text" id="cin" name="cin" required
                            value="<?= htmlspecialchars($_POST['cin'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="nom">Nom</label>
                        <input type="text" id="nom" name="nom" required
                            value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="prenom">Prénom</label>
                        <input type="text" id="prenom" name="prenom" required
                            value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                    </div>
                    <div class="field-group">
                        <label for="password">Nouveau mot de passe</label>
                        <input type="password" id="password" name="password" required minlength="8"
                            placeholder="8 caractères minimum">
                    </div>
                    <div class="field-group">
                        <label for="password_confirm">Confirmer le mot de passe</label>
                        <input type="password" id="password_confirm" name="password_confirm" required minlength="8"
                            placeholder="••••••••">
                    </div>
                    <button type="submit" class="btn-primary btn-full">Réinitialiser le mot de passe →</button>
                </form>

                <p class="auth-switch"><a href="/index.php?url=auth/login">← Retour à la connexion</a></p>
            </div>
        </div>
    </div>

    <script src="/js/app.js"></script>
</body>

</html>