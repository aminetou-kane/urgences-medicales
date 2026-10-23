-- ============================================================
--  Urgences Médicales : structure de la base de données
--  À importer dans une base VIDE (phpMyAdmin > Importer).
--  ATTENTION : ce script supprime les tables existantes du projet.
-- ============================================================
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS journal, demande_sang, don, donneur, stock_sang, ressource, patient, utilisateur;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE utilisateur (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(60)  NOT NULL,
    prenom      VARCHAR(60)  NOT NULL,
    login       VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,                 -- mot de passe haché (password_hash)
    role        ENUM('medecin', 'infirmier', 'accueil') NOT NULL DEFAULT 'accueil',
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE patient (
    id                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom                 VARCHAR(60) NOT NULL,
    prenom              VARCHAR(60) NOT NULL,
    age                 TINYINT UNSIGNED NOT NULL,
    groupe_sanguin      ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NULL,
    degre_urgence       ENUM('Faible', 'Moyenne', 'Absolue') NOT NULL DEFAULT 'Faible',
    statut              ENUM('En attente', 'En prise en charge', 'Traité') NOT NULL DEFAULT 'En attente',
    created_at          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,   -- heure d'admission
    pris_en_charge_at   DATETIME NULL,                                 -- début de prise en charge
    traite_at           DATETIME NULL,                                 -- fin de prise en charge
    INDEX idx_patient_statut (statut),
    INDEX idx_patient_urgence (degre_urgence),
    INDEX idx_patient_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE ressource (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom         VARCHAR(80) NOT NULL,
    type        ENUM('Lit', 'Équipement', 'Salle', 'Personnel') NOT NULL,
    statut      ENUM('Disponible', 'Occupé', 'En maintenance') NOT NULL DEFAULT 'Disponible',
    patient_id  INT UNSIGNED NULL,
    CONSTRAINT fk_ressource_patient FOREIGN KEY (patient_id) REFERENCES patient(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Banque de sang ----------
CREATE TABLE stock_sang (
    groupe_sanguin  VARCHAR(3) NOT NULL PRIMARY KEY,
    poches          INT UNSIGNED NOT NULL DEFAULT 0,
    seuil_alerte    INT UNSIGNED NOT NULL DEFAULT 5       -- alerte si poches < seuil
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE donneur (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nom             VARCHAR(60) NOT NULL,
    prenom          VARCHAR(60) NOT NULL,
    groupe_sanguin  ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    telephone       VARCHAR(20) NULL,
    dernier_don     DATE NULL,
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_donneur_groupe (groupe_sanguin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE don (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    donneur_id  INT UNSIGNED NOT NULL,
    date_don    DATE NOT NULL,
    volume_ml   SMALLINT UNSIGNED NOT NULL DEFAULT 450,
    CONSTRAINT fk_don_donneur FOREIGN KEY (donneur_id) REFERENCES donneur(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE demande_sang (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    patient_id      INT UNSIGNED NOT NULL,
    groupe_sanguin  ENUM('A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-') NOT NULL,
    poches          TINYINT UNSIGNED NOT NULL DEFAULT 1,
    commentaire     VARCHAR(200) NULL,
    statut          ENUM('En attente', 'Satisfaite', 'Refusée') NOT NULL DEFAULT 'En attente',
    demandeur_id    INT UNSIGNED NULL,
    traite_par      INT UNSIGNED NULL,
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    traite_at       DATETIME NULL,
    INDEX idx_demande_statut (statut),
    CONSTRAINT fk_demande_patient FOREIGN KEY (patient_id) REFERENCES patient(id) ON DELETE CASCADE,
    CONSTRAINT fk_demande_demandeur FOREIGN KEY (demandeur_id) REFERENCES utilisateur(id) ON DELETE SET NULL,
    CONSTRAINT fk_demande_traite FOREIGN KEY (traite_par) REFERENCES utilisateur(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Traçabilité ----------
CREATE TABLE journal (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id  INT UNSIGNED NULL,
    action          VARCHAR(40) NOT NULL,
    details         VARCHAR(255) NOT NULL DEFAULT '',
    created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_journal_action (action),
    CONSTRAINT fk_journal_user FOREIGN KEY (utilisateur_id) REFERENCES utilisateur(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Stock initial : 8 groupes à zéro (seuil d'alerte plus bas pour les groupes AB, plus rares)
INSERT INTO stock_sang (groupe_sanguin, poches, seuil_alerte) VALUES
    ('O+', 0, 5), ('O-', 0, 5), ('A+', 0, 5), ('A-', 0, 5),
    ('B+', 0, 5), ('B-', 0, 5), ('AB+', 0, 3), ('AB-', 0, 3);
