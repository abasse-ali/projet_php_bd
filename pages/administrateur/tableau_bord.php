<?php
// pages/admin/dashboard.php
if (!isset($_SESSION['id_utilisateur']) || ($_SESSION['roleU'] ?? '') !== 'Administrateur') {
    die("Accès interdit : Réservé aux Administrateurs.");
}

$titre = "Supervision Globale - Admin";
include 'inclusions/entete.php';

// 1. Statistiques d'occupation
$stats_users = $bdd->query("SELECT roleU, COUNT(*) as total FROM Utilisateur GROUP BY roleU ORDER BY total DESC")->fetchAll();
$total_parcelles = $bdd->query("SELECT COUNT(*) FROM Parcelle")->fetchColumn();
$parcelles_occupees = $bdd->query("SELECT COUNT(*) FROM Attribution WHERE date_fin IS NULL OR date_fin > CURRENT_DATE")->fetchColumn();

// 2. État de la grainothèque (alerte sur stock <= 15)
$alertes_stock = $bdd->query("
    SELECT s.nomS, s.stock_mis_a_jour, p.nom_variete 
    FROM Semence s
    JOIN Plante p ON s.id_plante_correspondre = p.id_plante
    WHERE s.stock_mis_a_jour <= 15
    ORDER BY s.stock_mis_a_jour ASC
")->fetchAll();
?>

<div class="container">
    <div class="header">
        <div>
            <h1 class="flex-title">
                <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="2" y1="12" x2="22" y2="12"></line><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"></path></svg>
                Supervision Globale
            </h1>
            <p>Tableau de bord décisionnel de l'administration</p>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <h3 class="flex-title">
                <svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                Taux d'occupation
            </h3>
            <p class="mb-2"><strong>Parcelles :</strong> <?= $parcelles_occupees ?> / <?= $total_parcelles ?> attribuées.</p>
            
            <table class="mt-2">
                <thead>
                    <tr><th>Rôle Utilisateur</th><th>Nombre de comptes</th></tr>
                </thead>
                <tbody>
                    <?php foreach($stats_users as $stat): ?>
                        <tr>
                            <td><?= htmlspecialchars($stat['roleu']) ?></td>
                            <td><strong><?= $stat['total'] ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div class="card <?= !empty($alertes_stock) ? 'alert-error' : 'alert-success' ?>" style="margin-bottom:0;">
            <h3 class="flex-title mb-1" style="color:inherit;">
                <svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                État de la Grainothèque
            </h3>
            <?php if(empty($alertes_stock)): ?>
                <p>Tous les stocks de semences sont suffisants.</p>
            <?php else: ?>
                <p><strong>Attention :</strong> Stocks critiques !</p>
                <ul style="margin-top: 10px; padding-left: 20px;">
                    <?php foreach($alertes_stock as $alerte): ?>
                        <li><?= htmlspecialchars($alerte['nom_variete']) ?> : <strong><?= $alerte['stock_mis_a_jour'] ?>g</strong> restants.</li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>