<?php
// pages/public/plantes.php
// Catalogue botanique consultable par tous les visiteurs et adhérents.

$titre = "Catalogue des Plantes";
include 'inclusions/entete.php';

// Récupération du catalogue complet (une seule requête)
$plantes = $bdd->query("SELECT * FROM Plante ORDER BY nom_variete")->fetchAll();

// Récupération de TOUTES les associations en une seule requête, puis regroupement par plante (évite le N+1).
$associations_raw = $bdd->query("
    SELECT s.id_plante_E1 AS id_source, s.id_plante_E2 AS id_partenaire, p.nom_variete AS partenaire_nom, s.type_accord
    FROM saccorder s
    JOIN Plante p ON p.id_plante = s.id_plante_E2
    UNION ALL
    SELECT s.id_plante_E2 AS id_source, s.id_plante_E1 AS id_partenaire, p.nom_variete AS partenaire_nom, s.type_accord
    FROM saccorder s
    JOIN Plante p ON p.id_plante = s.id_plante_E1
")->fetchAll();

$associations_by_plante = [];
foreach ($associations_raw as $a) {
    $associations_by_plante[$a['id_source']][] = $a;
}

// Petit helper pour traduire un numéro de mois en libellé court
$mois_court = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil', 'Août', 'Sept', 'Oct', 'Nov', 'Déc'];
?>

<div class="container">
    <div class="header">
        <h1 class="flex-title">
            <svg class="icon-lg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"></path><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"></path></svg>
            Catalogue des Plantes
        </h1>
        <p>Découvrez les variétés disponibles dans notre grainothèque.</p>
    </div>

    <?php if (empty($plantes)): ?>
        <div class="card">
            <p class="text-muted text-italic">Le catalogue est en cours de construction.</p>
        </div>
    <?php else: ?>
        <div class="plantes-grid" style="display:grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 1.5rem;">
            <?php foreach($plantes as $p):
                $semis_debut = (int) $p['num_mois_semis_debut'];
                $semis_fin   = (int) $p['num_mois_semis_fin'];
                $recol_debut = (int) $p['num_mois_recol_deb'];
                $recol_fin   = (int) $p['num_mois_recol_fin'];
                $accords     = $associations_by_plante[$p['id_plante']] ?? [];
            ?>
                <article class="card">
                    <h3 class="culture-title" style="margin-top:0;"><?= htmlspecialchars($p['nom_variete']) ?></h3>

                    <?php if (!empty($p['contenu_fiche'])): ?>
                        <p style="font-style: italic; color: #555; margin-bottom: 1rem;">
                            <?= nl2br(htmlspecialchars($p['contenu_fiche'])) ?>
                        </p>
                    <?php endif; ?>

                    <div class="info-list">
                        <div class="info-item">
                            <strong>Semis :</strong>
                            <span><?= htmlspecialchars($mois_court[$semis_debut] ?? '?') ?> &rarr; <?= htmlspecialchars($mois_court[$semis_fin] ?? '?') ?></span>
                        </div>
                        <div class="info-item">
                            <strong>Récolte :</strong>
                            <span><?= htmlspecialchars($mois_court[$recol_debut] ?? '?') ?> &rarr; <?= htmlspecialchars($mois_court[$recol_fin] ?? '?') ?></span>
                        </div>
                    </div>

                    <div style="margin-top: 1rem;">
                        <strong>Compagnonnage :</strong>
                        <?php if (empty($accords)): ?>
                            <p class="text-muted text-italic" style="margin-top: 0.3rem;">Aucune association connue.</p>
                        <?php else: ?>
                            <ul style="list-style: none; padding: 0; margin-top: 0.3rem;">
                                <?php foreach ($accords as $a):
                                    $est_benefique = (stripos($a['type_accord'], 'bénéfique') !== false);
                                    $couleur = $est_benefique ? '#166534' : '#991b1b';
                                ?>
                                    <li style="color:<?= $couleur ?>; display:flex; align-items:center; gap:0.4rem; margin-bottom: 4px;">
                                        <?php if ($est_benefique): ?>
                                            <?= icon('check') ?>
                                        <?php else: ?>
                                            <?= icon('warning') ?>
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($a['partenaire_nom']) ?>
                                            <small style="opacity:0.75;">(<?= htmlspecialchars($a['type_accord']) ?>)</small>
                                        </span>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>
