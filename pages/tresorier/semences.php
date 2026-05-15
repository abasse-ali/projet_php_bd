<?php
/**
 * pages/tresorier/semences.php
 *
 * Page de gestion des stocks de semences de la grainothèque.
 * Permet au Trésorier de visualiser l'inventaire et de mettre à jour 
 * manuellement les quantités (en grammes) pour chaque lot.
 */

// Sécurisation de l'accès à la page
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");

// Vérification des droits : page réservée au rôle 'Trésorier'
exiger_role('Trésorier');

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
/**
 * Traitement de la mise à jour d'un stock de semences.
 * Utilisation du pattern PRG (Post-Redirect-Get) pour éviter la double soumission.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_semence'], $_POST['nouveau_stock'])) {
    $id_semence    = (int) $_POST['id_semence'];
    $nouveau_stock = (int) $_POST['nouveau_stock'];

    // Validation métier : le stock saisi ne doit pas être négatif
    if ($nouveau_stock < 0) {
        header("Location: index.php?page=stocks&msg=invalid");
        exit;
    }

    try {
        // Mise à jour de la quantité en base de données pour la semence ciblée
        $bdd->prepare("UPDATE Semence SET stock_mis_a_jour = ? WHERE id_semence = ?")
            ->execute([$nouveau_stock, $id_semence]);
            
        header("Location: index.php?page=stocks&msg=ok");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?page=stocks&msg=db_err");
        exit;
    }
}

$titre = "Gestion des Stocks";
include 'inclusions/entete.php';

/**
 * Gestion et affichage des messages d'état post-redirection
 */
$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':      
            $msg = "<div class='alert alert-success'>" . icon('check') . " Stock mis à jour.</div>"; 
            break;
        case 'invalid': 
            $msg = "<div class='alert alert-error'>" . icon('warning') . " La quantité doit être positive ou nulle.</div>"; 
            break;
        case 'db_err':  
            $msg = "<div class='alert alert-error'>" . icon('warning') . " Erreur lors de la mise à jour.</div>"; 
            break;
    }
}

/**
 * @var array $semences Liste complète de l'inventaire de la grainothèque,
 * incluant le nom de la variété de plante correspondante.
 */
$semences = $bdd->query("
    SELECT s.*, p.nom_variete
    FROM Semence s
    JOIN Plante p ON s.id_plante_correspondre = p.id_plante
    ORDER BY p.nom_variete
")->fetchAll();

/**
 * @var int $SEUIL_ALERTE 
 * Seuil (en grammes) en dessous duquel l'interface affichera une alerte visuelle (stock critique).
 */
$SEUIL_ALERTE = 15;
?>

<div class="container">
    <div class="header">
        <div>
            <h1><?= icon('wheat') ?> Stocks de la Grainothèque</h1>
            <p>Mise à jour manuelle des quantités de semences disponibles. Seuil d'alerte : <?= $SEUIL_ALERTE ?> g.</p>
        </div>
    </div>

    <?= $msg ?>

    <div class="card">
        <h3>Inventaire des semences</h3>
        <table>
            <thead>
                <tr>
                    <th>Variété</th>
                    <th>Lot</th>
                    <th>Quantité en stock</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($semences)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Aucune semence en stock.</td></tr>
                <?php else: ?>
                    <?php foreach($semences as $s):
                        // Détermination de l'état du stock pour l'affichage conditionnel du badge
                        $stock = (int) ($s['stock_mis_a_jour'] ?? 0);
                        $est_critique = $stock <= $SEUIL_ALERTE;
                    ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($s['nom_variete']) ?></strong></td>
                            <td><?= htmlspecialchars($s['noms']) ?></td>
                            <td>
                                <?php if ($est_critique): ?>
                                    <span class="badge" style="background:#fee2e2; color:#991b1b;"><?= $stock ?> g — Critique</span>
                                <?php else: ?>
                                    <span class="badge badge-croissance"><?= $stock ?> g</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <form method="POST" action="index.php?page=stocks" class="form-inline" style="margin:0;">
                                    <input type="hidden" name="id_semence" value="<?= $s['id_semence'] ?>">
                                    <input type="number" name="nouveau_stock" value="<?= $stock ?>" min="0" style="width: 100px; padding: 0.3rem; margin: 0; font-size: 0.9rem;" required>
                                    <button type="submit" class="btn-primary" style="padding: 0.3rem 0.8rem; width: auto; margin-left: 10px;">Mettre à jour</button>
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