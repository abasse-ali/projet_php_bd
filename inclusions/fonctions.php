<?php
/**
 * inclusions/fonctions.php
 *
 * Bibliothèque de fonctions transverses utilisées dans toute l'application :
 * - Rendu d'icônes SVG inline.
 * - Contrôle d'accès par rôle.
 * - Lecture des données utilisateur en session.
 * - Propagation spatiale des alertes sanitaires.
 * - Lecture des notifications.
 */

/**
 * Retourne le code SVG inline d'une icône (24×24).
 * Les icônes utilisent `currentColor` pour hériter de la couleur du texte parent.
 *
 * @param string $name Identifiant de l'icône (ex. 'warning', 'check', 'sun').
 * @param string $extra_class Classes CSS additionnelles à ajouter sur le <svg>.
 * @return string Le SVG prêt à être inséré dans le HTML, ou chaîne vide si nom inconnu.
 */
function icon($name, $extra_class = '') {
    $paths = [
        'warning' => '<path d="M12 2L1 21h22L12 2zm0 4.18L19.53 19H4.47L12 6.18zM11 10v5h2v-5h-2zm0 6v2h2v-2h-2z"/>',
        'check' => '<path d="M20.285 6.708l-11.39 11.39-5.18-5.18 1.414-1.414 3.766 3.766L18.87 5.294l1.415 1.414z"/>',
        'arrow-right' => '<path d="M8.59 16.59L13.17 12 8.59 7.41 10 6l6 6-6 6-1.41-1.41z"/>',
        'arrow-down' => '<path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z"/>',
        'lightbulb' => '<path d="M9 21h6v-2H9v2zm3-19a7 7 0 00-4 12.74V17a1 1 0 001 1h6a1 1 0 001-1v-2.26A7 7 0 0012 2zm2.16 11.13l-.16.12V16h-4v-2.75l-.16-.12a5 5 0 114.32 0z"/>',
        'lock' => '<path d="M12 2a5 5 0 00-5 5v3H6a2 2 0 00-2 2v9a2 2 0 002 2h12a2 2 0 002-2v-9a2 2 0 00-2-2h-1V7a5 5 0 00-5-5zm-3 8V7a3 3 0 016 0v3H9zm3 4a2 2 0 11-.001 4.001A2 2 0 0112 14z"/>',
        'tools' => '<path d="M21.71 20.29l-5.4-5.4a6 6 0 00-7.6-7.6L12 10.6 10.6 12 7.3 8.7a6 6 0 007.6 7.6l5.4 5.4a1 1 0 001.41-1.41zM5 3a2 2 0 00-2 2v3.59a1 1 0 00.29.7l4.71 4.71L11.59 10 6.88 5.29A1 1 0 006.18 5H5z"/>',
        'water' => '<path d="M12 2.69l5.66 5.66a8 8 0 11-11.32 0L12 2.69zm0 2.83L7.76 9.76a6 6 0 108.48 0L12 5.52z"/>',
        'sparkles' => '<path d="M12 2l1.65 4.35L18 8l-4.35 1.65L12 14l-1.65-4.35L6 8l4.35-1.65L12 2zm6 10l.99 2.61L21.6 16l-2.61.99L18 19.6l-.99-2.61L14.4 16l2.61-.99L18 12zM6 14l.66 1.74L8.4 16.4l-1.74.66L6 18.8l-.66-1.74L3.6 16.4l1.74-.66L6 14z"/>',
        'broom' => '<path d="M19.36 2.72L20.78 4.14l-5.66 5.66 1.41 1.41-1.41 1.41-4.24-4.24 1.41-1.41 1.41 1.41 5.66-5.66zm-9.9 9.9l4.24 4.24-2.83 2.83a3 3 0 01-4.24 0l-1.41-1.41a3 3 0 010-4.24l4.24-1.42z"/>',
        'seedling' => '<path d="M12 22c-4.42 0-8-3.58-8-8 0-2.06.78-3.94 2.06-5.36C7.4 7.34 9.36 7 11 7c.38 0 .76.03 1.13.09A8.95 8.95 0 0112 14c0 .35.02.7.06 1.04.66-.04 1.31-.21 1.91-.5C16.39 12.43 18 9.97 18 7c0-1.66-.34-3.21-1-4.58A8.95 8.95 0 0113 2C8.58 2 5 5.58 5 10c0 .35.02.7.06 1.04C2.74 12.42 1 14.95 1 18c0 .35.04.69.1 1.02C1.69 21.34 4.61 23 8 23h8c1.66 0 3-1.34 3-3h-7z"/>',
        'users' => '<path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/>',
        'wheat' => '<path d="M12 2C9 5 9 9 12 12c3-3 3-7 0-10zM6 8c-1 3 1 6 4 7-1-3-3-5-4-7zm12 0c1 3-1 6-4 7 1-3 3-5 4-7zM6 14c-1 3 1 6 4 7-1-3-3-5-4-7zm12 0c1 3-1 6-4 7 1-3 3-5 4-7zm-6 4v4h0v-4z"/>',
        'pen' => '<path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04a1 1 0 000-1.41l-2.34-2.34a1 1 0 00-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/>',
        'sun' => '<path d="M12 7a5 5 0 100 10 5 5 0 000-10zm0 2a3 3 0 110 6 3 3 0 010-6zM11 1h2v3h-2V1zm0 19h2v3h-2v-3zM3.5 4.93l1.41-1.41L7.05 5.64 5.64 7.05 3.5 4.93zM16.95 18.36l1.41-1.41 2.14 2.13-1.41 1.41-2.14-2.13zM1 11h3v2H1v-2zm19 0h3v2h-3v-2zM4.93 20.5l-1.41-1.41 2.13-2.14 1.41 1.41-2.13 2.14zM18.36 7.05l-1.41-1.41 2.13-2.14 1.41 1.41-2.13 2.14z"/>',
        'cloud' => '<path d="M19.35 10.04A7.49 7.49 0 0012 4C9.11 4 6.6 5.64 5.35 8.04A5.99 5.99 0 000 14a6 6 0 006 6h13a5 5 0 00.35-9.96zM19 18H6a4 4 0 110-8 1 1 0 001-.84A5.5 5.5 0 0117.5 11a1 1 0 001 1A3 3 0 0119 18z"/>',
        'rain' => '<path d="M17 10.5A6.5 6.5 0 0010.5 4 6.5 6.5 0 004 10.5a5 5 0 000 9.96 1 1 0 100-2A3 3 0 014 14a1 1 0 001-.84A4.5 4.5 0 0114.5 11a1 1 0 001 1 3 3 0 010 6 1 1 0 100 2 5 5 0 001.5-9.5zM7 20l1-2H6l-1 2h2zm4 0l1-2h-2l-1 2h2zm4 0l1-2h-2l-1 2h2z"/>',
        'snow' => '<path d="M17 10.5A6.5 6.5 0 0010.5 4 6.5 6.5 0 004 10.5a5 5 0 000 10h13a5 5 0 000-10zm-9 11.5l.5-1H7l.5 1zm4 0l.5-1h-1.5l.5 1zm4 0l.5-1H15l.5 1zM6 22l1-2H5l-1 2h2zm12 0l1-2h-2l-1 2h2z"/>',
        'storm' => '<path d="M17 10.5A6.5 6.5 0 0010.5 4 6.5 6.5 0 004 10.5a5 5 0 000 9.96 1 1 0 100-2A3 3 0 014 14a1 1 0 001-.84A4.5 4.5 0 0114.5 11a1 1 0 001 1 3 3 0 010 6 1 1 0 100 2 5 5 0 001.5-9.5zM11 14l-3 5h2l-1 3 3-5h-2l1-3z"/>',
        'fog' => '<path d="M3 15h18v2H3v-2zm0 4h18v2H3v-2zM12 4a7 7 0 00-7 7 1 1 0 001 1h12a1 1 0 001-1 7 7 0 00-7-7z"/>',
        'drizzle' => '<path d="M17 10.5A6.5 6.5 0 0010.5 4 6.5 6.5 0 004 10.5a5 5 0 000 9.96 1 1 0 100-2A3 3 0 014 14a1 1 0 001-.84A4.5 4.5 0 0114.5 11a1 1 0 001 1 3 3 0 010 6 1 1 0 100 2 5 5 0 001.5-9.5zM8 21l.5-1h-1l.5 1zm4 0l.5-1h-1l.5 1zm4 0l.5-1h-1l.5 1z"/>',
    ];

    if (!isset($paths[$name])) {
        return '';
    }

    $cls = 'icon icon-' . htmlspecialchars($name);
    if ($extra_class !== '') {
        $cls .= ' ' . htmlspecialchars($extra_class);
    }

    return '<svg class="' . $cls . '" viewBox="0 0 24 24" width="1em" height="1em" fill="currentColor" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
}

