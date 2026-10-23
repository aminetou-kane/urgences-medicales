<?php
declare(strict_types=1);

/**
 * Point d'entrée unique : routage, session et contrôle d'accès.
 */
require_once __DIR__ . '/core/Config.php';
require_once __DIR__ . '/core/Database.php';
require_once __DIR__ . '/core/helpers.php';
require_once __DIR__ . '/core/View.php';
require_once __DIR__ . '/core/Auth.php';

Config::load();
date_default_timezone_set((string) Config::get('timezone', 'Africa/Dakar'));

if (Config::get('debug')) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL);
}

session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => !empty($_SERVER['HTTPS']),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

foreach (['Utilisateur', 'Dashboard', 'Patient', 'Ressource', 'Sang', 'Journal'] as $ctrl) {
    require_once __DIR__ . "/controllers/{$ctrl}Controller.php";
}

/**
 * Table des routes : action => [contrôleur, méthode, permission requise, verbe HTTP].
 * Toute action qui modifie des données passe en POST avec jeton CSRF.
 */
$routes = [
    'dashboard'          => ['DashboardController', 'index',            'dashboard',          'GET'],

    'patients'           => ['PatientController',   'index',            'patients.voir',      'GET'],
    'patients_export'    => ['PatientController',   'exporter',         'patients.exporter',  'GET'],
    'patient_ajouter'    => ['PatientController',   'ajouter',          'patients.ajouter',   'POST'],
    'patient_modifier'   => ['PatientController',   'modifier',         'patients.modifier',  'POST'],
    'patient_supprimer'  => ['PatientController',   'supprimer',        'patients.supprimer', 'POST'],

    'ressources'         => ['RessourceController', 'index',            'ressources.voir',    'GET'],
    'ressource_ajouter'  => ['RessourceController', 'ajouter',          'ressources.gerer',   'POST'],
    'ressource_modifier' => ['RessourceController', 'modifier',         'ressources.gerer',   'POST'],
    'ressource_liberer'  => ['RessourceController', 'liberer',          'ressources.gerer',   'POST'],
    'ressource_supprimer'=> ['RessourceController', 'supprimer',        'ressources.gerer',   'POST'],

    'sang'               => ['SangController',      'stock',            'sang.voir',          'GET'],
    'donneurs'           => ['SangController',      'donneurs',         'sang.voir',          'GET'],
    'donneur_ajouter'    => ['SangController',      'donneurAjouter',   'sang.gerer',         'POST'],
    'donneur_supprimer'  => ['SangController',      'donneurSupprimer', 'sang.gerer',         'POST'],
    'don_enregistrer'    => ['SangController',      'donEnregistrer',   'sang.gerer',         'POST'],
    'demandes'           => ['SangController',      'demandes',         'sang.voir',          'GET'],
    'demande_ajouter'    => ['SangController',      'demandeAjouter',   'sang.gerer',         'POST'],
    'demande_traiter'    => ['SangController',      'demandeTraiter',   'sang.valider',       'POST'],

    'journal'            => ['JournalController',   'index',            'journal.voir',       'GET'],
];

$action = (string) ($_GET['action'] ?? '');

if ($action === 'logout') {
    UtilisateurController::logout();
}
if (!Auth::check() || $action === 'login') {
    if (Auth::check()) {
        redirect('dashboard');
    }
    UtilisateurController::login();
    exit;
}

if ($action === '') {
    redirect('dashboard');
}
if (!isset($routes[$action])) {
    View::error(404, 'Page introuvable', "La page demandée n'existe pas.");
}

list($controleur, $methode, $permission, $verbe) = $routes[$action];

if (!Auth::can($permission)) {
    View::error(403, 'Accès refusé', "Votre rôle (" . Auth::roleLabel(Auth::user()['role']) . ") ne permet pas d'accéder à cette fonction.");
}
if ($_SERVER['REQUEST_METHOD'] !== $verbe) {
    View::error(405, 'Méthode non autorisée', 'Cette action doit être déclenchée depuis un formulaire.');
}
if ($verbe === 'POST') {
    csrf_verify();
}

try {
    $controleur::$methode();
} catch (Throwable $e) {
    error_log($e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
    if (Config::get('debug')) {
        throw $e;
    }
    View::error(500, 'Erreur interne', "Une erreur est survenue. L'incident a été enregistré.");
}
