-- ============================================================
-- Base de données : Gestion de jardins partagés
-- SGBD : PostgreSQL
-- ============================================================

-- Extensions
CREATE EXTENSION IF NOT EXISTS citext;

-- ============================================================
-- TABLES INDEPENDANTES
-- ============================================================

CREATE TABLE Utilisateur (
    id_utilisateur SERIAL PRIMARY KEY,
    nomU VARCHAR(100) NOT NULL,
    prenomU VARCHAR(100),
    roleU VARCHAR(50) NOT NULL CHECK (roleU IN ('Visiteur', 'Adhérent', 'Responsable', 'Tuteur', 'Trésorier', 'Administrateur')),
    mot_de_passe VARCHAR(255) NOT NULL CHECK (LENGTH(mot_de_passe) >= 8),
    date_inscription DATE NOT NULL DEFAULT CURRENT_DATE,
    email CITEXT NOT NULL UNIQUE
);

CREATE TABLE Parcelle (
    id_parcelle SERIAL PRIMARY KEY,
    surfaceP NUMERIC(10,2) NOT NULL CHECK (surfaceP > 0),
    secteurP VARCHAR(100) NOT NULL,
    numeroP INT NOT NULL,
    UNIQUE (secteurP, numeroP)
);

CREATE TABLE Plante (
    id_plante SERIAL PRIMARY KEY,
    nom_variete VARCHAR(100) NOT NULL,
    contenu_fiche TEXT,
    num_mois_semis_debut INT NOT NULL CHECK (num_mois_semis_debut BETWEEN 1 AND 12),
    num_mois_semis_fin INT NOT NULL CHECK (num_mois_semis_fin BETWEEN 1 AND 12),
    date_creation_fiche DATE NOT NULL DEFAULT CURRENT_DATE,
    num_mois_recol_deb INT NOT NULL CHECK (num_mois_recol_deb BETWEEN 1 AND 12),
    num_mois_recol_fin INT NOT NULL CHECK (num_mois_recol_fin BETWEEN 1 AND 12),
    CONSTRAINT chk_mois_semis CHECK (num_mois_semis_fin >= num_mois_semis_debut),
    CONSTRAINT chk_mois_recol CHECK (num_mois_recol_fin >= num_mois_recol_deb)
);

CREATE TABLE Outil (
    id_outil SERIAL PRIMARY KEY,
    nomO VARCHAR(100) NOT NULL,
    descriptionO TEXT,
    disponibiliteO BOOLEAN NOT NULL DEFAULT TRUE,
    etat_physique VARCHAR(50) NOT NULL CHECK (etat_physique IN ('Opérationnel', 'Abîmé', 'HS'))
);

CREATE TABLE Meteo (
    id_meteo SERIAL PRIMARY KEY,
    jour DATE NOT NULL DEFAULT CURRENT_DATE,
    precM NUMERIC(5,2) NOT NULL CHECK (precM >= 0),
    tempM NUMERIC(5,2) NOT NULL CHECK (tempM BETWEEN -20 AND 50),
    descM TEXT
);

-- ============================================================
-- TABLES DEPENDANTES (1er niveau)
-- ============================================================

CREATE TABLE Semence (
    id_semence SERIAL PRIMARY KEY,
    nomS VARCHAR(100) NOT NULL,
    stock_mis_a_jour INT NOT NULL,
    id_plante_correspondre INT REFERENCES Plante(id_plante),
    id_utilisateur_gerer_resp INT REFERENCES Utilisateur(id_utilisateur)
);

CREATE TABLE ConseilCultural (
    id_conseil SERIAL PRIMARY KEY,
    titreC VARCHAR(255) NOT NULL,
    contenuC TEXT NOT NULL,
    date_publication DATE NOT NULL DEFAULT CURRENT_DATE,
    id_utilisateur_redigtuteur INT NOT NULL REFERENCES Utilisateur(id_utilisateur),
    CONSTRAINT chk_conseil_date_notnull CHECK (date_publication IS NOT NULL)
);

CREATE TABLE Contribution (
    id_contribution SERIAL PRIMARY KEY,
    date_contribution DATE NOT NULL DEFAULT CURRENT_DATE,
    statutC VARCHAR(50) NOT NULL CHECK (statutC IN ('en attente', 'validée', 'refusée')),
    type_apport VARCHAR(100) NOT NULL,
    description TEXT,
    id_utilisateur_apporter INT NOT NULL REFERENCES Utilisateur(id_utilisateur)
);

CREATE TABLE Attribution (
    id_attribution SERIAL PRIMARY KEY,
    date_attribution DATE NOT NULL DEFAULT CURRENT_DATE,
    date_fin DATE CHECK (date_fin > date_attribution),
    id_parcelle_assigner INT NOT NULL REFERENCES Parcelle(id_parcelle),
    id_utilisateur_fournir INT NOT NULL REFERENCES Utilisateur(id_utilisateur)
);

