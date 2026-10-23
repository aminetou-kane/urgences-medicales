<?php
declare(strict_types=1);

class Config
{
    private static $data = null;

    public static function load(): void
    {
        $file = __DIR__ . '/../config/config.php';
        if (!is_file($file)) {
            http_response_code(500);
            echo '<!DOCTYPE html><meta charset="utf-8"><body style="font-family:sans-serif;max-width:560px;margin:60px auto">'
               . '<h2>Configuration manquante</h2>'
               . '<p>Copiez <code>config/config.example.php</code> en <code>config/config.php</code> '
               . 'puis renseignez les identifiants de votre base de données.</p></body>';
            exit;
        }
        self::$data = require $file;
    }

    public static function get(string $key, $default = null)
    {
        if (self::$data === null) {
            self::load();
        }
        $value = self::$data;
        foreach (explode('.', $key) as $part) {
            if (!is_array($value) || !array_key_exists($part, $value)) {
                return $default;
            }
            $value = $value[$part];
        }
        return $value;
    }
}
