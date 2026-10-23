<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/JournalModel.php';

class UtilisateurController
{
    public static function login(): void
    {
        $erreur = null;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();
            $login = input('login');
            $password = (string) ($_POST['password'] ?? '');
            if ($login === '' || $password === '') {
                $erreur = 'Veuillez remplir tous les champs.';
            } else {
                $erreur = Auth::attempt($login, $password);
                if ($erreur === null) {
                    JournalModel::log('connexion', 'Connexion de ' . $login);
                    redirect('dashboard');
                }
            }
        }
        View::bare('connexion', ['erreur' => $erreur]);
    }

    public static function logout(): void
    {
        if (Auth::check()) {
            JournalModel::log('deconnexion', 'Déconnexion');
        }
        Auth::logout();
        header('Location: index.php');
        exit;
    }
}
