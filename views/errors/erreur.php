<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titre) ?> · Urgences Médicales</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="login-body">
<div class="error-page">
    <div class="error-code"><?= (int) $code ?></div>
    <h1><?= e($titre) ?></h1>
    <p class="muted"><?= e($message) ?></p>
    <a class="btn btn-primary" href="index.php">Retour au tableau de bord</a>
</div>
</body>
</html>
