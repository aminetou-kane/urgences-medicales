<?php
declare(strict_types=1);

class View
{
    /** Page complète avec menu latéral. */
    public static function render(string $vue, array $vars = [], string $titre = '', string $actif = ''): void
    {
        extract($vars, EXTR_SKIP);
        ob_start();
        require __DIR__ . '/../views/' . $vue . '.php';
        $contenu = ob_get_clean();
        require __DIR__ . '/../views/layout.php';
    }

    /** Page autonome (connexion, erreurs). */
    public static function bare(string $vue, array $vars = []): void
    {
        extract($vars, EXTR_SKIP);
        require __DIR__ . '/../views/' . $vue . '.php';
    }

    public static function error(int $code, string $titre, string $message): void
    {
        http_response_code($code);
        self::bare('errors/erreur', compact('code', 'titre', 'message'));
        exit;
    }
}
