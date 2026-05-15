<?php
/**
 * pages/administrateur/utilisateurs.php
 *
 * Module d'administration pour la gestion globale des utilisateurs.
 * Permet aux administrateurs de lister l'ensemble des membres inscrits et de modifier 
 * leurs rôles applicatifs. Gère les cas de sécurité (interdiction d'auto-rétrogradation) 
 * et l'ajustement dynamique des contraintes de vérification (CHECK) de la base de données.
 */

// Sécurisation stricte de l'accès à l'espace d'administration
if (!isset($_SESSION['id_utilisateur']) || ($_SESSION['roleU'] ?? '') !== 'Administrateur') {
    die("Accès interdit : Réservé aux Administrateurs.");
}

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'], $_POST['new_role'])) {
    $new_role = $_POST['new_role'];
    $target_id = (int) $_POST['user_id'];

    // Sécurité : Empêcher l'administrateur connecté de révoquer ses propres droits d'accès
    if ($target_id === (int) $_SESSION['id_utilisateur'] && $new_role !== 'Administrateur') {
        header("Location: index.php?page=admin_utilisateurs&msg=self_demote");
        exit;
    }

    // Validation applicative du rôle sélectionné par rapport aux rôles définis dans le système
    $roles_autorises = ['Visiteur', 'Adhérent', 'Tuteur', 'Trésorier', 'Responsable', 'Administrateur'];
    if (!in_array($new_role, $roles_autorises, true)) {
        header("Location: index.php?page=admin_utilisateurs&msg=role_invalid");
        exit;
    }

    try {
        // Tentative de mise à jour du rôle utilisateur en base de données
        $requeteUpdate = $bdd->prepare("UPDATE Utilisateur SET roleU = ? WHERE id_utilisateur = ?");
        $requeteUpdate->execute([$new_role, $target_id]);
        header("Location: index.php?page=admin_utilisateurs&msg=ok");
        exit;
    } catch (PDOException $e) {
        // Traitement spécifique de l'erreur SQLSTATE 23514 (Violation de contrainte CHECK)
        if ($e->getCode() == '23514') {
            try {
                // Auto-correction BDD : Reconstruction de la contrainte CHECK pour l'aligner avec la liste applicative
                $bdd->exec("ALTER TABLE Utilisateur DROP CONSTRAINT IF EXISTS utilisateur_roleu_check");
                $bdd->exec("ALTER TABLE Utilisateur ADD CONSTRAINT utilisateur_roleu_check CHECK (roleU IN ('Visiteur', 'Adhérent', 'Tuteur', 'Trésorier', 'Responsable', 'Administrateur'))");
                
                // Nouvelle tentative d'exécution après réparation structurelle de la base
                $requeteUpdate->execute([$new_role, $target_id]);
                header("Location: index.php?page=admin_utilisateurs&msg=ok_fixed");
                exit;
            } catch (PDOException $e2) {
                header("Location: index.php?page=admin_utilisateurs&msg=db_err");
                exit;
            }
        }
        header("Location: index.php?page=admin_utilisateurs&msg=db_err");
        exit;
    }
}

$titre = "Gestion des Utilisateurs - Admin";
include 'inclusions/entete.php';

// Interprétation des codes de retour GET pour l'affichage des alertes utilisateur
$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':
            $msg = "<div class='alert alert-success'>" . icon('check') . " Le rôle a été mis à jour avec succès.</div>";
            break;
        case 'ok_fixed':
            $msg = "<div class='alert alert-success'>" . icon('check') . " Rôle mis à jour (un correctif BDD a été appliqué automatiquement sur la contrainte CHECK).</div>";
            break;
        case 'self_demote':
            $msg = "<div class='alert alert-error'>" . icon('warning') . " Vous ne pouvez pas vous retirer vous-même le rôle d'Administrateur.</div>";
            break;
        case 'role_invalid':
            $msg = "<div class='alert alert-error'>" . icon('warning') . " Rôle inconnu.</div>";
            break;
        case 'db_err':
            $msg = "<div class='alert alert-error'>" . icon('warning') . " Erreur BDD lors de la mise à jour.</div>";
            break;
    }
}

/**
 * @var array $utilisateurs Liste de tous les utilisateurs enregistrés en base, classés par ordre alphabétique.
 */
$utilisateurs = $bdd->query("SELECT id_utilisateur, nomU, prenomU, email, roleU FROM Utilisateur ORDER BY nomU")->fetchAll();
?>

<div class="container">
    <div class="header">
        <div>
            <h1><?= icon('users') ?> Gestion des Utilisateurs</h1>
            <p>Valider les comptes et assigner les rôles (Adhérent, Tuteur, etc.)</p>
        </div>
    </div>

    <div class="card">
        <?= $msg ?>
        <table>
            <thead>
                <tr>
                    <th>Nom & Prénom</th>
                    <th>Email</th>
                    <th>Rôle assigné</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($utilisateurs as $u): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($u['nomu'] . ' ' . $u['prenomu']) ?></strong></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <form method="POST" class="form-inline">
                            <input type="hidden" name="user_id" value="<?= $u['id_utilisateur'] ?>">
                            <select name="new_role">
                                <?php foreach (['Visiteur', 'Adhérent', 'Tuteur', 'Trésorier', 'Responsable', 'Administrateur'] as $role): ?>
                                    <option value="<?= $role ?>" <?= $u['roleu'] === $role ? 'selected' : '' ?>><?= $role ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-primary" style="padding: 0.4rem 1rem;">Mettre à jour</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>