/**
 * Mappe un libellé météo français vers l'identifiant d'icône correspondant.
 *
 * @param string $libelle Description météo lisible (ex. "Pluie", "Ciel clair").
 * @return string Nom d'icône à passer à {@see icon()}.
 */
function icone_meteo($libelle) {
    if (stripos($libelle, 'clair') !== false) return 'sun';
    if (stripos($libelle, 'nuageux') !== false) return 'cloud';
    if (stripos($libelle, 'brouillard') !== false) return 'fog';
    if (stripos($libelle, 'bruine') !== false) return 'drizzle';
    if (stripos($libelle, 'orage') !== false) return 'storm';
    if (stripos($libelle, 'neige') !== false) return 'snow';
    if (stripos($libelle, 'pluie') !== false) return 'rain';
    if (stripos($libelle, 'averse') !== false) return 'rain';
    return 'cloud';
}

/**
 * Indique si l'utilisateur courant possède l'un des rôles autorisés.
 * L'Administrateur a systématiquement accès à tout.
 *
 * @param string|string[] $roles Rôle unique ou liste de rôles acceptés.
 * @return bool true si l'accès est autorisé.
 */
function a_role($roles) {
    if (!isset($_SESSION['roleU'])) {
        return false;
    }
    if ($_SESSION['roleU'] === 'Administrateur') {
        return true;
    }
    if (is_string($roles)) {
        $roles = [$roles];
    }
    return in_array($_SESSION['roleU'], $roles);
}

