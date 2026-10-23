<?php
declare(strict_types=1);
require_once __DIR__ . '/../models/StatsModel.php';
require_once __DIR__ . '/../models/RessourceModel.php';
require_once __DIR__ . '/../models/SangModel.php';

class DashboardController
{
    public static function index(): void
    {
        $statuts = StatsModel::parStatut();
        $urgences = StatsModel::parUrgence();
        $data = [
            'statuts'      => $statuts,
            'urgences'     => $urgences,
            'actifs'       => $statuts['En attente'] + $statuts['En prise en charge'],
            'attenteMoy'   => StatsModel::attenteMoyenne(),
            'admissions'   => StatsModel::admissions7Jours(),
            'file'         => StatsModel::fileAttente(6),
            'ressources'   => StatsModel::ressourcesParType(),
            'compteurs'    => RessourceModel::compteurs(),
            'enRetard'     => StatsModel::urgencesEnRetard(15),
            'peutSang'     => Auth::can('sang.voir'),
            'stock'        => Auth::can('sang.voir') ? SangModel::stock() : [],
            'demandesAttente' => Auth::can('sang.voir') ? SangModel::demandesEnAttente() : 0,
        ];
        View::render('dashboard', $data, 'Tableau de bord', 'dashboard');
    }
}
