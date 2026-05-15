<?php
/**
 * pages/commun/profil.php
 *
 * Page de gestion du profil utilisateur.
 * Permet à tout membre connecté de mettre à jour ses informations personnelles
 * (Nom, Prénom, Email) et de modifier son mot de passe de manière sécurisée.
 * Applique le pattern PRG (Post-Redirect-Get) et met à jour la session active.
 */

// Sécurisation de l'accès à la page
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");

/**
 * @var int $id_utilisateur Identifiant unique de l'utilisateur connecté.
 */
$id_utilisateur = $_SESSION['id_utilisateur'];

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données du formulaire
    $nom              = trim($_POST['nom'] ?? '');
    $prenom           = trim($_POST['prenom'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password     = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validation 1 : Vérification des champs obligatoires
    if (empty($nom) || empty($prenom) || empty($email)) {
        header("Location: index.php?page=profil&msg=missing");
        exit;
    }

    // Validation 2 : Format de l'adresse email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: index.php?page=profil&msg=email_invalid");
        exit;
    }

    try {
        // 1. Vérification de l'unicité du nouvel email (en excluant le compte actuel)
        $requeteCheck = $bdd->prepare("SELECT COUNT(*) FROM Utilisateur WHERE email = ? AND id_utilisateur != ?");
        $requeteCheck->execute([$email, $id_utilisateur]);
        if ($requeteCheck->fetchColumn() > 0) {
            header("Location: index.php?page=profil&msg=email_taken");
            exit;
        }

        // 2. Récupération du hachage du mot de passe actuel pour vérification de sécurité
        $requeteCur = $bdd->prepare("SELECT mot_de_passe FROM Utilisateur WHERE id_utilisateur = ?");
        $requeteCur->execute([$id_utilisateur]);
        $current_hash = $requeteCur->fetchColumn();

        // 3. Traitement de la modification du mot de passe (si les champs sont complétés)
        $update_password = false;
        if (!empty($new_password) || !empty($confirm_password)) {
            if (empty($current_password)) {
                header("Location: index.php?page=profil&msg=need_current");
                exit;
            }
            if (!password_verify($current_password, $current_hash)) {
                header("Location: index.php?page=profil&msg=wrong_current");
                exit;
            }
            if ($new_password !== $confirm_password) {
                header("Location: index.php?page=profil&msg=mismatch");
                exit;
            }
            if (strlen($new_password) < 4) {
                header("Location: index.php?page=profil&msg=too_short");
                exit;
            }
            $update_password = true;
        }

        // 4. Mise à jour des informations de base (Nom, Prénom, Email)
        $requeteUpdate = $bdd->prepare("UPDATE Utilisateur SET nomU = ?, prenomU = ?, email = ? WHERE id_utilisateur = ?");
        $requeteUpdate->execute([$nom, $prenom, $email, $id_utilisateur]);

        // 5. Application du nouveau mot de passe si le changement a été validé
        if ($update_password) {
            $new_hash = password_hash($new_password, PASSWORD_BCRYPT);
            $bdd->prepare("UPDATE Utilisateur SET mot_de_passe = ? WHERE id_utilisateur = ?")->execute([$new_hash, $id_utilisateur]);
        }

        // 6. Synchronisation du prénom en session pour actualiser l'affichage dans le header
        $_SESSION['nom'] = $prenom;

        $code = $update_password ? 'ok_pwd' : 'ok';
        header("Location: index.php?page=profil&msg=$code");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?page=profil&msg=err");
        exit;
    }
}

$titre = "Mon Profil";
include 'inclusions/entete.php';

