<?php
/**
 * Configuration de l'application.
 * Copiez ce fichier en config/config.php puis renseignez vos valeurs.
 * Le fichier config/config.php est ignoré par Git : il ne doit JAMAIS être publié.
 */
return [
    'db' => [
        'host' => getenv('DB_HOST') ?: 'localhost',
        'name' => getenv('DB_NAME') ?: 'urgences_db',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
    ],
    'timezone'        => 'Africa/Dakar',
    'debug'           => false,   // true uniquement en développement
    'demo_comptes'    => true,    // affiche les comptes de démonstration sur la page de connexion
    'delai_don_jours' => 56,      // délai minimal entre deux dons de sang
    'par_page'        => 10,      // lignes par page dans les listes
];