CREATE TABLE Reservation (
    id_reservation SERIAL PRIMARY KEY,
    dateD DATE NOT NULL,
    dateF DATE NOT NULL,
    statutR VARCHAR(50) NOT NULL CHECK (statutR IN ('en attente', 'confirmée', 'en cours', 'annulée', 'terminée')),
    id_outil_concerner INT NOT NULL REFERENCES Outil(id_outil),
    id_utilisateur_effectuer INT NOT NULL REFERENCES Utilisateur(id_utilisateur),
    CONSTRAINT chk_reservation_dates CHECK (dateF >= dateD)
);

CREATE TABLE Notification (
    id_notification SERIAL PRIMARY KEY,
    titreN VARCHAR(255) NOT NULL,
    contenuN TEXT NOT NULL,
    date_envoi DATE NOT NULL DEFAULT CURRENT_DATE,
    typeN VARCHAR(50) NOT NULL CHECK (typeN IN ('Alerte', 'Rappel', 'Action', 'Stock', 'Conseil', 'Don')),
    est_lue BOOLEAN NOT NULL DEFAULT FALSE,
    id_utilisateur_recoit INT NOT NULL REFERENCES Utilisateur(id_utilisateur)
);

-- ============================================================
-- TABLES DEPENDANTES (2ème niveau)
-- ============================================================

CREATE TABLE Culture (
    id_culture SERIAL PRIMARY KEY,
    date_semis DATE NOT NULL,
    statut_Culture VARCHAR(50) NOT NULL CHECK (statut_Culture IN ('en cours', 'terminée', 'abandonnée')),
    date_recolte_prevue DATE CHECK (date_recolte_prevue > date_semis),
    id_attribution_seffectuer INT NOT NULL REFERENCES Attribution(id_attribution),
    id_plante_definir INT NOT NULL REFERENCES Plante(id_plante)
);

-- ============================================================
-- TABLES DEPENDANTES (3ème niveau)
-- ============================================================

CREATE TABLE Recolte (
    id_recolte SERIAL PRIMARY KEY,
    date_recolte DATE NOT NULL,
    quantiteR NUMERIC(10,2) NOT NULL CHECK (quantiteR > 0),
    uniteR VARCHAR(20) NOT NULL CHECK (uniteR IN ('kg', 'g', 'unité', 'litre')),
    qualite_avis VARCHAR(50) CHECK (qualite_avis IN ('excellente', 'bonne', 'moyenne', 'mauvaise')),
    id_culture_produire INT NOT NULL REFERENCES Culture(id_culture)
);

CREATE TABLE AlerteSanitaire (
    id_alerte SERIAL PRIMARY KEY,
    descriptionALR TEXT NOT NULL,
    date_detection DATE NOT NULL DEFAULT CURRENT_DATE,
    est_resolue BOOLEAN NOT NULL DEFAULT FALSE,
    nom_menace VARCHAR(100) NOT NULL,
    niveau_gravite VARCHAR(50) NOT NULL CHECK (niveau_gravite IN ('faible', 'modéré', 'élevé', 'critique')),
    traitement_applique TEXT,
    id_culture_est_signalee_sur INT NOT NULL REFERENCES Culture(id_culture)
);

-- ============================================================
-- TABLES D'ASSOCIATION (relations n-n)
-- ============================================================

CREATE TABLE sappliquer (
    id_outil INT NOT NULL REFERENCES Outil(id_outil),
    id_contribution INT NOT NULL REFERENCES Contribution(id_contribution),
    PRIMARY KEY (id_outil, id_contribution)
);

CREATE TABLE observer (
    id_utilisateur INT NOT NULL REFERENCES Utilisateur(id_utilisateur),
    id_parcelle INT NOT NULL REFERENCES Parcelle(id_parcelle),
    date_observation DATE NOT NULL CHECK (date_observation <= CURRENT_DATE),
    espece VARCHAR(100) NOT NULL,
    PRIMARY KEY (id_utilisateur, id_parcelle, date_observation)
);

CREATE TABLE s_inscrire (
    id_utilisateur INT NOT NULL REFERENCES Utilisateur(id_utilisateur),
    id_parcelle INT NOT NULL REFERENCES Parcelle(id_parcelle),
    date_demande DATE NOT NULL,
    priorite INT NOT NULL CHECK (priorite >= 1),
    motivation TEXT,
    PRIMARY KEY (id_utilisateur, id_parcelle)
);

CREATE TABLE est_voisine_de (
    id_parcelle INT NOT NULL REFERENCES Parcelle(id_parcelle),
    id_parcelle_voisine INT NOT NULL REFERENCES Parcelle(id_parcelle),
    PRIMARY KEY (id_parcelle, id_parcelle_voisine),
    CONSTRAINT chk_pas_autoref CHECK (id_parcelle <> id_parcelle_voisine)
);

