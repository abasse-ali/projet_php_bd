<?php
// pages/responsable/dashboard.php
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");
exiger_role('Responsable');

$titre = "Dashboard Foncier";
include 'inclusions/entete.php';

$nb_utilisateurs = $bdd->query("SELECT COUNT(*) FROM Utilisateur")->fetchColumn();
$nb_parcelles = $bdd->query("SELECT COUNT(*) FROM Parcelle")->fetchColumn();
$nb_affectations = $bdd->query("SELECT COUNT(*) FROM Attribution WHERE date_fin IS NULL OR date_fin > CURRENT_DATE")->fetchColumn();

// Candidats en attente
$attentes = $bdd->query("SELECT COUNT(*) FROM s_inscrire")->fetchColumn();
?>

<div class="container">
    <div class="header">
        <div>
            <h1 class="flex-title">
                <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                Tableau de bord Foncier
            </h1>
            <p>Outils d'analyse réservés aux Responsables</p>
        </div>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <h3 class="flex-title">
                <svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Statistiques d'Occupation
            </h3>
            <ul style="line-height: 2;">
                <li><strong>Membres inscrits :</strong> <?= $nb_utilisateurs ?></li>
                <li><strong>Parcelles totales sur site :</strong> <?= $nb_parcelles ?></li>
                <li><strong>Parcelles attribuées actives :</strong> <?= $nb_affectations ?> / <?= $nb_parcelles ?></li>
            </ul>
        </div>
        
        <div class="card alert-warning" style="margin-bottom:0;">
            <h3 class="flex-title mb-1" style="color:inherit;">
                <svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>
                Attributions en attente
            </h3>
            <p>Il y a <strong><?= $attentes ?> dossier(s)</strong> en liste d'attente.</p>
            <a href="index.php?page=parcelles" class="btn btn-primary mt-2" style="display:inline-block;">Gérer les attributions</a>
        </div>
    </div>
</div>
<?php include 'inclusions/pied_de_page.php'; ?>