<?php
// pages/admin/ressources.php
if (!isset($_SESSION['id_utilisateur']) || ($_SESSION['roleU'] ?? '') !== 'Administrateur') {
    die("Accès interdit : Réservé aux Administrateurs.");
}

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_reservation'])) {
    $action = $_POST['action'] ?? '';
    $id_resa = $_POST['id_reservation'];
    try {
        if ($action === 'annuler') {
            $requete = $bdd->prepare("UPDATE Reservation SET statutR = 'annulée' WHERE id_reservation = ?");
            $requete->execute([$id_resa]);
            header("Location: index.php?page=admin_ressources&msg=cancel_ok");
            exit;
        } elseif ($action === 'confirmer') {
            $requete = $bdd->prepare("UPDATE Reservation SET statutR = 'confirmée' WHERE id_reservation = ? AND statutR = 'en attente'");
            $requete->execute([$id_resa]);
            header("Location: index.php?page=admin_ressources&msg=confirm_ok");
            exit;
        }
    } catch (PDOException $e) {
        header("Location: index.php?page=admin_ressources&msg=err");
        exit;
    }
}

$titre = "Ressources & Logistique - Admin";
include 'inclusions/entete.php';
$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'cancel_ok':  $msg = "<div class='alert alert-success'>" . icon('check') . " Réservation annulée.</div>"; break;
        case 'confirm_ok': $msg = "<div class='alert alert-success'>" . icon('check') . " Réservation confirmée. L'adhérent en sera informé.</div>"; break;
        case 'err':        $msg = "<div class='alert alert-error'>" . icon('warning') . " Une erreur est survenue.</div>"; break;
    }
}

// Récupération de toutes les réservations actives
$reservations = $bdd->query("
    SELECT r.id_reservation, r.dateD, r.dateF, r.statutR, o.nomO, u.nomU, u.prenomU 
    FROM Reservation r
    JOIN Outil o ON r.id_outil_concerner = o.id_outil
    JOIN Utilisateur u ON r.id_utilisateur_effectuer = u.id_utilisateur
    WHERE r.statutR IN ('en attente', 'confirmée', 'en cours')
    ORDER BY r.dateD ASC
")->fetchAll();
?>

<div class="container">
    <div class="header">
        <div>
            <h1><?= icon('tools') ?> Ressources & Logistique</h1>
            <p>Contrôle absolu sur le matériel partagé. Gérez les conflits d'agenda.</p>
        </div>
    </div>

    <div class="card">
        <?= $msg ?>
        <h3>Calendrier Global des Réservations Actives</h3>
        <table>
            <thead>
                <tr>
                    <th>Outil</th>
                    <th>Emprunteur</th>
                    <th>Période</th>
                    <th>Statut</th>
                    <th>Action Admin</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($reservations)): ?>
                    <tr><td colspan="5">Aucune réservation active.</td></tr>
                <?php else: ?>
                    <?php foreach($reservations as $r):
                        $cls = match($r['statutr']) {
                            'en attente' => 'badge-recolte',
                            'confirmée'  => 'badge-croissance',
                            'en cours'   => 'badge-croissance',
                            default      => 'badge-terminee',
                        };
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($r['nomo']) ?></strong></td>
                        <td><?= htmlspecialchars($r['prenomu'] . ' ' . $r['nomu']) ?></td>
                        <td>Du <?= date("d/m/Y", strtotime($r['dated'])) ?> au <?= date("d/m/Y", strtotime($r['datef'])) ?></td>
                        <td><span class="badge <?= $cls ?>"><?= htmlspecialchars(ucfirst($r['statutr'])) ?></span></td>
                        <td style="display:flex; gap:0.5rem;">
                            <?php if ($r['statutr'] === 'en attente'): ?>
                                <form method="POST" style="margin: 0;">
                                    <input type="hidden" name="action" value="confirmer">
                                    <input type="hidden" name="id_reservation" value="<?= $r['id_reservation'] ?>">
                                    <button type="submit" class="btn-small" style="padding: 0.3rem 0.8rem; width: auto;">Confirmer</button>
                                </form>
                            <?php endif; ?>
                            <form method="POST" style="margin: 0;" onsubmit="return confirm('Annuler cette réservation ?');">
                                <input type="hidden" name="action" value="annuler">
                                <input type="hidden" name="id_reservation" value="<?= $r['id_reservation'] ?>">
                                <button type="submit" class="btn-outline-danger" style="padding: 0.3rem 0.8rem; width: auto;">Annuler</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>