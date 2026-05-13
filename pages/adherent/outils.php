<?php
// pages/adherent/outils.php
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");
exiger_role('Adhérent');

$id_utilisateur = $_SESSION['id_utilisateur'];

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // --- Cas 1 : Annulation d'une réservation par son propriétaire ---
    if (isset($_POST['action']) && $_POST['action'] === 'annuler') {
        $id_resa = $_POST['id_reservation'] ?? 0;
        try {
            // On ne peut annuler QUE ses propres réservations qui ne sont pas déjà terminées ou annulées.
            $requete = $bdd->prepare("
                UPDATE Reservation
                SET statutR = 'annulée'
                WHERE id_reservation = ?
                  AND id_utilisateur_effectuer = ?
                  AND statutR IN ('en attente', 'confirmée')
            ");
            $requete->execute([$id_resa, $id_utilisateur]);
            if ($requete->rowCount() > 0) {
                header("Location: index.php?page=outils&msg=cancel_ok");
                exit;
            } else {
                header("Location: index.php?page=outils&msg=cancel_err");
                exit;
            }
        } catch (PDOException $e) {
            header("Location: index.php?page=outils&msg=sql_err");
            exit;
        }
    }

    // --- Cas 2 : Création d'une nouvelle réservation ---
    $outil_id = $_POST['outil_id'] ?? '';
    $debut    = $_POST['debut'] ?? '';
    $fin      = $_POST['fin'] ?? '';

    if (empty($outil_id) || empty($debut) || empty($fin)) {
        header("Location: index.php?page=outils&msg=missing");
        exit;
    }

    if ($fin < $debut) {
        header("Location: index.php?page=outils&msg=date_err");
        exit;
    }

    if ($debut < date('Y-m-d')) {
        header("Location: index.php?page=outils&msg=past_err");
        exit;
    }

    try {
        // Vérification de chevauchement : on ignore les réservations 'annulée' et 'terminée'.
        $requeteCheck = $bdd->prepare("
            SELECT COUNT(*) FROM Reservation
            WHERE id_outil_concerner = ?
              AND statutR IN ('en attente', 'confirmée', 'en cours')
              AND dateD <= ? AND dateF >= ?
        ");
        $requeteCheck->execute([$outil_id, $fin, $debut]);

        if ($requeteCheck->fetchColumn() > 0) {
            header("Location: index.php?page=outils&msg=conflict");
            exit;
        }

        // Vérification que l'outil est bien marqué disponible
        $requeteDispo = $bdd->prepare("SELECT disponibiliteO FROM Outil WHERE id_outil = ?");
        $requeteDispo->execute([$outil_id]);
        $dispo = $requeteDispo->fetchColumn();
        if ($dispo === false || $dispo === 'f' || $dispo === 0 || $dispo === '0') {
            header("Location: index.php?page=outils&msg=unavailable");
            exit;
        }

        // Création en statut 'en attente' : un Administrateur devra confirmer la réservation.
        $bdd->prepare("
            INSERT INTO Reservation (id_outil_concerner, id_utilisateur_effectuer, dateD, dateF, statutR)
            VALUES (?, ?, ?, ?, 'en attente')
        ")->execute([$outil_id, $id_utilisateur, $debut, $fin]);

        header("Location: index.php?page=outils&msg=ok");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?page=outils&msg=sql_err");
        exit;
    }
}

$titre = "Réservation d'outils";
include 'inclusions/entete.php';

// Message à afficher après redirection
$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':          $msg = "<div class='alert alert-success'>" . icon('check') . " Demande de réservation enregistrée. Elle sera confirmée par l'Administrateur.</div>"; break;
        case 'cancel_ok':   $msg = "<div class='alert alert-success'>" . icon('check') . " Votre réservation a bien été annulée.</div>"; break;
        case 'cancel_err':  $msg = "<div class='alert alert-error'>" . icon('warning') . " Réservation introuvable ou déjà passée.</div>"; break;
        case 'missing':     $msg = "<div class='alert alert-error'>" . icon('warning') . " Veuillez remplir tous les champs.</div>"; break;
        case 'date_err':    $msg = "<div class='alert alert-error'>" . icon('warning') . " La date de fin ne peut pas être antérieure à la date de début.</div>"; break;
        case 'past_err':    $msg = "<div class='alert alert-error'>" . icon('warning') . " Impossible de réserver dans le passé.</div>"; break;
        case 'conflict':    $msg = "<div class='alert alert-error'>" . icon('warning') . " Cet outil est déjà réservé sur ce créneau.</div>"; break;
        case 'unavailable': $msg = "<div class='alert alert-error'>" . icon('warning') . " Cet outil n'est plus disponible.</div>"; break;
        case 'sql_err':     $msg = "<div class='alert alert-error'>" . icon('warning') . " Une erreur SQL est survenue.</div>"; break;
    }
}

$outils = $bdd->query("SELECT * FROM Outil ORDER BY nomO")->fetchAll();