CREATE TABLE saccorder (
    id_plante_E1 INT NOT NULL REFERENCES Plante(id_plante),
    id_plante_E2 INT NOT NULL REFERENCES Plante(id_plante),
    type_accord VARCHAR(50) NOT NULL CHECK (type_accord IN ('Association bénéfique', 'Association néfaste')),
    PRIMARY KEY (id_plante_E1, id_plante_E2),
    CONSTRAINT chk_pas_autoref CHECK (id_plante_E1 <> id_plante_E2)
);

CREATE TABLE justifier (
    id_meteo INT NOT NULL REFERENCES Meteo(id_meteo),
    id_conseil INT NOT NULL REFERENCES ConseilCultural(id_conseil),
    PRIMARY KEY (id_meteo, id_conseil)
);

CREATE TABLE porter_sur (
    id_contribution INT NOT NULL REFERENCES Contribution(id_contribution),
    id_semence INT NOT NULL REFERENCES Semence(id_semence),
    quantite_apportee INT NOT NULL CHECK (quantite_apportee > 0),
    PRIMARY KEY (id_contribution, id_semence)
);

CREATE TABLE modifier_tuteur (
    id_utilisateur INT NOT NULL REFERENCES Utilisateur(id_utilisateur),
    id_plante INT NOT NULL REFERENCES Plante(id_plante),
    date_modification DATE NOT NULL CHECK (date_modification <= CURRENT_DATE),
    PRIMARY KEY (id_utilisateur, id_plante, date_modification)
);

-- ============================================================
-- CONTRAINTE : max 2 attributions actives par utilisateur
-- ============================================================

CREATE OR REPLACE FUNCTION check_max_attributions_actives()
RETURNS TRIGGER AS $$
BEGIN
    IF (
        SELECT COUNT(*) FROM Attribution
        WHERE id_utilisateur_fournir = NEW.id_utilisateur_fournir
        AND date_fin IS NULL
    ) >= 2 THEN
        RAISE EXCEPTION 'Un utilisateur ne peut pas avoir plus de 2 attributions actives simultanément.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_max_attributions_actives
BEFORE INSERT ON Attribution
FOR EACH ROW EXECUTE FUNCTION check_max_attributions_actives();

-- ============================================================
-- CONTRAINTE : date_publication >= date_inscription de l'auteur
-- ============================================================

CREATE OR REPLACE FUNCTION check_conseil_date_publication()
RETURNS TRIGGER AS $$
DECLARE
    v_date_inscription DATE;
BEGIN
    SELECT date_inscription INTO v_date_inscription
    FROM Utilisateur WHERE id_utilisateur = NEW.id_utilisateur_redigtuteur;

    IF NEW.date_publication < v_date_inscription THEN
        RAISE EXCEPTION 'La date de publication doit être >= à la date d inscription de l auteur.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_conseil_date_publication
BEFORE INSERT OR UPDATE ON ConseilCultural
FOR EACH ROW EXECUTE FUNCTION check_conseil_date_publication();

-- ============================================================
-- CONTRAINTE : date_semis >= date_attribution de l'attribution liée
-- ============================================================

CREATE OR REPLACE FUNCTION check_culture_date_semis()
RETURNS TRIGGER AS $$
DECLARE
    v_date_attr DATE;
BEGIN
    SELECT date_attribution INTO v_date_attr
    FROM Attribution WHERE id_attribution = NEW.id_attribution_seffectuer;

    IF NEW.date_semis < v_date_attr THEN
        RAISE EXCEPTION 'La date de semis doit être >= à la date d attribution.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_culture_date_semis
BEFORE INSERT OR UPDATE ON Culture
FOR EACH ROW EXECUTE FUNCTION check_culture_date_semis();

-- ============================================================
-- CONTRAINTE : date_recolte >= date_semis de la culture liée
-- ============================================================

CREATE OR REPLACE FUNCTION check_recolte_date()
RETURNS TRIGGER AS $$
DECLARE
    v_date_semis DATE;
BEGIN
    SELECT date_semis INTO v_date_semis
    FROM Culture WHERE id_culture = NEW.id_culture_produire;

    IF NEW.date_recolte < v_date_semis THEN
        RAISE EXCEPTION 'La date de récolte doit être >= à la date de semis.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_recolte_date
BEFORE INSERT OR UPDATE ON Recolte
FOR EACH ROW EXECUTE FUNCTION check_recolte_date();

-- ============================================================
-- CONTRAINTE : date_detection >= date_semis de la culture liée
-- ============================================================

CREATE OR REPLACE FUNCTION check_alerte_date()
RETURNS TRIGGER AS $$
DECLARE
    v_date_semis DATE;
BEGIN
    SELECT date_semis INTO v_date_semis
    FROM Culture WHERE id_culture = NEW.id_culture_est_signalee_sur;

    IF NEW.date_detection < v_date_semis THEN
        RAISE EXCEPTION 'La date de détection doit être >= à la date de semis.';
    END IF;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TRIGGER trg_alerte_date
BEFORE INSERT OR UPDATE ON AlerteSanitaire
FOR EACH ROW EXECUTE FUNCTION check_alerte_date();
