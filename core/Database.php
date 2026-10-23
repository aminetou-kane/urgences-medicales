<?php
declare(strict_types=1);

require_once __DIR__ . '/Config.php';

class Database
{
    private static $conn = null;

    public static function getConnection(): PDO
    {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    'mysql:host=' . Config::get('db.host') . ';dbname=' . Config::get('db.name') . ';charset=utf8mb4',
                    (string) Config::get('db.user'),
                    (string) Config::get('db.pass'),
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES   => false,
                    ]
                );
            } catch (PDOException $e) {
                error_log('Connexion BDD impossible : ' . $e->getMessage());
                http_response_code(500);
                $detail = Config::get('debug') ? $e->getMessage() : 'Veuillez réessayer plus tard.';
                echo '<!DOCTYPE html><meta charset="utf-8"><body style="font-family:sans-serif;max-width:560px;margin:60px auto">'
                   . '<h2>Base de données indisponible</h2><p>' . htmlspecialchars($detail) . '</p></body>';
                exit;
            }
        }
        return self::$conn;
    }
}
