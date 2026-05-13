<?php
// pages/admin/terrain.php
if (!isset($_SESSION['id_utilisateur']) || ($_SESSION['roleU'] ?? '') !== 'Administrateur') {
    die("Accès interdit : Réservé aux Administrateurs.");
}

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cloturer_alerte'])) {
    try {
        $requete = $bdd->prepare("UPDATE AlerteSanitaire SET est_resolue = TRUE WHERE id_alerte = ?");
        $requete->execute([$_POST['id_alerte']]);
        header("Location: index.php?page=admin_terrain&msg=ok");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?page=admin_terrain&msg=err");
        exit;
    }
}

$titre = "Foncier & Agronomie - Admin";
include 'inclusions/entete.php';

$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':  $msg = "<div class='alert alert-success'>" . icon('check') . " L'alerte sanitaire a été marquée comme résolue.</div>"; break;
        case 'err': $msg = "<div class='alert alert-error'>" . icon('warning') . " Une erreur est survenue.</div>"; break;
    }
}

// Récupération des alertes non résolues
$alertes = $bdd->query("
    SELECT a.*, p.secteurP, p.numeroP, pl.nom_variete
    FROM AlerteSanitaire a
    JOIN Culture c ON a.id_culture_est_signalee_sur = c.id_culture
    JOIN Attribution attr ON c.id_attribution_seffectuer = attr.id_attribution
    JOIN Parcelle p ON attr.id_parcelle_assigner = p.id_parcelle
    JOIN Plante pl ON c.id_plante_definir = pl.id_plante
    WHERE a.est_resolue = FALSE
    ORDER BY a.date_detection DESC
")->fetchAll();
?>

<div class="container">
    <div class="header">
        <div>
            <h1><?= icon('seedling') ?> Foncier & Agronomie</h1>
            <p>Supervision physique du jardin partagé et suivi sanitaire.</p>
        </div>
    </div>

    <div class="card">
        <?= $msg ?>
        <h3 style="color: #991b1b;"><?= icon('warning') ?> Alertes Sanitaires en Cours</h3>
        <table>
            <thead>
                <tr>
                    <th>Détection</th>
                    <th>Parcelle / Culture</th>
                    <th>Menace</th>
                    <th>Gravité</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($alertes)): ?>
                    <tr><td colspan="5">Aucune menace détectée sur le terrain. Tout va bien !</td></tr>
                <?php else: ?>
                    <?php foreach($alertes as $a): ?>
                    <tr>
                        <td><?= date("d/m/Y", strtotime($a['date_detection'])) ?></td>
                        <td>Sect. <?= htmlspecialchars($a['secteurp'] . '-' . $a['numerop']) ?> <br><small class="text-muted"><?= htmlspecialchars($a['nom_variete']) ?></small></td>
                        <td><strong><?= htmlspecialchars($a['nom_menace']) ?></strong><br><small><?= htmlspecialchars($a['descriptionalr']) ?></small></td>
                        <td>
                            <?php 
                                $couleur = $a['niveau_gravite'] === 'critique' ? 'background: #991b1b; color: white;' : 'background: #fca5a5; color: #991b1b;';
                            ?>
                            <span class="badge" style="<?= $couleur ?>"><?= strtoupper(htmlspecialchars($a['niveau_gravite'])) ?></span>
                        </td>
                        <td>
                            <form method="POST" style="margin: 0;">
                                <input type="hidden" name="id_alerte" value="<?= $a['id_alerte'] ?>">
                                <button type="submit" name="cloturer_alerte" class="btn-primary" style="padding: 0.3rem 0.8rem; width: auto;">Clôturer</button>
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