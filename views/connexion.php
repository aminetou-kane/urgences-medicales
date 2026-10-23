<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Connexion · Urgences Médicales</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-body">
<div class="login">
    <section class="login-brand">
        <div class="brand">
            <span class="brand-logo"><svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M9 3h6v6h6v6h-6v6H9v-6H3V9h6z"/></svg></span>
            <span class="brand-text"><strong>Urgences</strong><small>Médicales</small></span>
        </div>
        <div class="login-pitch">
            <h1>Piloter le service des urgences, en temps réel.</h1>
            <p>Triage des patients, suivi des lits et équipements, banque de sang et traçabilité des actions : une seule plateforme pour toute l'équipe.</p>
            <ul>
                <li><?= icon('check', 18) ?> File d'attente classée par gravité</li>
                <li><?= icon('check', 18) ?> Ressources libérées automatiquement</li>
                <li><?= icon('check', 18) ?> Stock de sang, donneurs et demandes</li>
                <li><?= icon('check', 18) ?> Accès par rôle et journal d'activité</li>
            </ul>
        </div>
        <small class="login-foot">Projet universitaire · UGB Saint-Louis</small>
    </section>

    <section class="login-form-wrap">
        <form class="login-card" method="post" action="<?= e(url('login')) ?>" autocomplete="on">
            <?= csrf_field() ?>
            <h2>Connexion</h2>
            <p class="muted">Accédez à votre espace de travail.</p>

            <?php if ($erreur): ?>
                <div class="flash flash-error" role="alert"><?= icon('alert', 18) ?><span><?= e($erreur) ?></span></div>
            <?php endif; ?>

            <label class="field">
                <span>Identifiant</span>
                <div class="input-icon"><?= icon('user', 18) ?><input type="text" name="login" id="login" required autofocus autocomplete="username" value="<?= e($_POST['login'] ?? '') ?>"></div>
            </label>
            <label class="field">
                <span>Mot de passe</span>
                <div class="input-icon"><?= icon('lock', 18) ?><input type="password" name="password" id="password" required autocomplete="current-password"></div>
            </label>
            <button class="btn btn-primary btn-block" type="submit">Se connecter</button>

            <?php if (Config::get('demo_comptes')): ?>
                <div class="demo">
                    <strong>Comptes de démonstration</strong>
                    <p class="muted">Mot de passe : <code>pass123</code> · cliquez pour remplir</p>
                    <div class="demo-list">
                        <button type="button" class="demo-btn" data-login="moussa.diop">Médecin<small>moussa.diop</small></button>
                        <button type="button" class="demo-btn" data-login="awa.fall">Infirmière<small>awa.fall</small></button>
                        <button type="button" class="demo-btn" data-login="oumar.sene">Accueil<small>oumar.sene</small></button>
                    </div>
                </div>
            <?php endif; ?>
        </form>
    </section>
</div>
<script src="assets/js/app.js"></script>
</body>
</html>
