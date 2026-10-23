<?php
declare(strict_types=1);
require_once __DIR__ . '/../models/SangModel.php';
require_once __DIR__ . '/../models/PatientModel.php';
require_once __DIR__ . '/../models/JournalModel.php';

class SangController
{
    private static function delai(): int
    {
        return (int) Config::get('delai_don_jours', 56);
    }

    /** Stock de sang, alertes et compatibilités. */
    public static function stock(): void
    {
        View::render('sang/stock', [
            'stock'         => SangModel::stock(),
            'critiques'     => SangModel::stocksCritiques(),
            'derniersDons'  => SangModel::derniersDons(6),
            'enAttente'     => SangModel::demandesEnAttente(),
            'compatibilite' => SangModel::COMPATIBILITE,
        ], 'Banque de sang', 'sang');
    }

    public static function donneurs(): void
    {
        $groupe = get_param('groupe');
        $groupe = in_array($groupe, SangModel::GROUPES, true) ? $groupe : '';
        $recherche = get_param('search');
        View::render('sang/donneurs', [
            'donneurs'  => SangModel::donneurs($recherche, $groupe, self::delai()),
            'recherche' => $recherche,
            'groupe'    => $groupe,
            'delai'     => self::delai(),
        ], 'Donneurs', 'donneurs');
    }

    public static function donneurAjouter(): void
    {
        $nom = input('nom');
        $prenom = input('prenom');
        $groupe = input('groupe_sanguin');
        $tel = input('telephone');
        if ($nom === '' || $prenom === '' || mb_strlen($nom) > 60 || mb_strlen($prenom) > 60) {
            flash('error', 'Nom et prénom sont obligatoires (60 caractères maximum).');
        } elseif (!in_array($groupe, SangModel::GROUPES, true)) {
            flash('error', 'Groupe sanguin invalide.');
        } elseif ($tel !== '' && !preg_match('/^[0-9 +().-]{6,20}$/', $tel)) {
            flash('error', 'Numéro de téléphone invalide.');
        } else {
            $id = SangModel::ajouterDonneur($nom, $prenom, $groupe, $tel);
            JournalModel::log('donneur_ajout', "Donneur $prenom $nom ($groupe) enregistré (#$id)");
            flash('success', "Donneur $prenom $nom enregistré.");
        }
        redirect('donneurs');
    }

    public static function donneurSupprimer(): void
    {
        $id = (int) input('id');
        if ($id) {
            $nom = SangModel::nomDonneur($id);
            SangModel::supprimerDonneur($id);
            JournalModel::log('donneur_suppr', "Donneur $nom supprimé (#$id)");
            flash('success', 'Donneur supprimé.');
        }
        redirect('donneurs');
    }

    public static function donEnregistrer(): void
    {
        $id = (int) input('id');
        list($ok, $message) = SangModel::enregistrerDon($id, self::delai());
        if ($ok) {
            JournalModel::log('don_enregistre', 'Don de ' . SangModel::nomDonneur($id) . ' (+1 poche)');
        }
        flash($ok ? 'success' : 'error', $message);
        redirect('donneurs');
    }

    public static function demandes(): void
    {
        $statut = get_param('statut');
        $statut = in_array($statut, ['En attente', 'Satisfaite', 'Refusée'], true) ? $statut : '';
        View::render('sang/demandes', [
            'demandes' => SangModel::demandes($statut),
            'patients' => PatientModel::actifs(),
            'statut'   => $statut,
            'stock'    => SangModel::stock(),
        ], 'Demandes de sang', 'demandes');
    }

    public static function demandeAjouter(): void
    {
        $patientId = (int) input('patient_id');
        $groupe = input('groupe_sanguin');
        $poches = (int) input('poches', '1');
        $commentaire = input('commentaire');
        if (!$patientId || !PatientModel::find($patientId)) {
            flash('error', 'Sélectionnez un patient.');
        } elseif (!in_array($groupe, SangModel::GROUPES, true)) {
            flash('error', 'Groupe sanguin invalide.');
        } elseif ($poches < 1 || $poches > 10) {
            flash('error', 'Le nombre de poches doit être compris entre 1 et 10.');
        } elseif (mb_strlen($commentaire) > 200) {
            flash('error', 'Le commentaire est limité à 200 caractères.');
        } else {
            $id = SangModel::ajouterDemande($patientId, $groupe, $poches, $commentaire, (int) Auth::user()['id']);
            JournalModel::log('demande_creation', "Demande #$id : $poches poche(s) $groupe pour le patient #$patientId");
            flash('success', 'Demande de sang enregistrée.');
        }
        redirect('demandes');
    }

    public static function demandeTraiter(): void
    {
        $id = (int) input('id');
        $decision = input('decision') === 'refuser' ? 'refuser' : 'satisfaire';
        list($ok, $message) = SangModel::traiterDemande($id, $decision, (int) Auth::user()['id']);
        if ($ok) {
            JournalModel::log($decision === 'refuser' ? 'demande_refus' : 'demande_satisfaite', "Demande #$id : " . $message);
        }
        flash($ok ? 'success' : 'error', $message);
        redirect('demandes');
    }
}
