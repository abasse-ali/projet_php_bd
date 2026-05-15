<?php
// pages/public/inscription.php

// Si l'utilisateur est déjà connecté, redirection immédiate vers la page d'accueil
if (isset($_SESSION['id_utilisateur'])) {
    header("Location: index.php?page=accueil");
    exit;
}

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données du formulaire
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $mot_de_passe = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation 1 : Vérification des champs requis
    if (empty($nom) || empty($prenom) || empty($email) || empty($mot_de_passe) || empty($confirm_password)) {
        $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
        header("Location: index.php?page=inscription&msg=missing");
        exit;
    }

    // Validation 2 : Format de l'adresse email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
        header("Location: index.php?page=inscription&msg=email_invalid");
        exit;
    }

    // Validation 3 : Longueur minimale du mot de passe
    if (strlen($mot_de_passe) < 4) {
        $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
        header("Location: index.php?page=inscription&msg=too_short");
        exit;
    }

    // Validation 4 : Correspondance des deux mots de passe
    if ($mot_de_passe !== $confirm_password) {
        $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
        header("Location: index.php?page=inscription&msg=mismatch");
        exit;
    }

    try {
        // Validation 5 : Vérification de l'unicité de l'adresse email
        $requeteCheck = $bdd->prepare("SELECT COUNT(*) FROM Utilisateur WHERE email = ?");
        $requeteCheck->execute([$email]);
        if ($requeteCheck->fetchColumn() > 0) {
            $_SESSION['register_old'] = compact('nom', 'prenom', 'email');
            header("Location: index.php?page=inscription&msg=email_taken");
            exit;
        }

        // Préparation des données d'insertion (hachage sécurisé et rôle par défaut)
        $password_hash = password_hash($mot_de_passe, PASSWORD_BCRYPT);
        $role_par_defaut = 'Visiteur';

        // --- MODE DEBUG : ON DÉSACTIVE LES TRANSACTIONS ---
        // $bdd->beginTransaction();

        // Insertion du nouvel utilisateur (RETURNING id_utilisateur)
        $requeteInsert = $bdd->prepare("INSERT INTO Utilisateur (nomU, prenomU, email, mot_de_passe, roleU, date_inscription) VALUES (?, ?, ?, ?, ?, CURRENT_DATE) RETURNING id_utilisateur");
        $requeteInsert->execute([$nom, $prenom, $email, $password_hash, $role_par_defaut]);
        
        $id_utilisateur = $requeteInsert->fetchColumn();

        // Inscription sur la liste d'attente
        $id_parcelle = $bdd->query("SELECT id_parcelle FROM Parcelle ORDER BY id_parcelle LIMIT 1")->fetchColumn();
        if ($id_parcelle) {
            $requeteAttente = $bdd->prepare("INSERT INTO s_inscrire (id_utilisateur, id_parcelle, date_demande, priorite, motivation) VALUES (?, ?, CURRENT_DATE, 1, ?)");
            $requeteAttente->execute([$id_utilisateur, $id_parcelle, "Demande d'inscription au jardin partagé"]);
        }

        // $bdd->commit();
        unset($_SESSION['register_old']);
        
        // --- MODE DEBUG : ON ARRÊTE LE SCRIPT SI TOUT MARCHE ---
        die("SUCCÈS ! L'utilisateur a été inséré avec l'ID : " . $id_utilisateur);

    } catch (PDOException $e) {
        // --- MODE DEBUG : ON AFFICHE L'ERREUR EXACTE ---
        die("VOICI LA VRAIE ERREUR SQL : " . $e->getMessage());
    }
}

$titre = "Inscription - La Bòstia Verda";
include 'inclusions/entete.php';

// Cartographie et traduction des messages d'erreur ou de succès post-redirection
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
