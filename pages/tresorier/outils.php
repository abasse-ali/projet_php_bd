<?php
// pages/tresorier/outils.php
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");
exiger_role('Trésorier');

// États possibles pour un outil (cohérent avec la contrainte CHECK BDD)
$ETATS_VALIDES = ['Opérationnel', 'Abîmé', 'En réparation', 'HS'];

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'add') {
        $nomO = trim($_POST['nomO'] ?? '');
        $etat = $_POST['etat_physique'] ?? '';

        if (empty($nomO) || !in_array($etat, $ETATS_VALIDES, true)) {
            header("Location: index.php?page=stocks_outils&msg=invalid");
            exit;
        }

        try {
            $requete = $bdd->prepare("INSERT INTO Outil (nomO, etat_physique, disponibiliteO) VALUES (?, ?, TRUE)");
            $requete->execute([$nomO, $etat]);
            header("Location: index.php?page=stocks_outils&msg=add_ok");
            exit;
        } catch (PDOException $e) {
            header("Location: index.php?page=stocks_outils&msg=db_err");
            exit;
        }

    } elseif ($_POST['action'] === 'update_etat') {
        $id_outil    = (int) ($_POST['id_outil'] ?? 0);
        $nouvel_etat = $_POST['nouvel_etat'] ?? '';

        if ($id_outil <= 0 || !in_array($nouvel_etat, $ETATS_VALIDES, true)) {
            header("Location: index.php?page=stocks_outils&msg=invalid");
            exit;
        }

        // Si l'outil n'est plus opérationnel, on coupe la disponibilité automatiquement.
        $dispo = ($nouvel_etat === 'Opérationnel');

        try {
            $requete = $bdd->prepare("UPDATE Outil SET etat_physique = ?, disponibiliteO = ? WHERE id_outil = ?");
            $requete->execute([$nouvel_etat, $dispo, $id_outil]);
            header("Location: index.php?page=stocks_outils&msg=update_ok");
            exit;
        } catch (PDOException $e) {
            header("Location: index.php?page=stocks_outils&msg=db_err");
            exit;
        }
    }
}

$titre = "Gestion des Outils";
include 'inclusions/entete.php';

$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'add_ok':    $msg = "<div class='alert alert-success'>" . icon('check') . " Nouvel outil ajouté au cabanon.</div>"; break;
        case 'update_ok': $msg = "<div class='alert alert-success'>" . icon('check') . " État de l'outil mis à jour.</div>"; break;
        case 'invalid':   $msg = "<div class='alert alert-error'>" . icon('warning') . " Données invalides (nom manquant ou état non reconnu).</div>"; break;
        case 'db_err':    $msg = "<div class='alert alert-error'>" . icon('warning') . " Erreur BDD.</div>"; break;
    }
}

$outils = $bdd->query("SELECT * FROM Outil ORDER BY nomO")->fetchAll();

// Réservations actives qui chevauchent aujourd'hui (donc outils actuellement empruntés)
$reservations_en_cours = [];
$requete_res = $bdd->query("
    SELECT r.id_outil_concerner, r.dateD, r.dateF, u.prenomU
    FROM Reservation r
    JOIN Utilisateur u ON r.id_utilisateur_effectuer = u.id_utilisateur
    WHERE r.statutR IN ('confirmée', 'en cours')
      AND CURRENT_DATE BETWEEN r.dateD AND r.dateF
");
foreach ($requete_res->fetchAll(PDO::FETCH_ASSOC) as $res) {
    $reservations_en_cours[$res['id_outil_concerner']] = $res;
}
?>

<div class="container">
    <div class="header">
        <div>
            <h1 class="flex-title">
                <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"></path></svg>
                Stocks du Matériel & Outils
            </h1>
            <p>Enregistrez les outils et gérez leur état physique.</p>
        </div>
    </div>

    <?= $msg ?>

    <div class="dashboard-grid">
        <div class="main-column">
            <div class="card">
                <h3>Inventaire de la Cabane à Outils</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Outil</th>
                            <th>État physique</th>
                            <th>Localisation</th>
                            <th>Disponibilité</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($outils)): ?>
                            <tr><td colspan="4" class="text-center text-muted">Aucun outil enregistré.</td></tr>
                        <?php else: ?>
                            <?php foreach($outils as $o):
                                $reservation_actuelle = $reservations_en_cours[$o['id_outil']] ?? null;
                                $est_operationnel = ($o['etat_physique'] === 'Opérationnel');
                                $est_emprunte = $reservation_actuelle && $est_operationnel;
                            ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($o['nomo']) ?></strong></td>
                                    <td>
                                        <?php if ($est_emprunte): ?>
                                            <span class="text-muted" title="En cours d'emprunt">
                                                <?= htmlspecialchars($o['etat_physique']) ?>
                                                <small>(emprunté, modification verrouillée)</small>
                                            </span>
                                        <?php else: ?>
                                            <form method="POST" action="index.php?page=stocks_outils" class="form-inline" style="margin:0;">
                                                <input type="hidden" name="action" value="update_etat">
                                                <input type="hidden" name="id_outil" value="<?= $o['id_outil'] ?>">
                                                <select name="nouvel_etat" onchange="this.form.submit()" style="padding: 0.3rem; margin: 0; width: auto; font-size: 0.9rem;">
                                                    <?php foreach ($ETATS_VALIDES as $e): ?>
                                                        <option value="<?= htmlspecialchars($e) ?>" <?= $o['etat_physique'] === $e ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($est_emprunte): ?>
                                            Chez un adhérent
                                            <br><small class="text-muted">Par <?= htmlspecialchars($reservation_actuelle['prenomu']) ?> jusqu'au <?= date("d/m/Y", strtotime($reservation_actuelle['datef'])) ?></small>
                                        <?php else: ?>
                                            Au cabanon
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($est_emprunte || !$est_operationnel): ?>
                                            <span class="badge" style="background:#fee2e2; color:#991b1b;">Indisponible</span>
                                        <?php else: ?>
                                            <span class="badge badge-croissance">Disponible</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="side-column">
            <div class="form-card" style="position: sticky; top: 100px;">
                <h3 style="color: var(--color-primary-dark); margin-bottom: 1rem;">Ajouter un outil</h3>
                <form method="POST" action="index.php?page=stocks_outils">
                    <input type="hidden" name="action" value="add">
                    <label for="nomO">Nom de l'outil :</label>
                    <input type="text" name="nomO" id="nomO" required maxlength="80">

                    <label for="etat_physique">État initial :</label>
                    <select name="etat_physique" id="etat_physique" required>
                        <?php foreach ($ETATS_VALIDES as $e): ?>
                            <option value="<?= htmlspecialchars($e) ?>" <?= $e === 'Opérationnel' ? 'selected' : '' ?>><?= htmlspecialchars($e) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" style="margin-top: 1.5rem;">Ajouter au stock</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>