/**
 * Bloque l'accès à la page courante si l'utilisateur n'a pas le rôle requis.
 * Redirige vers l'accueil et termine le script.
 *
 * @param string|string[] $roles Rôle(s) autorisé(s).
 * @return void
 */
function exiger_role($roles) {
    if (!a_role($roles)) {
        header("Location: index.php?page=accueil");
        exit;
    }
}

/**
 * Récupère le nom complet (prénom + nom) de l'utilisateur connecté.
 * Renvoie la chaîne "Utilisateur" si la session n'est pas active.
 *
 * @param PDO $bdd Connexion à la base de données.
 * @return string Nom complet déjà échappé pour le HTML.
 */
function obtenir_nom_complet(PDO $bdd) {
    if (!isset($_SESSION['id_utilisateur'])) {
        return 'Utilisateur';
    }
    $requete = $bdd->prepare("SELECT prenomU, nomU FROM Utilisateur WHERE id_utilisateur = ?");
    $requete->execute([$SESSION['id_utilisateur']]);
    $donnees = $requete->fetch();
    if ($donnees) {
        return htmlspecialchars($donnees['prenomu'] . ' ' . $donnees['nomu']);
    }
    return htmlspecialchars($_SESSION['nom'] ?? 'Utilisateur');
}

/**
 * Récupère l'attribution active (parcelle + dates) d'un utilisateur.
 * Une attribution est dite active si `date_fin` est NULL ou postérieure à aujourd'hui.
 *
 * @param PDO $bdd Connexion BDD.
 * @param int $id_utilisateur Identifiant de l'adhérent.
 * @return array|false Ligne fusionnée Parcelle/Attribution, ou false si aucune attribution active.
 */
