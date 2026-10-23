-- ============================================================
--  Données de DÉMONSTRATION (fictives) : à importer après schema.sql.
--  Les heures sont calculées par rapport à NOW() : le tableau de bord
--  reste toujours « vivant ». Mot de passe des 3 comptes : pass123
-- ============================================================
SET NAMES utf8mb4;

INSERT INTO utilisateur (id, nom, prenom, login, password, role) VALUES
    (1, 'Diop', 'Moussa', 'moussa.diop', '$2y$10$JR1pZ0jXODQ7jTtGpljFoONk2jcnYcvYF85h5bfEghrmMlodeqI2a', 'medecin'),
    (2, 'Fall', 'Awa',    'awa.fall',    '$2y$10$JR1pZ0jXODQ7jTtGpljFoONk2jcnYcvYF85h5bfEghrmMlodeqI2a', 'infirmier'),
    (3, 'Sène', 'Oumar',  'oumar.sene',  '$2y$10$JR1pZ0jXODQ7jTtGpljFoONk2jcnYcvYF85h5bfEghrmMlodeqI2a', 'accueil');

-- Patients (id, nom, prénom, âge, groupe, urgence, statut, admission, début prise en charge, fin)
INSERT INTO patient (id, nom, prenom, age, groupe_sanguin, degre_urgence, statut, created_at, pris_en_charge_at, traite_at) VALUES
    (1,  'Ndiaye',   'Fatou',      34, 'O+',  'Absolue', 'En prise en charge', NOW() - INTERVAL 55 MINUTE, NOW() - INTERVAL 50 MINUTE, NULL),
    (2,  'Sow',      'Cheikh',     58, 'A+',  'Absolue', 'En prise en charge', NOW() - INTERVAL 40 MINUTE, NOW() - INTERVAL 31 MINUTE, NULL),
    (3,  'Ba',       'Aminata',    27, 'B+',  'Moyenne', 'En prise en charge', NOW() - INTERVAL 75 MINUTE, NOW() - INTERVAL 55 MINUTE, NULL),
    (4,  'Gueye',    'Ibrahima',    7, 'O-',  'Moyenne', 'En prise en charge', NOW() - INTERVAL 30 MINUTE, NOW() - INTERVAL 22 MINUTE, NULL),
    (5,  'Sy',       'Mariama',    45, 'AB+', 'Absolue', 'En attente',         NOW() - INTERVAL 8 MINUTE,  NULL, NULL),
    (6,  'Diallo',   'Abdoulaye',  63, 'A-',  'Moyenne', 'En attente',         NOW() - INTERVAL 35 MINUTE, NULL, NULL),
    (7,  'Faye',     'Khady',      19, 'O+',  'Faible',  'En attente',         NOW() - INTERVAL 70 MINUTE, NULL, NULL),
    (8,  'Mbaye',    'Pape',       41, 'B-',  'Moyenne', 'En attente',         NOW() - INTERVAL 20 MINUTE, NULL, NULL),
    (9,  'Thiam',    'Ndeye',      29, NULL,  'Faible',  'En attente',         NOW() - INTERVAL 95 MINUTE, NULL, NULL),
    (10, 'Seck',     'Ousmane',    52, 'O+',  'Moyenne', 'Traité', NOW() - INTERVAL 26 HOUR, NOW() - INTERVAL 25 HOUR - INTERVAL 35 MINUTE, NOW() - INTERVAL 24 HOUR),
    (11, 'Cissé',    'Awa',        31, 'A+',  'Faible',  'Traité', NOW() - INTERVAL 30 HOUR, NOW() - INTERVAL 29 HOUR - INTERVAL 10 MINUTE, NOW() - INTERVAL 28 HOUR),
    (12, 'Dème',     'Modou',      72, 'B+',  'Absolue', 'Traité', NOW() - INTERVAL 2 DAY, NOW() - INTERVAL 2 DAY + INTERVAL 6 MINUTE, NOW() - INTERVAL 44 HOUR),
    (13, 'Fall',     'Rokhaya',    24, 'O+',  'Faible',  'Traité', NOW() - INTERVAL 50 HOUR, NOW() - INTERVAL 49 HOUR, NOW() - INTERVAL 48 HOUR),
    (14, 'Sarr',     'Ibra',       38, 'AB-', 'Moyenne', 'Traité', NOW() - INTERVAL 3 DAY, NOW() - INTERVAL 3 DAY + INTERVAL 22 MINUTE, NOW() - INTERVAL 68 HOUR),
    (15, 'Ndao',     'Binta',      66, 'A+',  'Absolue', 'Traité', NOW() - INTERVAL 74 HOUR, NOW() - INTERVAL 74 HOUR + INTERVAL 4 MINUTE, NOW() - INTERVAL 70 HOUR),
    (16, 'Camara',   'Souleymane', 15, 'O+',  'Faible',  'Traité', NOW() - INTERVAL 4 DAY, NOW() - INTERVAL 4 DAY + INTERVAL 40 MINUTE, NOW() - INTERVAL 94 HOUR),
    (17, 'Diouf',    'Astou',      49, 'B+',  'Moyenne', 'Traité', NOW() - INTERVAL 5 DAY, NOW() - INTERVAL 5 DAY + INTERVAL 18 MINUTE, NOW() - INTERVAL 116 HOUR),
    (18, 'Badji',    'Lamine',     33, 'O-',  'Absolue', 'Traité', NOW() - INTERVAL 122 HOUR, NOW() - INTERVAL 122 HOUR + INTERVAL 3 MINUTE, NOW() - INTERVAL 118 HOUR),
    (19, 'Mendy',    'Marie',      28, 'A+',  'Faible',  'Traité', NOW() - INTERVAL 6 DAY, NOW() - INTERVAL 6 DAY + INTERVAL 55 MINUTE, NOW() - INTERVAL 140 HOUR),
    (20, 'Sané',     'Pierre',     55, 'O+',  'Moyenne', 'Traité', NOW() - INTERVAL 145 HOUR, NOW() - INTERVAL 145 HOUR + INTERVAL 25 MINUTE, NOW() - INTERVAL 142 HOUR),
    (21, 'Lô',       'Mamadou',    36, 'B+',  'Moyenne', 'Traité', NOW() - INTERVAL 4 HOUR, NOW() - INTERVAL 3 HOUR - INTERVAL 45 MINUTE, NOW() - INTERVAL 2 HOUR);

