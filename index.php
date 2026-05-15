<?php
/**
 * index.php — Front Controller
 *
 * Point d'entrée unique de l'application : toutes les requêtes HTTP passent par
 * ce fichier, qui se charge :
 * 1. de bootstrapper la session et la connexion BDD,
 * 2. de résoudre l'URL `?page=X` vers le bon fichier de pages/,
 * 3. de bloquer l'accès aux pages privées si l'utilisateur n'est pas connecté,
 * 4. de rafraîchir le rôle en session à chaque chargement.
 */

require 'configuration/configuration.php';
require 'configuration/bdd.php';
require 'inclusions/fonctions.php';

/**
 * Table de correspondance URL -> chemin réel du fichier (sans extension).
 * Les pages sont regroupées par rôle dans des sous-dossiers pour la lisibilité.
 * * @var array $chemins_pages
 */
$chemins_pages = [
    // Public — accessible sans connexion
    'accueil' => 'public/accueil',
    'connexion' => 'public/connexion',
    'inscription' => 'public/inscription',
    'plantes' => 'public/plantes',

    // Commun — tout utilisateur connecté
    'deconnexion' => 'commun/deconnexion',
    'profil' => 'commun/profil',

    // Adhérent
    'cultures' => 'adherent/cultures',
    'outils' => 'adherent/outils',
    'meteo' => 'adherent/meteo',
    'alertes_adherent' => 'adherent/signaler_alerte',

    // Tuteur
    'alertes' => 'tuteur/alertes',
    'conseils' => 'tuteur/conseils',

    // Trésorier
    'tresorerie' => 'tresorier/tresorerie',
    'stocks' => 'tresorier/semences',
    'stocks_outils' => 'tresorier/outils',

    // Responsable du terrain
    'parcelles' => 'responsable/parcelles',
    'recoltes' => 'responsable/recoltes',
    'tableau_bord' => 'responsable/tableau_bord',
    'analyses' => 'responsable/analyses',

    // Administrateur
    'admin_tableau_bord' => 'administrateur/tableau_bord',
    'admin_utilisateurs' => 'administrateur/utilisateurs',
    'admin_ressources' => 'administrateur/ressources',
    'admin_terrain' => 'administrateur/terrain',
];

/**
 * @var string $page Page demandée — défaut : accueil. 
 * Une page inconnue est silencieusement remplacée par l'accueil.
 */
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';
if (!isset($chemins_pages[$page])) {
    $page = 'accueil';
}

/**
 * @var array $pages_publiques Liste fermée des pages accessibles sans connexion (whitelist).
 */
$pages_publiques = ['accueil', 'connexion', 'inscription', 'plantes'];

// Blocage des pages privées pour les visiteurs non authentifiés.
if (!isset($_SESSION['id_utilisateur']) && !in_array($page, $pages_publiques)) {
    header("Location: index.php?page=connexion");
    exit;
}

/**
 * Rafraîchissement dynamique du rôle :
 * On relit la colonne `roleU` à chaque chargement de page. Ainsi, si un Administrateur
 * modifie ou suspend un compte, la nouvelle valeur est prise en compte dès le prochain rafraîchissement
 * sans nécessiter de déconnexion/reconnexion.
 */
if (isset($_SESSION['id_utilisateur'])) {
    $requete_role = $bdd->prepare("SELECT roleU FROM Utilisateur WHERE id_utilisateur = ?");
    $requete_role->execute([$_SESSION['id_utilisateur']]);
    if ($role_actuel = $requete_role->fetchColumn()) {
        $_SESSION['roleU'] = $role_actuel;
    }
}

// Chargement effectif de la page demandée.
include "pages/" . $chemins_pages[$page] . ".php";
?>