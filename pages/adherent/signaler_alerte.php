<?php
// pages/adherent/signaler_alerte.php
// Permet à un Adhérent de déclarer une alerte sanitaire sur ses propres cultures
// et de consulter celles déjà signalées sur sa parcelle.

if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");
exiger_role('Adhérent');

$id_utilisateur = $_SESSION['id_utilisateur'];

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_culture = $_POST['id_culture'] ?? '';
    $nom_menace = trim($_POST['nom_menace'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $gravite = $_POST['niveau_gravite'] ?? '';
    $traitement = trim($_POST['traitement_applique'] ?? '');

    if (empty($id_culture) || empty($nom_menace) || empty($description) || empty($gravite)) {
        header("Location: index.php?page=alertes_adherent&msg=missing");
        exit;
    }

    // Vérification stricte : la culture ciblée doit bien appartenir à une attribution
    // active de cet adhérent (sinon, on rejette pour éviter toute injection d'ID arbitraire).
    $requeteCheck = $bdd->prepare("
        SELECT COUNT(*) FROM Culture c
        JOIN Attribution a ON c.id_attribution_seffectuer = a.id_attribution
        WHERE c.id_culture = ?
          AND a.id_utilisateur_fournir = ?
          AND (a.date_fin IS NULL OR a.date_fin > CURRENT_DATE)
    ");
    $requeteCheck->execute([$id_culture, $id_utilisateur]);
    if ($requeteCheck->fetchColumn() == 0) {
        header("Location: index.php?page=alertes_adherent&msg=forbidden");
        exit;
    }

    try {
        $bdd->beginTransaction();
        $requete = $bdd->prepare("
            INSERT INTO AlerteSanitaire (descriptionALR, date_detection, est_resolue, nom_menace, niveau_gravite, traitement_applique, id_culture_est_signalee_sur)
            VALUES (?, CURRENT_DATE, FALSE, ?, ?, ?, ?)
        ");
        $requete->execute([$description, $nom_menace, $gravite, $traitement, $id_culture]);

        // Propagation spatiale : notifier les exploitants des parcelles voisines (+ Tuteurs)
        $nb_notif = propager_alerte_voisinage($bdd, $id_culture, $nom_menace, $gravite, $id_utilisateur);

        $bdd->commit();
        header("Location: index.php?page=alertes_adherent&msg=ok&notif=" . $nb_notif);
        exit;
    } catch (PDOException $e) {
        if ($bdd->inTransaction()) $bdd->rollBack();
        header("Location: index.php?page=alertes_adherent&msg=err");
        exit;
    }
}

$titre = "Signaler une alerte sanitaire";
include 'inclusions/entete.php';

// Message à afficher après redirection
$msg = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'ok') {
        $nb = isset($_GET['notif']) ? (int)$_GET['notif'] : 0;
        $sfx = $nb > 0
            ? " <strong>$nb voisin(s) de parcelle ou tuteur(s)</strong> ont été notifiés automatiquement."
            : " Aucune parcelle voisine active à notifier pour le moment.";
        $msg = "<div class='alert alert-success'>" . icon('check') . " Votre alerte sanitaire a bien été déclarée." . $sfx . "</div>";
    }
    elseif ($_GET['msg'] === 'missing')  $msg = "<div class='alert alert-error'>" . icon('warning') . " Veuillez remplir tous les champs obligatoires.</div>";
    elseif ($_GET['msg'] === 'forbidden') $msg = "<div class='alert alert-error'>" . icon('warning') . " Vous ne pouvez signaler une alerte que sur vos propres cultures.</div>";
    elseif ($_GET['msg'] === 'err')      $msg = "<div class='alert alert-error'>" . icon('warning') . " Une erreur est survenue lors de l'enregistrement de l'alerte.</div>";
}

// Cultures actives de l'adhérent connecté (sur sa parcelle attribuée)
$cultures_actives = $bdd->prepare("
    SELECT c.id_culture, pl.nom_variete, parc.secteurP, parc.numeroP
    FROM Culture c
    JOIN Plante pl ON c.id_plante_definir = pl.id_plante
    JOIN Attribution a ON c.id_attribution_seffectuer = a.id_attribution
    JOIN Parcelle parc ON a.id_parcelle_assigner = parc.id_parcelle
    WHERE a.id_utilisateur_fournir = ?
      AND (a.date_fin IS NULL OR a.date_fin > CURRENT_DATE)
      AND c.statut_Culture = 'en cours'
    ORDER BY parc.secteurP, parc.numeroP
");
$cultures_actives->execute([$id_utilisateur]);
$cultures_actives = $cultures_actives->fetchAll();

// Historique des alertes existantes sur les cultures de cet adhérent
$alertes_existantes = $bdd->prepare("
    SELECT al.*, pl.nom_variete, parc.secteurP, parc.numeroP
    FROM AlerteSanitaire al
    JOIN Culture c ON al.id_culture_est_signalee_sur = c.id_culture
    JOIN Plante pl ON c.id_plante_definir = pl.id_plante
    JOIN Attribution a ON c.id_attribution_seffectuer = a.id_attribution
    JOIN Parcelle parc ON a.id_parcelle_assigner = parc.id_parcelle
    WHERE a.id_utilisateur_fournir = ?
    ORDER BY al.date_detection DESC
");
$alertes_existantes->execute([$id_utilisateur]);
$alertes_existantes = $alertes_existantes->fetchAll();
?>

<div class="container">
    <div class="header">
        <div>
            <h1><?= icon('warning') ?> Signaler une alerte sur ma culture</h1>
            <p>Détectez-vous une maladie, un nuisible ou une anomalie ? Signalez-le pour que le Tuteur et les voisins de parcelle soient prévenus.</p>
        </div>
    </div>

    <?= $msg ?>

    <div class="dashboard-grid">
        <div class="main-column">
            <div class="form-card">
                <h3>Nouvelle déclaration</h3>

                <?php if (empty($cultures_actives)): ?>
                    <div class="alert alert-warning">
                        <?= icon('warning') ?> Vous n'avez aucune culture en cours sur une parcelle active.
                        Vous pourrez signaler une alerte une fois qu'une culture aura été enregistrée sur votre parcelle.
                    </div>
                <?php else: ?>
                    <form method="POST" action="index.php?page=alertes_adherent">

                        <label for="id_culture">Culture concernée :</label>
                        <select name="id_culture" id="id_culture" required>
                            <option value="" disabled selected>-- Sélectionner la culture touchée --</option>
                            <?php foreach($cultures_actives as $c): ?>
                                <option value="<?= $c['id_culture'] ?>">
                                    Secteur <?= htmlspecialchars($c['secteurp']) ?>-<?= htmlspecialchars($c['numerop']) ?> : <?= htmlspecialchars($c['nom_variete']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <div class="form-grid-half">
                            <div>
                                <label for="nom_menace">Nom de la menace :</label>
                                <input type="text" name="nom_menace" id="nom_menace" placeholder="Ex: Mildiou, Pucerons..." required>
                            </div>
                            <div>
                                <label for="niveau_gravite">Niveau de gravité :</label>
                                <select name="niveau_gravite" id="niveau_gravite" required>
                                    <option value="faible">Faible</option>
                                    <option value="modéré">Modéré</option>
                                    <option value="élevé">Élevé</option>
                                    <option value="critique">Critique</option>
                                </select>
                            </div>
                        </div>

                        <label for="description">Symptômes observés :</label>
                        <textarea name="description" id="description" rows="4" style="width:100%; padding:0.8rem; margin-bottom:1.2rem; border-radius:12px; border:none; background-color:var(--color-input-bg);" required placeholder="Ex: Taches noires sur les feuilles basses, jaunissement..."></textarea>

                        <label for="traitement_applique">Traitement déjà appliqué (optionnel) :</label>
                        <input type="text" name="traitement_applique" id="traitement_applique" placeholder="Ex: Bouillie bordelaise, savon noir...">

                        <button type="submit" class="btn-danger w-100 mt-3">Signaler l'alerte</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="side-column">
            <div class="card">
                <h3><?= icon('warning') ?> Alertes sur mes cultures</h3>
                <?php if (empty($alertes_existantes)): ?>
                    <p class="text-muted text-italic">Aucune alerte enregistrée pour vos cultures. Tout va bien !</p>
                <?php else: ?>
                    <?php foreach ($alertes_existantes as $a): ?>
                        <?php if (!$a['est_resolue']): ?>
                            <div class="notif-alert-maladie">
                                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                <div class="alert-content">
                                    <strong>
                                        <?= htmlspecialchars($a['nom_menace']) ?>
                                        <span class="badge badge-recolte"><?= htmlspecialchars(ucfirst($a['niveau_gravite'])) ?></span>
                                    </strong>
                                    <span class="notif-text">
                                        Secteur <?= htmlspecialchars($a['secteurp']) ?>-<?= htmlspecialchars($a['numerop']) ?>
                                        · <?= htmlspecialchars($a['nom_variete']) ?>
                                    </span>
                                    <span class="notif-text"><?= htmlspecialchars($a['descriptionalr']) ?></span>
                                    <span class="notif-date">Détectée le <?= date("d/m/Y", strtotime($a['date_detection'])) ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="notif-item">
                                <strong>
                                    <?= htmlspecialchars($a['nom_menace']) ?>
                                    <span class="badge badge-terminee">Résolue</span>
                                </strong>
                                <span class="notif-text">
                                    Secteur <?= htmlspecialchars($a['secteurp']) ?>-<?= htmlspecialchars($a['numerop']) ?>
                                    · <?= htmlspecialchars($a['nom_variete']) ?>
                                </span>
                                <span class="notif-text"><?= htmlspecialchars($a['descriptionalr']) ?></span>
                                <span class="notif-date">Détectée le <?= date("d/m/Y", strtotime($a['date_detection'])) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>
