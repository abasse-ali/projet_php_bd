<?php
/**
 * pages/tresorier/tresorerie.php
 *
 * Page de gestion de la trésorerie et des contributions.
 * Permet au Trésorier de visualiser l'historique des contributions,
 * de valider les apports en attente et de promouvoir automatiquement
 * les Visiteurs en Adhérents lors de la validation de leur contribution.
 */

// Sécurisation de l'accès à la page
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");

// Vérification des droits : réservé au rôle 'Trésorier'
exiger_role('Trésorier');

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
/**
 * Traitement de la validation d'une contribution.
 * Applique le pattern PRG (Post-Redirect-Get) pour éviter les doubles soumissions de formulaire.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_contribution'], $_POST['id_utilisateur'])) {
    $id_contrib = (int) $_POST['id_contribution'];
    $id_user    = (int) $_POST['id_utilisateur'];
    
    try {
        // Démarrage de la transaction pour garantir la cohérence des deux requêtes
        $bdd->beginTransaction();
        
        // 1. On passe la contribution concernée du statut 'en attente' à 'validée'
        $bdd->prepare("UPDATE Contribution SET statutC = 'validée' WHERE id_contribution = ? AND statutC = 'en attente'")
            ->execute([$id_contrib]);

        // 2. Logique métier : si l'apporteur était un 'Visiteur', il est automatiquement promu 'Adhérent'
        $bdd->prepare("UPDATE Utilisateur SET roleU = 'Adhérent' WHERE id_utilisateur = ? AND roleU = 'Visiteur'")
            ->execute([$id_user]);

        // Validation de la transaction
        $bdd->commit();
        header("Location: index.php?page=tresorerie&msg=ok");
        exit;
    } catch (PDOException $e) {
        // Annulation des modifications en cas d'erreur
        $bdd->rollBack();
        header("Location: index.php?page=tresorerie&msg=db_err");
        exit;
    }
}

$titre = "Trésorerie";
include 'inclusions/entete.php';

/**
 * Gestion et affichage des messages d'état post-redirection
 */
$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':     
            $msg = "<div class='alert alert-success'>" . icon('check') . " Contribution validée. Si le membre était Visiteur, il est désormais Adhérent.</div>"; 
            break;
        case 'db_err': 
            $msg = "<div class='alert alert-error'>" . icon('warning') . " Erreur lors de la validation.</div>"; 
            break;
    }
}

/**
 * @var int $nb_total      Nombre total de contributions enregistrées.
 * @var int $nb_en_attente Nombre de contributions nécessitant l'action du trésorier.
 * @var int $nb_validees   Nombre de contributions validées avec succès.
 */
// --- KPI : statistiques de trésorerie ---
$nb_total      = $bdd->query("SELECT COUNT(*) FROM Contribution")->fetchColumn();
$nb_en_attente = $bdd->query("SELECT COUNT(*) FROM Contribution WHERE statutC = 'en attente'")->fetchColumn();
$nb_validees   = $bdd->query("SELECT COUNT(*) FROM Contribution WHERE statutC = 'validée'")->fetchColumn();

/**
 * @var array $contributions Liste complète des contributions avec les informations du membre associé.
 * Tri : les contributions 'en attente' apparaissent en premier, puis tri par date décroissante.
 */
// --- Historique des contributions ---
$contributions = $bdd->query("
    SELECT c.*, u.prenomU, u.nomU, u.id_utilisateur, u.roleU
    FROM Contribution c
    JOIN Utilisateur u ON c.id_utilisateur_apporter = u.id_utilisateur
    ORDER BY
        CASE WHEN c.statutC = 'en attente' THEN 0 ELSE 1 END,
        c.date_contribution DESC
")->fetchAll();
?>

<div class="container">
    <div class="header">
        <div>
            <h1>Trésorerie et Apports</h1>
            <p>Suivi des contributions et des cotisations des membres.</p>
        </div>
    </div>

    <?= $msg ?>

    <div class="stats-grid" style="grid-template-columns: repeat(3, 1fr); background-color: transparent; color: var(--color-primary-dark); padding: 0; gap: 1rem; margin-bottom: 1.5rem;">
        <div class="card stat-card" style="margin-bottom: 0;">
            <div class="stat-card-content">
                <div class="stat-number" style="font-size: 1.8rem;"><?= (int) $nb_total ?></div>
                <div class="stat-label">Contributions au total</div>
            </div>
        </div>
        <div class="card stat-card" style="margin-bottom: 0;">
            <div class="stat-card-content">
                <div class="stat-number" style="font-size: 1.8rem; color: #c2410c;"><?= (int) $nb_en_attente ?></div>
                <div class="stat-label">En attente de validation</div>
            </div>
        </div>
        <div class="card stat-card" style="margin-bottom: 0;">
            <div class="stat-card-content">
                <div class="stat-number" style="font-size: 1.8rem; color: #16a34a;"><?= (int) $nb_validees ?></div>
                <div class="stat-label">Validées</div>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>Historique des contributions</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Membre</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($contributions)): ?>
                    <tr><td colspan="6" class="text-center text-muted">Aucune contribution enregistrée.</td></tr>
                <?php else: ?>
                    <?php foreach ($contributions as $c): ?>
                        <tr>
                            <td><?= date("d/m/Y", strtotime($c['date_contribution'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($c['prenomu'] . ' ' . $c['nomu']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($c['roleu']) ?></small>
                            </td>
                            <td><?= htmlspecialchars($c['type_apport']) ?></td>
                            <td><?= htmlspecialchars($c['description']) ?></td>
                            <td>
                                <?php if ($c['statutc'] === 'validée'): ?>
                                    <span class="badge badge-croissance">Validée</span>
                                <?php elseif ($c['statutc'] === 'en attente'): ?>
                                    <span class="badge badge-recolte">En attente</span>
                                <?php else: ?>
                                    <span class="badge badge-terminee"><?= htmlspecialchars(ucfirst($c['statutc'])) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['statutc'] === 'en attente'): ?>
                                    <form method="POST" action="index.php?page=tresorerie" style="margin:0;">
                                        <input type="hidden" name="id_contribution" value="<?= $c['id_contribution'] ?>">
                                        <input type="hidden" name="id_utilisateur" value="<?= $c['id_utilisateur'] ?>">
                                        <button type="submit" class="btn-small">Valider</button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>