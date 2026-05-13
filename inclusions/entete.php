<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre) ? htmlspecialchars($titre) . ' — ' : '' ?>La Bòstia Verda</title>
    <!-- Inclusion de la feuille de style avec chemin relatif -->
    <link rel="stylesheet" href="ressources/styles/style.css">
    <!-- Favicon — nouveau logo La Bòstia Verda -->
    <link rel="icon" type="image/png" href="ressources/images/logo.png">
</head>
<body class="<?= isset($_SESSION['id_utilisateur']) ? 'logged-in' : 'guest' ?>">

<!-- EN-TÊTE PRINCIPAL (Navigation) -->
<header class="main-header">
    <a href="index.php?page=accueil" class="logo" style="text-decoration: none; color: inherit;">
        <img src="ressources/images/logo.png" alt="Logo La Bòstia Verda" class="header-logo">
        <strong>La Bòstia Verda</strong>
    </a>
    
    <nav class="main-nav">
        <ul class="nav-menu">
            <?php 
            // Vérification de la session active et récupération du rôle. 
            // Si l'utilisateur n'est pas connecté, on lui donne le rôle "Invite" 
            // (pour ne pas le confondre avec le Visiteur qui est le compte en attente)
            $roleActuel = isset($_SESSION['id_utilisateur']) ? ($_SESSION['roleU'] ?? 'Invite') : 'Invite';

            // Affichage dynamique du menu selon le rôle de l'utilisateur
            switch ($roleActuel) {
                case 'Visiteur':
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=plantes" class="nav-link">Catalogue des plantes</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=cultures" class="nav-link nav-link-pending">Ma demande d\'inscription <span class="badge badge-pending">En attente</span></a></li>';
                    break;
                case 'Adhérent':
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=cultures" class="nav-link">Mon jardin et cultures</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=outils" class="nav-link">Réserver un outil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=meteo" class="nav-link">Journal météo</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=alertes_adherent" class="nav-link">Signaler une alerte</a></li>';
                    break;
                case 'Responsable':
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=parcelles" class="nav-link">Gestion des attributions</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=tableau_bord" class="nav-link">Tableau de bord</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=recoltes" class="nav-link">Historique des recoltes</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=analyses" class="nav-link">Analyses decisionnelles</a></li>';
                    break;
                case 'Tuteur':
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=conseils" class="nav-link">Publier un conseil cultural</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=alertes" class="nav-link">Declarer une alerte sanitaire</a></li>';
                    break;
                case 'Trésorier':
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=tresorerie" class="nav-link">Finances et contributions</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=stocks" class="nav-link">Stocks de semences</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=stocks_outils" class="nav-link">Stocks d\'outils</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=analyses" class="nav-link">Analyses decisionnelles</a></li>';
                    break;
                case 'Administrateur':
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=admin_tableau_bord" class="nav-link">Supervision globale</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=admin_utilisateurs" class="nav-link">Gestion des utilisateurs</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=analyses" class="nav-link">Analyses decisionnelles</a></li>'; // Admin can also see analyses
                    break;
                default:
                    // Menu par défaut pour les invités non connectés
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=plantes" class="nav-link">Catalogue des plantes</a></li>';
                    break;
            }
            ?>
        </ul>
    </nav>
    <!-- Affichage des informations utilisateur et bouton de déconnexion -->
    <div class="header-actions">
        <?php if (isset($_SESSION['id_utilisateur'])): ?>
            <!-- Le bouton de déconnexion est commun à tous les rôles -->
            <a href="index.php?page=deconnexion" class="btn btn-primary">Déconnexion</a>
        <?php else: ?>
            <!-- Boutons de connexion et d'inscription alignés à droite pour les invités -->
            <a href="index.php?page=connexion" class="link-login">Connexion</a>
            <a href="index.php?page=inscription" class="btn btn-primary">S'inscrire</a>
        <?php endif; ?>
    </div>
</header>

<!-- BADGE UTILISATEUR (En haut à gauche sous la navbar) -->
<?php if (isset($_SESSION['id_utilisateur'])): ?>
    <?php
    $nom_complet = obtenir_nom_complet($bdd);
    
    // Attribution d'une petite icône stylée selon le rôle
    // Emojis removed as per strict rule. Icons can be handled via CSS classes if needed.
    // For now, just display the role text.
    ?>
    <div class="badge-wrapper">
        <a href="index.php?page=profil" class="user-status-badge" title="Accéder à mon profil">
            <span class="status-text">Connecté en tant que</span>
            <span class="status-name"><?= htmlspecialchars($nom_complet) ?></span>
            <span class="status-role"><?= htmlspecialchars($roleActuel) ?></span>
        </a>
    </div>
<?php endif; ?>