<?php
// pages/public/inscription.php

// Si l'utilisateur est déjà connecté, redirection immédiate
if (isset($_SESSION['id_utilisateur'])) {
    header("Location: index.php?page=accueil");
    exit;
}

// --- TRAITEMENT POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validations de base
    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($confirm_password)) {
        $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
        header("Location: index.php?page=inscription&msg=missing");
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
        header("Location: index.php?page=inscription&msg=email_invalid");
        exit;
    }

    if (strlen($mot_de_passe) < 4) {
        $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
        header("Location: index.php?page=inscription&msg=too_short");
        exit;
    }

    if ($mot_de_passe !== $confirm_password) {
        $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
        header("Location: index.php?page=inscription&msg=mismatch");
        exit;
    }

    try {
        // Vérification si l'email existe déjà
        $requeteCheck = $bdd->prepare("SELECT COUNT(*) FROM Utilisateur WHERE email = ?");
        $requeteCheck->execute([$email]);
        if ($requeteCheck->fetchColumn() > 0) {
            $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
            header("Location: index.php?page=inscription&msg=email_taken");
            exit;
        }

        // Hachage et rôle
        $password_hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);
        $role_par_defaut = 'Visiteur';

        // 1. Insertion de l'utilisateur (AVEC RETURNING et SANS beginTransaction)
        $requeteInsert = $bdd->prepare("INSERT INTO Utilisateur (nomU, prenomU, email, mot_de_passe, roleU, date_inscription) VALUES (?, ?, ?, ?, ?, CURRENT_DATE) RETURNING id_utilisateur");
        $requeteInsert->execute([$nom, $prenom, $email, $password_hash, $role_par_defaut]);
        
        $id_utilisateur = $requeteInsert->fetchColumn();

        // 2. Inscription sur la liste d'attente
        $id_parcelle = $bdd->query("SELECT id_parcelle FROM Parcelle ORDER BY id_parcelle LIMIT 1")->fetchColumn();
        if ($id_parcelle && $id_utilisateur) {
            $requeteAttente = $bdd->prepare("INSERT INTO s_inscrire (id_utilisateur, id_parcelle, date_demande, priorite, motivation) VALUES (?, ?, CURRENT_DATE, 1, ?)");
            $requeteAttente->execute([$id_utilisateur, $id_parcelle, "Demande d'inscription au jardin partagé"]);
        }

        // Succès
        unset($_SESSION['register_old']);
        header("Location: index.php?page=inscription&msg=ok");
        exit;

    } catch (PDOException $e) {
        $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
        header("Location: index.php?page=inscription&msg=db_err");
        exit;
    }
}

$titre = "Inscription - La Bòstia Verda";
include 'inclusions/entete.php';

// Messages
$erreur = null;
$success = null;
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok': 
            $success = "Compte créé avec succès ! Vous êtes sur la liste d'attente pour une parcelle."; 
            break;
        case 'missing':
            $erreur = "Veuillez remplir tous les champs.";
            break;
        case 'email_invalid':
            $erreur = "L'adresse email n'est pas valide.";
            break;
        case 'too_short':
            $erreur = "Le mot de passe doit contenir au moins 4 caractères.";
            break;
        case 'mismatch':
            $erreur = "Les mots de passe ne correspondent pas.";
            break;
        case 'email_taken':
            $erreur = "Cet email est déjà utilisé. Veuillez vous connecter.";
            break;
        case 'db_err':
            $erreur = "Une erreur est survenue lors de l'inscription. Réessayez.";
            break;
    }
}

$old = $_SESSION['register_old'] ?? ['nom' => '', 'prenom' => '', 'email' => ''];
?>

<div class="login-wrapper">
    <div class="login-box">
        <img src="ressources/images/logo.png" alt="Logo La Bòstia Verda" class="main-logo">

        <h2>Créer un compte</h2>
        <p>Rejoignez La Bòstia Verda</p>

        <?php if($erreur): ?>
            <div class="alert alert-error"><?= icon('warning') ?> <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="alert alert-success"><?= icon('check') ?> <?= htmlspecialchars($success) ?></div>
            <a href="index.php?page=connexion" class="btn btn-primary" style="display:inline-block; margin-top: 15px;">Se connecter</a>
        <?php else: ?>
            <form method="POST" action="index.php?page=inscription">
                <input type="text" name="nom" placeholder="Votre Nom" required value="<?= htmlspecialchars($old['nom']) ?>">
                <input type="text" name="prenom" placeholder="Votre Prénom" required value="<?= htmlspecialchars($old['prenom']) ?>">
                <input type="email" name="email" placeholder="Votre Email" required value="<?= htmlspecialchars($old['email']) ?>">
                <input type="password" name="password" placeholder="Mot de passe (4 caractères min.)" required minlength="4">
                <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required minlength="4">
                <button type="submit">S'inscrire</button>
            </form>

            <p style="margin-top:25px; font-size:0.95em; color:var(--text-light);">
                Déjà membre ? <a href="index.php?page=connexion" class="link">Se connecter</a>
            </p>
        <?php endif; ?>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>
