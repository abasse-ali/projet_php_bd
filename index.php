<?php
// index.php — Point d'entrée unique (Front Controller)
// Architecture 3 tiers : ce fichier orchestre la couche Présentation
// en chargeant la bonne page selon le rôle de l'utilisateur.

require 'configuration/configuration.php'; // session_start() et configuration globale
require 'configuration/bdd.php';           // Connexion PDO PostgreSQL
require 'inclusions/fonctions.php';        // Fonctions de sécurité, rôles, icônes…

// --- Correspondance URL ?page=X → chemin réel du fichier ---
$chemins_pages = [
    // Public (accessible sans connexion)
    'accueil'             => 'public/accueil',
    'connexion'           => 'public/connexion',
    'inscription'         => 'public/inscription',
    'plantes'             => 'public/plantes',

    // Commun (tous rôles connectés)
    'deconnexion'         => 'commun/deconnexion',
    'profil'              => 'commun/profil',

    // Adhérent
    'cultures'            => 'adherent/cultures',
    'outils'              => 'adherent/outils',
    'meteo'               => 'adherent/meteo',
    'alertes_adherent'    => 'adherent/signaler_alerte',

    // Tuteur
    'alertes'             => 'tuteur/alertes',
    'conseils'            => 'tuteur/conseils',

    // Trésorier
    'tresorerie'          => 'tresorier/tresorerie',
    'stocks'              => 'tresorier/semences',
    'stocks_outils'       => 'tresorier/outils',

    // Responsable du terrain
    'parcelles'           => 'responsable/parcelles',
    'recoltes'            => 'responsable/recoltes',
    'tableau_bord'        => 'responsable/tableau_bord',
    'analyses'            => 'responsable/analyses',

    // Administrateur
    'admin_tableau_bord'  => 'administrateur/tableau_bord',
    'admin_utilisateurs'  => 'administrateur/utilisateurs',
    'admin_ressources'    => 'administrateur/ressources',
    'admin_terrain'       => 'administrateur/terrain',
];

// Page demandée (défaut : accueil)
$page = isset($_GET['page']) ? $_GET['page'] : 'accueil';

// Page inconnue → accueil
if (!isset($chemins_pages[$page])) {
    $page = 'accueil';
}

// Pages publiques (accessibles sans connexion)
$pages_publiques = ['accueil', 'connexion', 'inscription', 'plantes'];

// Blocage des pages privées pour les non-connectés
if (!isset($_SESSION['id_utilisateur']) && !in_array($page, $pages_publiques)) {
    header("Location: index.php?page=connexion");
    exit;
}

// --- ACTUALISATION DYNAMIQUE DU RÔLE ---
// On relit le rôle en base à chaque chargement : si l'Administrateur change le rôle
// d'un utilisateur, celui-ci voit le changement appliqué dès le prochain rafraîchissement (F5).
if (isset($_SESSION['id_utilisateur'])) {
    $requete_role = $bdd->prepare("SELECT roleU FROM Utilisateur WHERE id_utilisateur = ?");
    $requete_role->execute([$_SESSION['id_utilisateur']]);
    if ($role_actuel = $requete_role->fetchColumn()) {
        $_SESSION['roleU'] = $role_actuel;
    }
}

// Chargement de la page via la correspondance
include "pages/" . $chemins_pages[$page] . ".php";
?>
