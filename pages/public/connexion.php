<?php
// pages/public/login.php

$titre = "Connexion - La Bòstia Verda";

// Si on est déjà connecté, on va au jardin
if (isset($_SESSION['id_utilisateur'])) {
    header("Location: index.php?page=accueil");
    exit;
}

$erreur = null;

// Traitement de la soumission du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $mot_de_passe = $_POST['password'];

    if (empty($email) || empty($mot_de_passe)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        // On cherche l'utilisateur
        $requete = $bdd->prepare("SELECT * FROM Utilisateur WHERE email = ?");
        $requete->execute([$email]);
        $utilisateur = $requete->fetch();

        // Vérification du mot de passe
        // On utilise password_verify pour comparer le mot de passe fourni avec le hachage en BDD
        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])) {
            // Connexion réussie : On remplit la session
            $_SESSION['id_utilisateur'] = $utilisateur['id_utilisateur'];
            $_SESSION['nom']     = $utilisateur['prenomu']; // Prénom pour le header
            $_SESSION['roleU']   = $utilisateur['roleu'];   // Clé unifiée utilisée par toute l'app
            $_SESSION['role']    = $utilisateur['roleu'];   // Alias historique (conservé pour rétro-compat)

            // Redirection selon le rôle
            switch ($utilisateur['roleu']) {
                case 'Visiteur':
                    header("Location: index.php?page=cultures"); // Le visiteur va sur son dossier d'attente
                    break;
                case 'Administrateur':
                    header("Location: index.php?page=admin_tableau_bord");
                    break;
                case 'Responsable':
                    header("Location: index.php?page=tableau_bord"); // A adapter plus tard
                    break;
                case 'Tuteur':
                case 'Trésorier':
                    header("Location: index.php?page=accueil"); // En attendant leurs pages spécifiques
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