-- Ressources (celles occupées sont assignées à des patients en prise en charge)
INSERT INTO ressource (id, nom, type, statut, patient_id) VALUES
    (1,  'Lit 01',                  'Lit',        'Occupé',         1),
    (2,  'Lit 02',                  'Lit',        'Occupé',         3),
    (3,  'Lit 03',                  'Lit',        'Disponible',     NULL),
    (4,  'Lit 04',                  'Lit',        'Disponible',     NULL),
    (5,  'Lit 05',                  'Lit',        'En maintenance', NULL),
    (6,  'Lit 06',                  'Lit',        'Disponible',     NULL),
    (7,  'Respirateur A',           'Équipement', 'Occupé',         2),
    (8,  'Respirateur B',           'Équipement', 'Disponible',     NULL),
    (9,  'Défibrillateur DEA-1',    'Équipement', 'Disponible',     NULL),
    (10, 'Électrocardiographe ECG-1','Équipement','Occupé',         4),
    (11, 'Salle de déchocage',      'Salle',      'Occupé',         2),
    (12, 'Salle de soins 1',        'Salle',      'Disponible',     NULL),
    (13, 'Salle de plâtre',         'Salle',      'Disponible',     NULL),
    (14, 'Équipe SMUR 1',           'Personnel',  'Disponible',     NULL);

-- Banque de sang
UPDATE stock_sang SET poches = 14 WHERE groupe_sanguin = 'O+';
UPDATE stock_sang SET poches = 6  WHERE groupe_sanguin = 'O-';
UPDATE stock_sang SET poches = 11 WHERE groupe_sanguin = 'A+';
UPDATE stock_sang SET poches = 2  WHERE groupe_sanguin = 'A-';
UPDATE stock_sang SET poches = 8  WHERE groupe_sanguin = 'B+';
UPDATE stock_sang SET poches = 4  WHERE groupe_sanguin = 'B-';
UPDATE stock_sang SET poches = 5  WHERE groupe_sanguin = 'AB+';
UPDATE stock_sang SET poches = 1  WHERE groupe_sanguin = 'AB-';

