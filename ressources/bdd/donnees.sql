-- data.sql
/**
 * Jeu de données pour le projet La Bòstia Verda
 * Référence temporelle : mai 2026
 * Ce script réinitialise les tables et insère un jeu de données complet pour les tests.
 */

/**
 * Vider les tables avant réinsertion.
 * L'utilisation de CASCADE permet de contourner les contraintes de clés étrangères lors de la suppression.
 * RESTART IDENTITY réinitialise les compteurs (IDs) à 1 pour toutes les tables.
 */
TRUNCATE TABLE
    Utilisateur, Parcelle, Plante, Outil, Meteo, Semence, ConseilCultural,
    Contribution, Attribution, Reservation, Notification, Culture, Recolte,
    AlerteSanitaire, sappliquer, observer, s_inscrire, est_voisine_de,
    saccorder, justifier, porter_sur, modifier_tuteur
RESTART IDENTITY CASCADE;

/**
 * ============================================================
 * 1. UTILISATEURS
 * ============================================================
 * Insère 14 comptes répartis sur tous les rôles de l'application.
 * Le mot de passe haché pour TOUS les comptes de test correspond à : 1234
 */
INSERT INTO Utilisateur (nomU, prenomU, roleU, mot_de_passe, date_inscription, email) VALUES
-- Visiteurs (en liste d'attente)
('ALI', 'Abasse', 'Visiteur', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2026-04-10', 'visiteur1@gmail.com'),
('BOUTAHIR', 'Ayyub', 'Visiteur', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2026-04-18', 'visiteur2@gmail.com'),
('FOSSECAVE', 'Louna', 'Visiteur', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2026-05-02', 'visiteur3@gmail.com'),
-- Adhérents (avec parcelles attribuées)
('LALUE', 'Valentin', 'Adhérent', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2026-01-10', 'adherent1@gmail.com'),
('MENOUN', 'Thanina', 'Adhérent', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2026-01-15', 'adherent2@gmail.com'),
('RIGAL', 'Robin', 'Adhérent', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2026-01-20', 'adherent3@gmail.com'),
('ETU', 'Adhérent', 'Adhérent', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2026-02-08', 'adherent4@gmail.com'),
('ETU', 'Adhérent', 'Adhérent', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2026-02-22', 'adherent5@gmail.com'),
-- Tuteurs
('ETU', 'Tuteur', 'Tuteur', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2025-12-05', 'tuteur1@gmail.com'),
('ETU', 'Tuteur', 'Tuteur', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2025-12-15', 'tuteur2@gmail.com'),
-- Trésorier
('ETU', 'Trésorier', 'Trésorier', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2025-12-10', 'tresorier@gmail.com'),
-- Responsable du terrain
('ETU', 'Responsable', 'Responsable', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2025-12-01', 'responsable@gmail.com'),
-- Administrateurs
('ETU', 'Administrateur', 'Administrateur', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2025-11-01', 'administrateur1@gmail.com'),
('ETU', 'Administrateur', 'Administrateur', '$2y$10$xzAHJ/f4zNiSg9b1gwmPT.J4wU2dK/OYYm1Pxh7oqBgYgaG0SCrpm', '2025-11-15', 'administrateur2@gmail.com');

/**
 * ============================================================
 * 2. PARCELLES
 * ============================================================
 * Crée 8 parcelles physiques réparties sur 3 secteurs (Nord, Sud, Est).
 */
INSERT INTO Parcelle (surfaceP, secteurP, numeroP) VALUES
(15.5, 'Nord', 101), -- id 1
(12.0, 'Nord', 102), -- id 2
(14.0, 'Nord', 103), -- id 3
(20.0, 'Sud', 201),  -- id 4
(18.5, 'Sud', 202),  -- id 5
(22.0, 'Sud', 203),  -- id 6
(25.0, 'Est', 301),  -- id 7
(16.0, 'Est', 302);  -- id 8

/**
 * ============================================================
 * 3. PLANTES
 * ============================================================
 * Catalogue de 10 variétés de plantes avec leurs périodes de semis et de récolte.
 */
INSERT INTO Plante (nom_variete, contenu_fiche, num_mois_semis_debut, num_mois_semis_fin, date_creation_fiche, num_mois_recol_deb, num_mois_recol_fin) VALUES
('Tomate Marmande', 'Variété ancienne à gros fruits charnus. Exige beaucoup de soleil et un tuteurage rapide.', 3, 5, '2025-12-01', 7, 9),
('Tomate Cerise', 'Petits fruits sucrés idéaux pour apéritif. Production prolongée jusqu''aux gelées.', 3, 5, '2025-12-01', 7, 10),
('Carotte Nantaise', 'Sol meuble et sableux indispensable. Éviter les sols pierreux pour racines bien droites.', 3, 6, '2025-12-01', 7, 10),
('Basilic Grand Vert', 'Craint le gel. Excellente association avec la tomate. Arrosage régulier au pied.', 4, 6, '2025-12-15', 6, 9),
('Radis de 18 jours', 'Croissance très rapide (3 semaines). Arrosage régulier pour éviter le piquant.', 3, 9, '2025-12-20', 4, 10),
('Courgette Verte', 'Très productive : 1 à 2 pieds suffisent pour une famille. Récolter jeune.', 4, 6, '2026-01-10', 7, 10),
('Salade Batavia', 'Variété rustique adaptée toute saison. Semis échelonnés tous les 15 jours.', 3, 9, '2026-01-15', 5, 10),
('Aubergine Violette', 'Chaleur indispensable. Tuteurer et pincer la tête après la 5ème fleur.', 4, 5, '2026-02-01', 7, 10),
('Poivron Doux', 'Très bonne association avec basilic et persil. Sensible aux excès d''eau.', 4, 5, '2026-02-01', 8, 10),
('Haricot Vert', 'Semis directs en pleine terre. Récolter tous les 2 jours pour stimuler la production.', 5, 7, '2026-02-15', 7, 10);

/**
 * ============================================================
 * 4. OUTILS
 * ============================================================
 * Inventaire matériel du jardin avec leur état actuel et disponibilité.
 */
INSERT INTO Outil (nomO, descriptionO, disponibiliteO, etat_physique) VALUES
('Bêche', 'Pelle avec manche en bois renforcé', TRUE, 'Opérationnel'),
('Râteau', 'Râteau à 14 dents en acier trempé', TRUE, 'Opérationnel'),
('Arrosoir', 'Arrosoir 10L en plastique vert', TRUE, 'Opérationnel'),
('Brouette', 'Brouette 90L roue gonflable', FALSE, 'Abîmé'),
('Pelle', 'Pelle ronde manche bois', TRUE, 'Opérationnel'),
('Sécateur', 'Sécateur professionnel lames forgées', TRUE, 'Opérationnel'),
('Cisaille', 'Cisaille à haies manche télescopique', FALSE, 'Abîmé'),
('Tuyau d''arrosage', 'Tuyau 25m avec enrouleur mural', FALSE, 'HS');

/**
 * ============================================================
 * 5. MÉTÉO
 * ============================================================
 * Historique des relevés météorologiques sur les 6 dernières semaines.
 */
INSERT INTO Meteo (jour, precM, tempM, descM) VALUES
('2026-04-01', 3.0, 16.0, 'Ciel variable, averses passagères'),
('2026-04-08', 0.0, 19.5, 'Beau temps printanier, vent faible'),
('2026-04-15', 15.0, 14.0, 'Forte pluie, vigilance orage'),
('2026-04-22', 1.5, 18.0, 'Éclaircies, températures saisonnières'),
('2026-04-29', 0.0, 22.0, 'Grand soleil, premières chaleurs'),
('2026-05-03', 8.0, 17.5, 'Pluie modérée bénéfique pour les semis'),
('2026-05-07', 0.0, 24.5, 'Soleil et chaleur, vigilance arrosage'),
('2026-05-10', 4.0, 20.0, 'Couvert, averses éparses en fin de journée'),
('2026-05-12', 0.0, 23.0, 'Beau temps, vent du sud'),
('2026-05-13', 0.0, 25.0, 'Très ensoleillé, attention au stress hydrique');

/**
 * ============================================================
 * 6. SEMENCES
 * ============================================================
 * Stocks de la grainothèque, liés aux plantes et gérés par le Trésorier (id_utilisateur = 11).
 */
INSERT INTO Semence (nomS, stock_mis_a_jour, id_plante_correspondre, id_utilisateur_gerer_resp) VALUES
('Lot Tomates Marmande 2026', 120, 1, 11),
('Lot Tomates Cerises 2026', 80, 2, 11),
('Lot Carottes Nantaises 2026', 10, 3, 11), -- Stock CRITIQUE
('Lot Basilic Grand Vert', 45, 4, 11),
('Lot Radis 18j vrac', 300, 5, 11),
('Lot Courgettes vertes', 25, 6, 11),
('Lot Salade Batavia', 150, 7, 11),
('Lot Aubergines violettes', 8, 8, 11), -- Stock CRITIQUE
('Lot Poivrons doux', 35, 9, 11),
('Lot Haricots verts', 200, 10, 11);

/**
 * ============================================================
 * 7. CONSEILS CULTURAUX
 * ============================================================
 * Publications rédigées par les Tuteurs (id 9 et 10) pour aider les adhérents.
 */
INSERT INTO ConseilCultural (titreC, contenuC, date_publication, id_utilisateur_redigtuteur) VALUES
('Taille des gourmands sur tomates', 'Pensez à pincer régulièrement les gourmands pour favoriser la production de fruits plutôt que de feuilles.', '2026-04-12', 9),
('Protéger les jeunes plants des gelées', 'En avril, les gelées tardives peuvent encore frapper. Couvrir basilics et tomates avec un voile de forçage la nuit.', '2026-04-16', 9),
('Rotation des cultures', 'Évitez de planter au même endroit deux ans de suite la même famille. Alternez légumineuses, racines et fruits.', '2026-04-25', 10),
('Association tomate-basilic', 'Plantez du basilic au pied de vos tomates : il repousse les pucerons et améliore le goût des fruits.', '2026-05-02', 9),
('Arrosage par fortes chaleurs', 'Avec les températures qui dépassent 24°C, privilégiez l''arrosage le matin tôt ou le soir tard pour limiter l''évaporation.', '2026-05-08', 10),
('Paillage : économiser l''eau', 'Un paillage de 5 cm autour des plants peut réduire l''arrosage de 40%. Utilisez paille, BRF ou tonte séchée.', '2026-05-11', 10);

/**
 * ============================================================
 * 8. CONTRIBUTIONS
 * ============================================================
 * Historique des dons matériels ou de graines faits par les utilisateurs.
 */
INSERT INTO Contribution (date_contribution, statutC, type_apport, description, id_utilisateur_apporter) VALUES
('2026-03-01', 'validée', 'Matériel', 'Don d''une petite pelle de jardin neuve', 4),
('2026-03-15', 'validée', 'Graines', 'Graines de courges butternut de l''an dernier (50g)', 5),
('2026-04-05', 'en attente', 'Graines', 'Sachet de graines de tomates anciennes (40g)', 6),
('2026-04-20', 'validée', 'Mixte', 'Lot de petits outils + graines de fleurs', 7),
('2026-05-04', 'en attente', 'Graines', 'Graines de radis et carottes récupérées (80g)', 1),
('2026-05-09', 'en attente', 'Matériel', 'Don d''un arrosoir 5L et d''un transplantoir', 2),
('2026-05-11', 'validée', 'Matériel', 'Bidon de bouillie bordelaise 2L scellé', 8);

/**
 * ============================================================
 * 9. ATTRIBUTIONS
 * ============================================================
 * Affectation des parcelles aux adhérents. Comprend l'historique et les affectations en cours.
 */
-- Historique (terminées en 2025)
INSERT INTO Attribution (date_attribution, date_fin, id_parcelle_assigner, id_utilisateur_fournir) VALUES
('2025-03-01', '2025-11-01', 2, 6); -- Sophie (désormais RIGAL Robin) avait la Nord 102 en 2025
-- Attributions ACTIVES (saison 2026)
INSERT INTO Attribution (date_attribution, date_fin, id_parcelle_assigner, id_utilisateur_fournir) VALUES
('2026-02-15', NULL, 1, 4), -- Adhérent 4 -> Nord 101
('2026-03-01', NULL, 4, 5), -- Adhérent 5 -> Sud 201
('2026-03-10', NULL, 2, 6), -- Adhérent 6 -> Nord 102
('2026-03-20', NULL, 5, 7), -- Adhérent 7 -> Sud 202
('2026-04-05', NULL, 7, 8); -- Adhérent 8 -> Est 301

/**
 * ============================================================
 * 10. RÉSERVATIONS D'OUTILS
 * ============================================================
 * Suivi des emprunts matériels des adhérents, avec leurs différents statuts.
 */
INSERT INTO Reservation (dateD, dateF, statutR, id_outil_concerner, id_utilisateur_effectuer) VALUES
-- Passées
('2026-04-05', '2026-04-06', 'terminée', 1, 6),
('2026-04-12', '2026-04-13', 'terminée', 3, 4),
('2026-04-25', '2026-04-26', 'terminée', 5, 7),
-- En cours
('2026-05-12', '2026-05-14', 'en cours', 6, 5),
-- Confirmées (à venir)
('2026-05-18', '2026-05-19', 'confirmée', 3, 4),
('2026-05-22', '2026-05-23', 'confirmée', 1, 8),
-- En attente
('2026-05-25', '2026-05-26', 'en attente', 2, 7),
('2026-05-28', '2026-05-30', 'en attente', 5, 6),
-- Annulée
('2026-04-30', '2026-05-01', 'annulée', 2, 5);

/**
 * ============================================================
 * 11. NOTIFICATIONS
 * ============================================================
 * Messages systèmes et alertes envoyés aux utilisateurs.
 */
INSERT INTO Notification (titreN, contenuN, date_envoi, typeN, est_lue, id_utilisateur_recoit) VALUES
('Bienvenue !', 'Votre compte adhérent est validé. Bienvenue à La Bòstia Verda !', '2026-01-11', 'Action', TRUE, 4),
('Rappel restitution', 'Merci de penser à rapporter la bêche empruntée la semaine dernière.', '2026-04-07', 'Rappel', FALSE, 6),
('Conseil météo', 'Forte chaleur annoncée cette semaine, pensez à arroser tôt le matin.', '2026-05-08', 'Conseil', FALSE, 4),
('Conseil météo', 'Forte chaleur annoncée cette semaine, pensez à arroser tôt le matin.', '2026-05-08', 'Conseil', FALSE, 5),
('Alerte sanitaire détectée', 'Une alerte mildiou a été signalée sur une parcelle voisine de la vôtre. Inspectez vos tomates.', '2026-05-10', 'Alerte', FALSE, 4),
('Alerte sanitaire détectée', 'Une alerte mildiou a été signalée sur une parcelle voisine de la vôtre. Inspectez vos tomates.', '2026-05-10', 'Alerte', FALSE, 6),
('Réservation confirmée', 'Votre réservation de l''arrosoir du 18/05 a été confirmée par l''administrateur.', '2026-05-12', 'Action', FALSE, 4);

/**
 * ============================================================
 * 12. CULTURES
 * ============================================================
 * Représente les plantations effectuées sur les parcelles par les adhérents.
 */
-- Culture passée
INSERT INTO Culture (date_semis, statut_Culture, date_recolte_prevue, id_attribution_seffectuer, id_plante_definir) VALUES
('2025-05-01', 'terminée', '2025-08-01', 1, 5);
-- Cultures EN COURS pour la saison 2026
INSERT INTO Culture (date_semis, statut_Culture, date_recolte_prevue, id_attribution_seffectuer, id_plante_definir) VALUES
-- Adhérent 4 (attribution 2, Nord 101)
('2026-04-20', 'en cours', '2026-08-15', 2, 1),
('2026-04-22', 'en cours', '2026-07-20', 2, 4),
('2026-04-25', 'en cours', '2026-07-10', 2, 7),
-- Adhérent 5 (attribution 3, Sud 201)
('2026-04-25', 'en cours', '2026-09-01', 3, 3),
('2026-04-28', 'en cours', '2026-08-10', 3, 6),
-- Adhérent 6 (attribution 4, Nord 102)
('2026-04-15', 'terminée', '2026-05-10', 4, 5),
('2026-05-01', 'en cours', '2026-09-15', 4, 2),
('2026-05-03', 'en cours', '2026-09-30', 4, 10),
-- Adhérent 7 (attribution 5, Sud 202)
('2026-04-20', 'en cours', '2026-09-20', 5, 8),
('2026-04-20', 'en cours', '2026-09-25', 5, 9),
-- Adhérent 8 (attribution 6, Est 301)
('2026-05-05', 'en cours', '2026-08-15', 6, 1),
('2026-05-08', 'en cours', '2026-07-05', 6, 7);

/**
 * ============================================================
 * 13. RÉCOLTES
 * ============================================================
 * Enregistrement des quantités récoltées pour les cultures terminées.
 */
INSERT INTO Recolte (date_recolte, quantiteR, uniteR, qualite_avis, id_culture_produire) VALUES
('2025-06-15', 2.5, 'kg', 'excellente', 1),
('2025-07-10', 1.2, 'kg', 'moyenne', 1),
('2026-05-08', 1.8, 'kg', 'excellente', 6),
('2026-05-12', 1.1, 'kg', 'excellente', 6);

/**
 * ============================================================
 * 14. ALERTES SANITAIRES
 * ============================================================
 * Déclaration des maladies ou nuisibles constatés sur les cultures.
 */
INSERT INTO AlerteSanitaire (descriptionALR, date_detection, est_resolue, nom_menace, niveau_gravite, traitement_applique, id_culture_est_signalee_sur) VALUES
('Taches noires sur les feuilles basses, sans atteindre les fruits.', '2026-05-09', FALSE, 'Mildiou', 'élevé', 'Bouillie bordelaise (renouveler 2x/semaine)', 2),
('Petits insectes verts agglutinés sous les feuilles de basilic.', '2026-05-04', TRUE, 'Pucerons verts', 'modéré', 'Savon noir dilué + coccinelles relâchées', 3),
('Feuilles trouées et coupées au ras du sol.', '2026-05-06', TRUE, 'Limaces', 'faible', 'Cendres + pièges à bière', 11),
('Jaunissement progressif des feuilles, possible carence en azote.', '2026-05-11', FALSE, 'Carence azote', 'modéré', NULL, 7),
('Cloque sur jeunes feuilles, déformation suspecte.', '2026-05-12', FALSE, 'Cloque du pêcher', 'critique', 'Isolement immédiat de la parcelle voisine', 10);

/**
 * ============================================================
 * 15. TABLES DE LIAISON (Tests croisés)
 * ============================================================
 * Alimentation des relations N:M pour valider le modèle relationnel.
 */

-- Liste d'attente / inscriptions aux parcelles
INSERT INTO s_inscrire (id_utilisateur, id_parcelle, date_demande, priorite, motivation) VALUES
(1, 3, '2026-04-12', 1, 'Très motivé pour cultiver bio. Disponible en semaine.'),
(2, 6, '2026-04-22', 2, 'Famille de 4 personnes, projet pédagogique avec enfants.'),
(3, 8, '2026-05-04', 1, 'Nouveau dans la commune, envie de s''investir localement.');

-- Topologie des parcelles (qui est à côté de qui)
INSERT INTO est_voisine_de (id_parcelle, id_parcelle_voisine) VALUES
(1, 2), (2, 1),
(2, 3), (3, 2),
(4, 5), (5, 4),
(5, 6), (6, 5),
(7, 8), (8, 7);

-- Associations culturales (compagnonnage végétal)
INSERT INTO saccorder (id_plante_E1, id_plante_E2, type_accord) VALUES
(1, 4, 'Association bénéfique'),
(2, 4, 'Association bénéfique'),
(3, 7, 'Association bénéfique'),
(8, 4, 'Association bénéfique'),
(9, 4, 'Association bénéfique'),
(10, 3, 'Association bénéfique'),
(1, 3, 'Association néfaste'),
(6, 10, 'Association néfaste');

-- Observations de la biodiversité sur les parcelles
INSERT INTO observer (id_utilisateur, id_parcelle, date_observation, espece) VALUES
(4, 1, '2026-04-22', 'Coccinelle à 7 points'),
(4, 1, '2026-04-25', 'Ver de terre'),
(4, 1, '2026-05-02', 'Abeille domestique'),
(4, 1, '2026-05-09', 'Syrphe ceinturé'),
(5, 4, '2026-04-26', 'Abeille charpentière'),
(5, 4, '2026-05-05', 'Bourdon terrestre'),
(5, 4, '2026-05-10', 'Carabe doré'),
(6, 2, '2026-05-04', 'Hérisson (traces)'),
(6, 2, '2026-05-12', 'Lézard des murailles'),
(7, 5, '2026-04-30', 'Mésange charbonnière'),
(7, 5, '2026-05-08', 'Cétoine dorée'),
(8, 7, '2026-05-10', 'Coccinelle à 2 points');

-- Lien entre une donnée météo et un conseil justifié par celle-ci
INSERT INTO justifier (id_meteo, id_conseil) VALUES
(3, 2),
(7, 5),
(10, 5),
(7, 6);

-- Détail des apports en semences liés aux contributions
INSERT INTO porter_sur (id_contribution, id_semence, quantite_apportee) VALUES
(2, 1, 50),
(3, 2, 40),
(5, 5, 50),
(5, 3, 30);

-- Historique des modifications de fiches plantes par les tuteurs
INSERT INTO modifier_tuteur (id_utilisateur, id_plante, date_modification) VALUES
(9, 1, '2026-03-10'),
(9, 4, '2026-03-12'),
(10, 8, '2026-04-02'),
(10, 9, '2026-04-02'),
(9, 6, '2026-04-15');