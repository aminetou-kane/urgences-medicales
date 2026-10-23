<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/UtilisateurModel.php';

class Auth
{
    const ROLES = [
        'medecin'   => 'Médecin',
        'infirmier' => 'Infirmier(ère)',
        'accueil'   => 'Accueil',
    ];

    /** Permissions par rôle : une seule matrice, vérifiée dans le routeur. */
    const PERMISSIONS = [
        'dashboard'          => ['medecin', 'infirmier', 'accueil'],
        'patients.voir'      => ['medecin', 'infirmier', 'accueil'],
        'patients.ajouter'   => ['medecin', 'infirmier', 'accueil'],
        'patients.modifier'  => ['medecin', 'infirmier'],
        'patients.supprimer' => ['medecin'],
        'patients.exporter'  => ['medecin', 'infirmier'],
        'ressources.voir'    => ['medecin', 'infirmier'],
        'ressources.gerer'   => ['medecin', 'infirmier'],
        'sang.voir'          => ['medecin', 'infirmier'],
        'sang.gerer'         => ['medecin', 'infirmier'],
        'sang.valider'       => ['medecin'],
        'journal.voir'       => ['medecin'],
    ];

    const MAX_ECHECS = 5;
    const VERROU_SECONDES = 30;

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']);
    }

    public static function can(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        return in_array($user['role'], self::PERMISSIONS[$permission] ?? [], true);
    }

    public static function roleLabel(?string $role): string
    {
        return self::ROLES[$role ?? ''] ?? 'Utilisateur';
    }

    /** Retourne null si OK, sinon le message d'erreur à afficher. */
    public static function attempt(string $login, string $password): ?string
    {
        $verrou = (int) ($_SESSION['verrou_jusqua'] ?? 0);
        if ($verrou > time()) {
            return 'Trop de tentatives. Réessayez dans ' . ($verrou - time()) . ' secondes.';
        }

        $user = $login !== '' ? UtilisateurModel::getByLogin($login) : null;
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            unset($_SESSION['echecs'], $_SESSION['verrou_jusqua']);
            $_SESSION['user'] = [
                'id'     => (int) $user['id'],
                'nom'    => $user['nom'],
                'prenom' => $user['prenom'],
                'role'   => $user['role'],
            ];
            return null;
        }

        $_SESSION['echecs'] = (int) ($_SESSION['echecs'] ?? 0) + 1;
        if ($_SESSION['echecs'] >= self::MAX_ECHECS) {
            $_SESSION['verrou_jusqua'] = time() + self::VERROU_SECONDES;
            $_SESSION['echecs'] = 0;
        }
        return 'Identifiants incorrects.';
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