function obtenir_attribution_active(PDO $bdd, $id_utilisateur) {
    $requete = $bdd->prepare("
        SELECT p.*, a.date_attribution, a.id_attribution
        FROM Parcelle p
        JOIN Attribution a ON p.id_parcelle = a.id_parcelle_assigner
        WHERE a.id_utilisateur_fournir = ? AND (a.date_fin IS NULL OR a.date_fin > CURRENT_DATE)
    ");
    $requete->execute([$id_utilisateur]);
    return $requete->fetch();
}

/**
 * Liste toutes les cultures rattachées à une attribution donnée,
 * triées du semis le plus récent au plus ancien.
 *
 * @param PDO $bdd Connexion BDD.
 * @param int $id_attribution Identifiant de l'attribution.
 * @return array Cultures jointes au nom de variété.
 */
function obtenir_cultures_par_attribution(PDO $bdd, $id_attribution) {
    $requete = $bdd->prepare("
        SELECT c.*, pl.nom_variete
        FROM Culture c
        JOIN Plante pl ON c.id_plante_definir = pl.id_plante
        WHERE c.id_attribution_seffectuer = ?
        ORDER BY c.date_semis DESC
    ");
    $requete->execute([$id_attribution]);
    return $requete->fetchAll();
}

/**
 * Propage une alerte sanitaire aux exploitants des parcelles voisines.
 *
 * Algorithme :
 * 1. Localise la parcelle d'origine via la culture concernée.
 * 2. Cherche les parcelles voisines via l'association réflexive `est_voisine_de`.
 * 3. Cible les exploitants actifs de ces parcelles, plus tous les Tuteurs.
 * 4. Insère une notification ciblée pour chacun (l'auteur de l'alerte est exclu).
 *
 * @param PDO $bdd Connexion BDD.
 * @param int $id_culture Culture sur laquelle l'alerte porte.
 * @param string $nom_menace Nom de la menace (ex. "Mildiou").
 * @param string $gravite Niveau de gravité.
 * @param int $id_auteur Auteur du signalement (exclu des destinataires).
 * @return int Nombre de notifications créées.
 */
function propager_alerte_voisinage(PDO $bdd, $id_culture, $nom_menace, $gravite, $id_auteur) {
    // Étape 1 — Identifier la parcelle d'origine
    $requete = $bdd->prepare("
        SELECT parc.id_parcelle, parc.secteurP, parc.numeroP
        FROM Culture c
        JOIN Attribution a ON c.id_attribution_seffectuer = a.id_attribution
        JOIN Parcelle parc ON a.id_parcelle_assigner = parc.id_parcelle
        WHERE c.id_culture = ?
    ");
    $requete->execute([$id_culture]);
    $origine = $requete->fetch();
    if (!$origine) return 0;

    $id_parcelle_origine = $origine['id_parcelle'];
    $secteur_origine = $origine['secteurp'];
    $num_origine = $origine['numerop'];

    // Étape 2 + 3 — Destinataires : voisins actifs + tous les Tuteurs (auteur exclu, doublons supprimés)
    $requete = $bdd->prepare("
        SELECT DISTINCT u.id_utilisateur
        FROM est_voisine_de v
        JOIN Attribution a ON a.id_parcelle_assigner = v.id_parcelle_voisine
        JOIN Utilisateur u ON u.id_utilisateur = a.id_utilisateur_fournir
        WHERE v.id_parcelle = ?
          AND (a.date_fin IS NULL OR a.date_fin > CURRENT_DATE)
          AND u.id_utilisateur <> ?
        UNION
        SELECT id_utilisateur FROM Utilisateur
        WHERE roleU = 'Tuteur' AND id_utilisateur <> ?
    ");
    $requete->execute([$id_parcelle_origine, $id_auteur, $id_auteur]);
    $destinataires = $requete->fetchAll(PDO::FETCH_COLUMN);

    if (empty($destinataires)) return 0;

    // Étape 4 — Insertion des notifications ciblées
    $titre = "Alerte sanitaire à proximité : " . $nom_menace;
    $contenu = "Une alerte « " . $nom_menace . " » (gravité " . $gravite . ") "
             . "a été signalée sur la parcelle Secteur " . $secteur_origine . "-" . $num_origine . ". "
             . "Inspectez vos cultures voisines et appliquez les mesures préventives si besoin.";

    $insertion = $bdd->prepare("
        INSERT INTO Notification (titreN, contenuN, date_envoi, typeN, est_lue, id_utilisateur_recoit)
        VALUES (?, ?, CURRENT_DATE, 'Alerte', FALSE, ?)
    ");
    
    $nb_inserees = 0;
    foreach ($destinataires as $id_dest) {
        $insertion->execute([$titre, $contenu, $id_dest]);
        $nb_inserees++;
    }
    return $nb_inserees;
}

/**
 * Récupère les notifications non lues d'un utilisateur, des plus récentes aux plus anciennes.
 *
 * @param PDO $bdd Connexion BDD.
 * @param int $id_utilisateur Destinataire des notifications.
 * @param int $limit Nombre maximum de notifications à retourner.
 * @return array Lignes de la table `Notification`.
 */
function obtenir_notifications_non_lues(PDO $bdd, $id_utilisateur, $limit = 5) {
    // bindValue avec PARAM_INT est nécessaire car le placeholder LIMIT est typé entier strict.
    $requete = $bdd->prepare("
        SELECT * FROM Notification
        WHERE id_utilisateur_recoit = ? AND est_lue = false
        ORDER BY date_envoi DESC
        LIMIT ?
    ");
    $requete->bindValue(1, $id_utilisateur, PDO::PARAM_INT);
    $requete->bindValue(2, $limit, PDO::PARAM_INT);
    $requete->execute();
    return $requete->fetchAll();
}