INSERT INTO donneur (id, nom, prenom, groupe_sanguin, telephone, dernier_don) VALUES
    (1,  'Sall',    'Moustapha', 'O+',  '77 100 20 01', CURDATE() - INTERVAL 20 DAY),
    (2,  'Diagne',  'Aïssatou',  'A+',  '78 200 30 02', CURDATE() - INTERVAL 90 DAY),
    (3,  'Ndoye',   'Pape Samba','B+',  '76 300 40 03', CURDATE() - INTERVAL 130 DAY),
    (4,  'Thioune', 'Coumba',    'O-',  '70 400 50 04', CURDATE() - INTERVAL 70 DAY),
    (5,  'Bâ',      'Mamadou',   'AB+', '77 500 60 05', NULL),
    (6,  'Kébé',    'Fatima',    'A-',  '78 600 70 06', CURDATE() - INTERVAL 40 DAY),
    (7,  'Lo',      'Serigne',   'O+',  '76 700 80 07', CURDATE() - INTERVAL 200 DAY),
    (8,  'Sène',    'Adja',      'B-',  '70 800 90 08', CURDATE() - INTERVAL 15 DAY),
    (9,  'Fofana',  'Moussa',    'O+',  '77 900 10 09', CURDATE() - INTERVAL 100 DAY),
    (10, 'Wade',    'Ndèye',     'A+',  '78 110 22 10', NULL);

INSERT INTO don (donneur_id, date_don, volume_ml) VALUES
    (1, CURDATE() - INTERVAL 20 DAY, 450),  (2, CURDATE() - INTERVAL 90 DAY, 450),
    (3, CURDATE() - INTERVAL 130 DAY, 450), (4, CURDATE() - INTERVAL 70 DAY, 450),
    (6, CURDATE() - INTERVAL 40 DAY, 450),  (7, CURDATE() - INTERVAL 200 DAY, 450),
    (8, CURDATE() - INTERVAL 15 DAY, 450),  (9, CURDATE() - INTERVAL 100 DAY, 450),
    (1, CURDATE() - INTERVAL 160 DAY, 450), (4, CURDATE() - INTERVAL 160 DAY, 450);

INSERT INTO demande_sang (patient_id, groupe_sanguin, poches, commentaire, statut, demandeur_id, traite_par, created_at, traite_at) VALUES
    (1,  'O+', 2, 'Hémorragie, préparation du bloc',      'En attente', 2, NULL, NOW() - INTERVAL 25 MINUTE, NULL),
    (2,  'A+', 3, 'Transfusion avant intervention',       'En attente', 2, NULL, NOW() - INTERVAL 12 MINUTE, NULL),
    (6,  'A-', 4, 'Anémie sévère',                        'En attente', 2, NULL, NOW() - INTERVAL 5 MINUTE,  NULL),
    (10, 'O+', 1, NULL,                                    'Satisfaite', 2, 1,    NOW() - INTERVAL 25 HOUR, NOW() - INTERVAL 24 HOUR - INTERVAL 40 MINUTE),
    (12, 'B+', 2, 'Stock insuffisant à ce moment',         'Refusée',    2, 1,    NOW() - INTERVAL 46 HOUR, NOW() - INTERVAL 45 HOUR);

INSERT INTO journal (utilisateur_id, action, details, created_at) VALUES
    (3, 'connexion',        'Connexion de oumar.sene',                                   NOW() - INTERVAL 90 MINUTE),
    (3, 'patient_ajout',    'Admission de Fatou Ndiaye (#1), urgence Absolue',           NOW() - INTERVAL 55 MINUTE),
    (2, 'patient_modif',    'Mise à jour de Fatou Ndiaye (#1) : En prise en charge, urgence Absolue', NOW() - INTERVAL 50 MINUTE),
    (2, 'demande_creation', 'Demande de sang : 2 poche(s) O+ pour le patient #1',        NOW() - INTERVAL 25 MINUTE),
    (1, 'don_enregistre',   'Don de Moustapha Sall (O+) : +1 poche',                     NOW() - INTERVAL 20 DAY);
