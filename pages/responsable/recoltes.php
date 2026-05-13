<?php
// pages/responsable/recoltes.php
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");
exiger_role('Responsable');

$titre = "Analyse des Récoltes";
include 'inclusions/entete.php';

// Requête avancée pour agréger les récoltes par parcelle et par année
$stats_recoltes = [];
$erreur_sql = "";

try {
    $stats_recoltes = $bdd->query("
        SELECT 
            p.secteurP, p.numeroP,
            EXTRACT(YEAR FROM r.date_recolte) as annee,
            SUM(r.quantiter) as total_kg
        FROM Recolte r
        JOIN Culture c ON r.id_culture_produire = c.id_culture
        JOIN Attribution a ON c.id_attribution_seffectuer = a.id_attribution
        JOIN Parcelle p ON a.id_parcelle_assigner = p.id_parcelle
        GROUP BY p.secteurP, p.numeroP, annee
        ORDER BY annee DESC, total_kg DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $cols = $bdd->query("SELECT column_name FROM information_schema.columns WHERE table_name ILIKE 'recolte'")->fetchAll(PDO::FETCH_COLUMN);
    $erreur_sql = icon('warning') . " <b>Erreur SQL :</b> " . htmlspecialchars($e->getMessage()) . "<br><br>" . icon('lightbulb') . " <b>Voici les colonnes qui existent VRAIMENT dans votre table Recolte :</b><br><span style='color:var(--color-primary-dark); font-family:monospace;'>" . implode(', ', $cols) . "</span><br><br><i>" . icon('arrow-right') . " Remplacez les noms (comme <code>r.quantite</code>, <code>r.date_recolte</code> ou <code>r.id_culture_produire</code>) dans la requête par les bons noms listés ci-dessus !</i>";
}
?>

<div class="container">
    <div class="header">
        <h1>Historique des récoltes</h1>
        <p>Historique des volumes de récoltes (en Kg) par parcelle et par année.</p>
    </div>

    <div class="card">
        <?php if($erreur_sql): ?>
            <div class="alert alert-warning"><?= $erreur_sql ?></div>
        <?php endif; ?>
        
        <h3>Statistiques annuelles</h3>
        <table>
            <thead>
                <tr>
                    <th>Année</th>
                    <th>Parcelle</th>
                    <th>Poids total récolté (Kg)</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($stats_recoltes)): ?>
                    <tr><td colspan="3">Aucune récolte n'a encore été enregistrée.</td></tr>
                <?php else: ?>
                    <?php foreach($stats_recoltes as $stat): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($stat['annee']) ?></strong></td>
                        <td>Secteur <?= htmlspecialchars($stat['secteurp']) ?> - N°<?= htmlspecialchars($stat['numerop']) ?></td>
                        <td><span class="badge badge-recolte"><?= htmlspecialchars(round($stat['total_kg'], 2)) ?> Kg</span></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>