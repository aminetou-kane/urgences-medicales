<?php
declare(strict_types=1);
require_once __DIR__ . '/../models/JournalModel.php';

class JournalController
{
    const FAMILLES = [
        ''          => 'Toutes les actions',
        'connexion' => 'Connexions',
        'patient'   => 'Patients',
        'ressource' => 'Ressources',
        'don'       => 'Dons',
        'demande'   => 'Demandes de sang',
    ];

    public static function index(): void
    {
        $filtre = get_param('type');
        $filtre = array_key_exists($filtre, self::FAMILLES) ? $filtre : '';
        $page = max(1, (int) get_param('page', '1'));
        $parPage = 20;
        list($entrees, $total) = JournalModel::liste($filtre, $page, $parPage);
        View::render('journal', [
            'entrees' => $entrees,
            'total'   => $total,
            'page'    => $page,
            'pages'   => max(1, (int) ceil($total / $parPage)),
            'filtre'  => $filtre,
            'familles' => self::FAMILLES,
        ], "Journal d'activité", 'journal');
    }
}
