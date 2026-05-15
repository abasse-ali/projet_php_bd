<?php
/**
 * pages/public/connexion.php
 *
 * Page de connexion de l'application.
 * Permet l'authentification sécurisée des utilisateurs via leur email et mot de passe,
 * l'initialisation de leur session globale et une redirection dynamique vers l'espace
 * dédié correspondant à leur rôle.
 */

$titre = "Connexion - La Bòstia Verda";

// Si l'utilisateur est déjà connecté, redirection immédiate vers l'accueil
if (isset($_SESSION['id_utilisateur'])) {
    header("Location: index.php?page=accueil");
    exit;
}

$erreur = null;

// Traitement de la soumission du formulaire d'authentification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['password'];

    if (empty($email) || empty($mot_de_passe)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        // Recherche de l'utilisateur correspondant à l'email fourni
        $requete = $bdd->prepare("SELECT * FROM Utilisateur WHERE email = ?");
        $requete->execute([$email]);
        $utilisateur = $requete->fetch();

        // Comparaison et vérification du mot de passe en clair avec le hachage stocké en BDD
        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            // Initialisation des variables de session en cas de succès
            $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
            $_SESSION['nom']     = $utilisateur['prenomu']; // Prénom utilisé pour l'en-tête
            $_SESSION['roleU']   = $utilisateur['roleu'];   // Clé unifiée pour le contrôle d'accès
            $_SESSION['role']    = $utilisateur['roleu'];   // Alias historique pour rétro-compatibilité

            // Redirection conditionnelle et dynamique selon le rôle de l'utilisateur
            switch ($utilisateur['roleu']) {
                case 'Visiteur':
                    header("Location: index.php?page=cultures"); // Le visiteur va sur son dossier d'attente
                    break;
                case 'Administrateur':
                    header("Location: index.php?page=admin_tableau_bord");
                    break;
                case 'Responsable':
                    header("Location: index.php?page=tableau_bord");
                    break;
                case 'Tuteur':
                case 'Trésorier':
                    header("Location: index.php?page=accueil"); // Redirection transitoire en attendant leurs pages spécifiques
                    break;
                case 'Adhérent':
                default:
                    header("Location: index.php?page=cultures");
                    break;
            }
            exit;
        } else {
            $erreur = "Email ou mot de passe incorrect.";
        }
    }
}
?>
<?php include 'inclusions/entete.php'; ?>

<div class="login-wrapper">
    <div class="login-box">
        <img src="ressources/images/logo.png" alt="Logo La Bòstia Verda" class="main-logo">

        <h2>La Bòstia Verda</h2>
        <p>Connectez-vous à votre espace</p>

        <?php if($erreur): ?>
            <div class="alert alert-error"><?= icon('warning') ?> <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Votre Email" required>
            <input type="password" name="password" placeholder="Mot de passe" required>
            <button type="submit">Se connecter</button>
        </form>

        <p style="margin-top:25px; font-size:0.95em; color:var(--text-light);">
            Pas encore membre ? <a href="index.php?page=inscription" class="link">Créer un compte</a>
        </p>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>