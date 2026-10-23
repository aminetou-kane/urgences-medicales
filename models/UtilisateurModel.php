<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Database.php';

class UtilisateurModel
{
    public static function getByLogin(string $login)
    {
        $stmt = Database::getConnection()->prepare('SELECT * FROM utilisateur WHERE login = :login');
        $stmt->execute([':login' => $login]);
        return $stmt->fetch();
    }

    public static function create(string $nom, string $prenom, string $login, string $password, string $role): bool
    {
        $stmt = Database::getConnection()->prepare(
            'INSERT INTO utilisateur (nom, prenom, login, password, role) VALUES (?, ?, ?, ?, ?)'
        );
        return $stmt->execute([$nom, $prenom, $login, password_hash($password, PASSWORD_DEFAULT), $role]);
    }
}
