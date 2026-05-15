<?php
/**
 * pages/public/plantes.php
 *
 * Catalogue botanique consultable par tous les visiteurs et adhérents.
 * Ce script récupère et affiche la liste des plantes disponibles dans la grainothèque,
 * incluant leurs périodes de semis/récolte et leurs associations (compagnonnage).
 */

$titre = "Catalogue des Plantes";
include 'inclusions/entete.php';

/**
 * @var array $plantes
 * Récupération du catalogue complet de toutes les variétés de plantes enregistrées.
 */
$plantes = $bdd->query("SELECT * FROM Plante ORDER BY nom_variete")->fetchAll();

/**
 * @var array $associations_raw
 * Récupération de TOUTES les associations (compagnonnage) en une seule requête.
 * L'utilisation de UNION ALL permet de récupérer les relations de manière bidirectionnelle
 * (E1 -> E2 et E2 -> E1) car la table `saccorder` ne stocke l'association que dans un sens.
 * Cela permet d'éviter le problème des requêtes N+1 (une requête par plante dans la boucle).
 */
$associations_raw = $bdd->query("
    SELECT s.id_plante_E1 AS id_source, s.id_plante_E2 AS id_partenaire, p.nom_variete AS partenaire_nom, s.type_accord
    FROM saccorder s
    JOIN Plante p ON p.id_plante = s.id_plante_E2
    UNION ALL
    SELECT s.id_plante_E2 AS id_source, s.id_plante_E1 AS id_partenaire, p.nom_variete AS partenaire_nom, s.type_accord
    FROM saccorder s
    JOIN Plante p ON p.id_plante = s.id_plante_E1
")->fetchAll();

/**
 * @var array $associations_by_plante
 * Restructuration des données brutes en un tableau associatif groupé par l'ID de la plante source.
 * Facilite l'accès direct aux associations lors du parcours des plantes.
 */
$associations_by_plante = [];
foreach ($associations_raw as $a) {
    $associations_by_plante[$a['id_source']][] = $a;
}

/**
 * @var array $mois_court
 * Helper pour traduire un numéro de mois (1-12) en libellé court.
 * L'index 0 est intentionnellement vide pour faire correspondre l'index 1 à 'Jan'.
 */
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
                // Extraction, typage et sécurisation des données de la plante courante
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
                                    // Détermination de l'impact visuel selon le type d'accord (bénéfique ou néfaste)
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