// Traitement et formatage des messages d'état retournés par les requêtes GET
$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':            $msg = "<div class='alert alert-success'>" . icon('check') . " Vos informations ont été mises à jour.</div>"; break;
        case 'ok_pwd':        $msg = "<div class='alert alert-success'>" . icon('check') . " Vos informations et votre mot de passe ont été mis à jour.</div>"; break;
        case 'missing':       $msg = "<div class='alert alert-error'>" . icon('warning') . " Les champs Nom, Prénom et Email sont obligatoires.</div>"; break;
        case 'email_invalid': $msg = "<div class='alert alert-error'>" . icon('warning') . " L'adresse email n'est pas valide.</div>"; break;
        case 'email_taken':   $msg = "<div class='alert alert-error'>" . icon('warning') . " Cet email est déjà utilisé par un autre compte.</div>"; break;
        case 'need_current':  $msg = "<div class='alert alert-error'>" . icon('warning') . " Pour changer votre mot de passe, vous devez saisir votre mot de passe actuel.</div>"; break;
        case 'wrong_current': $msg = "<div class='alert alert-error'>" . icon('warning') . " Mot de passe actuel incorrect.</div>"; break;
        case 'mismatch':      $msg = "<div class='alert alert-error'>" . icon('warning') . " La confirmation ne correspond pas au nouveau mot de passe.</div>"; break;
        case 'too_short':     $msg = "<div class='alert alert-error'>" . icon('warning') . " Le nouveau mot de passe doit contenir au moins 4 caractères.</div>"; break;
        case 'err':           $msg = "<div class='alert alert-error'>" . icon('warning') . " Une erreur est survenue lors de la mise à jour.</div>"; break;
    }
}

/**
 * @var array $utilisateur Contient les informations à jour du membre pour pré-remplir les champs HTML.
 */
$requete = $bdd->prepare("SELECT nomU, prenomU, email, roleU FROM Utilisateur WHERE id_utilisateur = ?");
$requete->execute([$id_utilisateur]);
$utilisateur = $requete->fetch();
?>

<div class="container">
    <div class="header">
        <div>
            <div style="display: flex; align-items: center; gap: 10px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width: 28px; height: 28px; color: var(--color-primary-dark);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <h1 style="margin: 0;">Mon Profil</h1>
            </div>
            <p>Gérez vos informations personnelles et vos paramètres de sécurité.
                <span class="badge badge-croissance" style="margin-left:0.5rem;"><?= htmlspecialchars($utilisateur['roleu']) ?></span>
            </p>
        </div>
    </div>

    <div class="form-card" style="max-width: 600px; margin: 0 auto;">
        <?= $msg ?>
        <form method="POST" action="index.php?page=profil">
            <h3 style="margin-bottom: 1rem; border-bottom: 1px solid var(--color-bg-gradient-end); padding-bottom: 0.5rem; color: var(--color-primary-dark);">Informations Personnelles</h3>

            <label for="nom">Nom :</label>
            <input type="text" name="nom" id="nom" value="<?= htmlspecialchars($utilisateur['nomu']) ?>" required>

            <label for="prenom">Prénom :</label>
            <input type="text" name="prenom" id="prenom" value="<?= htmlspecialchars($utilisateur['prenomu']) ?>" required>

            <label for="email">Email :</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($utilisateur['email']) ?>" required>

            <h3 style="margin-top: 2rem; margin-bottom: 1rem; border-bottom: 1px solid var(--color-bg-gradient-end); padding-bottom: 0.5rem; color: var(--color-primary-dark);">Changer mon mot de passe</h3>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 1rem; font-style: italic;">Laissez ces champs vides si vous ne souhaitez pas modifier votre mot de passe actuel.</p>

            <label for="current_password">Mot de passe actuel :</label>
            <input type="password" name="current_password" id="current_password" placeholder="Indispensable pour changer le mot de passe" autocomplete="current-password">

            <label for="new_password">Nouveau mot de passe :</label>
            <input type="password" name="new_password" id="new_password" placeholder="Saisir un nouveau mot de passe" autocomplete="new-password">

            <label for="confirm_password">Confirmer le nouveau mot de passe :</label>
            <input type="password" name="confirm_password" id="confirm_password" placeholder="Ressaisir le nouveau mot de passe" autocomplete="new-password">

            <button type="submit" style="margin-top: 1.5rem; font-size: 1.1rem;">Enregistrer les modifications</button>
        </form>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>