<?php
/**
 * pages/commun/deconnexion.php
 *
 * Script de déconnexion de l'application.
 * Libère toutes les variables de session actives, détruit la session courante,
 * puis redirige l'utilisateur vers la page d'authentification.
 */

// Désaffecte toutes les variables de la session active
session_unset();

// Détruit complètement les données associées à la session sur le serveur
session_destroy();

// Redirection de sécurité vers la page de connexion
header("Location: index.php?page=connexion");
exit;
?>