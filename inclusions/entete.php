<?php
/**
 * inclusions/entete.php
 *
 * Composant d'en-tête (Header) global de l'application.
 * Initialise le document HTML5, charge les ressources graphiques (CSS, Favicon)
 * et génère de façon dynamique le menu de navigation structurel en fonction
 * du rôle applicatif de l'utilisateur stocké en session ($_SESSION['roleU']).
 */
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($titre) ? htmlspecialchars($titre) . ' — ' : '' ?>La Bòstia Verda</title>
    <link rel="stylesheet" href="ressources/styles/style.css">
    <link rel="icon" type="image/png" href="ressources/images/logo.png">
</head>
<body class="<?= isset($_SESSION['id_utilisateur']) ? 'logged-in' : 'guest' ?>">

<header class="main-header">
    <a href="index.php?page=accueil" class="logo" style="text-decoration: none; color: inherit;">
        <img src="ressources/images/logo.png" alt="Logo La Bòstia Verda" class="header-logo">
        <strong>La Bòstia Verda</strong>
    </a>

    <input type="checkbox" id="menu-burger" class="menu-burger-input" aria-label="Ouvrir le menu">
    <label for="menu-burger" class="menu-burger-btn" aria-hidden="true">
        <span class="menu-burger-trait"></span>
        <span class="menu-burger-trait"></span>
        <span class="menu-burger-trait"></span>
    </label>

    <nav class="main-nav">
        <ul class="nav-menu">
            <?php
            /**
             * @var string $roleActuel Détermine les privilèges d'affichage du menu.
             * Les utilisateurs non authentifiés reçoivent la valeur temporaire 'Invite' 
             * afin de ne pas interférer avec le statut restrictif de 'Visiteur'.
             */
            $roleActuel = isset($_SESSION['id_utilisateur']) ? ($_SESSION['roleU'] ?? 'Invite') : 'Invite';

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
                    echo '<li class="nav-item"><a href="index.php?page=recoltes" class="nav-link">Historique des récoltes</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=analyses" class="nav-link">Analyses décisionnelles</a></li>';
                    break;
                case 'Tuteur':
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=conseils" class="nav-link">Publier un conseil cultural</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=alertes" class="nav-link">Déclarer une alerte sanitaire</a></li>';
                    break;
                case 'Trésorier':
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=tresorerie" class="nav-link">Finances et contributions</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=stocks" class="nav-link">Stocks de semences</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=stocks_outils" class="nav-link">Stocks d\'outils</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=analyses" class="nav-link">Analyses décisionnelles</a></li>';
                    break;
                case 'Administrateur':
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=admin_tableau_bord" class="nav-link">Supervision globale</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=admin_utilisateurs" class="nav-link">Gestion des utilisateurs</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=analyses" class="nav-link">Analyses décisionnelles</a></li>';
                    break;
                default:
                    echo '<li class="nav-item"><a href="index.php?page=accueil" class="nav-link">Accueil</a></li>';
                    echo '<li class="nav-item"><a href="index.php?page=plantes" class="nav-link">Catalogue des plantes</a></li>';
                    break;
            }
            ?>
        </ul>
    </nav>

    <div class="header-actions">
        <?php if (isset($_SESSION['id_utilisateur'])): ?>
            <a href="index.php?page=deconnexion" class="btn btn-primary">Déconnexion</a>
        <?php else: ?>
            <a href="index.php?page=connexion" class="link-login">Connexion</a>
            <a href="index.php?page=inscription" class="btn btn-primary">S'inscrire</a>
        <?php endif; ?>
    </div>
</header>

<?php
/**
 * Bloc d'affichage d'identité.
 * Si le membre est authentifié, affiche un bandeau d'information cliquable sous la Navbar 
 * détaillant son identité complète (via fonction bdd) et son niveau d'accréditation.
 */
if (isset($_SESSION['id_utilisateur'])):
    $nom_complet = obtenir_nom_complet($bdd);
?>
    <div class="badge-wrapper">
        <a href="index.php?page=profil" class="user-status-badge" title="Accéder à mon profil">
            <span class="status-text">Connecté en tant que</span>
            <span class="status-name"><?= htmlspecialchars($nom_complet) ?></span>
            <span class="status-role"><?= htmlspecialchars($roleActuel) ?></span>
        </a>
    </div>
<?php endif; ?>