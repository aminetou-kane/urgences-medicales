<?php
declare(strict_types=1);
require_once __DIR__ . '/../core/Database.php';

class JournalModel
{
    /** Enregistre une action. Ne doit jamais bloquer l'application. */
    public static function log(string $action, string $details = ''): void
    {
        try {
            $user = $_SESSION['user']['id'] ?? null;
            $stmt = Database::getConnection()->prepare(
                'INSERT INTO journal (utilisateur_id, action, details) VALUES (?, ?, ?)'
            );
            $stmt->execute([$user, $action, mb_substr($details, 0, 255)]);
        } catch (Throwable $e) {
            error_log('Journal : ' . $e->getMessage());
        }
    }

    public static function liste(string $filtre, int $page, int $parPage): array
    {
        $db = Database::getConnection();
        $where = '';
        $params = [];
        if ($filtre !== '') {
            $where = 'WHERE j.action LIKE ?';
            $params[] = $filtre . '%';
        }
        $stmt = $db->prepare("SELECT COUNT(*) FROM journal j $where");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        $offset = max(0, ($page - 1) * $parPage);
        $stmt = $db->prepare(
            "SELECT j.*, CONCAT(u.prenom, ' ', u.nom) AS auteur, u.role
             FROM journal j LEFT JOIN utilisateur u ON u.id = j.utilisateur_id
             $where ORDER BY j.id DESC LIMIT " . (int) $parPage . ' OFFSET ' . (int) $offset
        );
        $stmt->execute($params);
        return [$stmt->fetchAll(), $total];
    }
}