// Planning global des réservations à venir (toutes confondues, sauf annulées/terminées)
$reservations = $bdd->query("
    SELECT r.*, o.nomO, u.prenomU
    FROM Reservation r
    JOIN Outil o ON r.id_outil_concerner = o.id_outil
    JOIN Utilisateur u ON r.id_utilisateur_effectuer = u.id_utilisateur
    WHERE r.dateF >= CURRENT_DATE
      AND r.statutR IN ('en attente', 'confirmée', 'en cours')
    ORDER BY r.dateD ASC
")->fetchAll();

// Mes réservations à venir (pour pouvoir les annuler)
$requeteMine = $bdd->prepare("
    SELECT r.*, o.nomO
    FROM Reservation r
    JOIN Outil o ON r.id_outil_concerner = o.id_outil
    WHERE r.id_utilisateur_effectuer = ?
      AND r.dateF >= CURRENT_DATE
      AND r.statutR IN ('en attente', 'confirmée', 'en cours')
    ORDER BY r.dateD ASC
");
$requeteMine->execute([$id_utilisateur]);
$mes_reservations = $requeteMine->fetchAll();
?>

<div class="container">
    <div class="header">
        <h1 class="flex-title">
            <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
            Outils Partagés
        </h1>
        <p>Réservez un outil pour votre parcelle ou consultez les réservations à venir.</p>
    </div>

    <?= $msg ?>

    <div class="outils-wrapper">
        <div class="form-card">
            <h3>Réserver un outil</h3>
            <form method="POST" action="index.php?page=outils">
                <label for="outil_id">Outil :</label>
                <select name="outil_id" id="outil_id" required>
                    <option value="" disabled selected>-- Choisir un outil --</option>
                    <?php foreach($outils as $o):
                        $estDispo = ($o['disponibiliteo'] === true || $o['disponibiliteo'] === 't' || $o['disponibiliteo'] == 1);
                    ?>
                        <option value="<?= $o['id_outil'] ?>" <?= !$estDispo ? 'disabled' : '' ?>>
                            <?= htmlspecialchars($o['nomo']) ?> (État : <?= htmlspecialchars($o['etat_physique']) ?>)<?= !$estDispo ? ' — INDISPONIBLE' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <label for="debut">Date de début :</label>
                <input type="date" name="debut" id="debut" required min="<?= date('Y-m-d') ?>">
                <label for="fin">Date de fin :</label>
                <input type="date" name="fin" id="fin" required min="<?= date('Y-m-d') ?>">
                <button type="submit">Demander la réservation</button>
                <p class="text-muted text-italic" style="font-size: 0.85rem; margin-top: 0.5rem;">
                    Votre demande sera soumise à validation de l'Administrateur.
                </p>
            </form>
        </div>

        <div class="card">
            <h3 class="flex-title">
                <svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                Mes réservations à venir
            </h3>
            <?php if (empty($mes_reservations)): ?>
                <p class="text-muted text-italic">Vous n'avez aucune réservation en cours.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr><th>Outil</th><th>Du</th><th>Au</th><th>Statut</th><th>Action</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach($mes_reservations as $r): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['nomo']) ?></strong></td>
                                <td><?= date("d/m/Y", strtotime($r['dated'])) ?></td>
                                <td><?= date("d/m/Y", strtotime($r['datef'])) ?></td>
                                <td>
                                    <?php
                                        $cls = match($r['statutr']) {
                                            'en attente' => 'badge-recolte',
                                            'confirmée'  => 'badge-croissance',
                                            'en cours'   => 'badge-croissance',
                                            default      => 'badge-terminee',
                                        };
                                    ?>
                                    <span class="badge <?= $cls ?>"><?= htmlspecialchars(ucfirst($r['statutr'])) ?></span>
                                </td>
                                <td>
                                    <form method="POST" action="index.php?page=outils" onsubmit="return confirm('Annuler cette réservation ?');" style="margin:0;">
                                        <input type="hidden" name="action" value="annuler">
                                        <input type="hidden" name="id_reservation" value="<?= $r['id_reservation'] ?>">
                                        <button type="submit" class="btn-small btn-outline-danger">Annuler</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-3">
        <h3 class="flex-title">
            <svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
            Planning global des réservations
        </h3>
        <table>
            <thead>
                <tr><th>Outil</th><th>Membre</th><th>Du</th><th>Au</th><th>Statut</th></tr>
            </thead>
            <tbody>
                <?php if (empty($reservations)): ?>
                    <tr><td colspan="5" class="text-center text-muted">Aucune réservation à venir.</td></tr>
                <?php else: ?>
                    <?php foreach($reservations as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['nomo']) ?></strong></td>
                            <td><?= htmlspecialchars($r['prenomu']) ?></td>
                            <td><?= date("d/m/Y", strtotime($r['dated'])) ?></td>
                            <td><?= date("d/m/Y", strtotime($r['datef'])) ?></td>
                            <td>
                                <?php
                                    $cls = match($r['statutr']) {
                                        'en attente' => 'badge-recolte',
                                        'confirmée'  => 'badge-croissance',
                                        'en cours'   => 'badge-croissance',
                                        default      => 'badge-terminee',
                                    };
                                ?>
                                <span class="badge <?= $cls ?>"><?= htmlspecialchars(ucfirst($r['statutr'])) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>
