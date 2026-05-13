<?php
// pages/responsable/analyses.php
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");

// Sécurisation stricte : Tableau de bord décisionnel réservé aux instances décisionnaires
exiger_role(['Responsable', 'Trésorier', 'Administrateur']);

$titre = "Tableau de Bord Décisionnel";
include 'inclusions/entete.php';

// On récupère le rôle de l'utilisateur pour afficher uniquement les encarts qui le concernent.
$roleActuel = $_SESSION['roleU'] ?? '';
$afficher_responsable = in_array($roleActuel, ['Responsable', 'Administrateur'], true);
$afficher_tresorier   = in_array($roleActuel, ['Trésorier', 'Administrateur'], true);

// Initialisation des variables pour éviter des erreurs HTML si la requête échoue
$rotation_data = [];
$biodiversite_data = [];
$stocks_data = [];
$erreur_sql = "";

try {
    // ==============================================================================
    // VUE RESPONSABLE DU TERRAIN
    // ==============================================================================
    if ($afficher_responsable) {
        
        // --- ANALYSE 1 : Optimisation de la rotation des cultures ---
        // Logique : Jointures multiples (JOIN) pour relier la parcelle à son occupant, 
        // puis à la culture en cours/terminée, et enfin aux détails de la plante.
        // On trie par Parcelle puis chronologiquement pour voir l'historique d'une même terre.
        $requeteRotation = $bdd->query("
            SELECT 
                p.numeroP, 
                p.secteurP, 
                pl.nom_variete, 
                c.date_semis 
            FROM Parcelle p
            JOIN Attribution a ON p.id_parcelle = a.id_parcelle_assigner
            JOIN Culture c ON a.id_attribution = c.id_attribution_seffectuer
            JOIN Plante pl ON c.id_plante_definir = pl.id_plante
            ORDER BY p.numeroP ASC, c.date_semis DESC
        ");
        $rotation_data = $requeteRotation->fetchAll();

        // --- ANALYSE 2 : Analyse de la biodiversité observée ---
        // Logique : Interrogation de la table d'association `observer`.
        // Fonction d'agrégation COUNT(DISTINCT) pour compter le nombre d'espèces uniques.
        // La clause HAVING filtre post-regroupement pour exclure les parcelles pauvres en biodiversité (<=1).
        $requeteBio = $bdd->query("
            SELECT 
                p.numeroP, 
                p.secteurP, 
                COUNT(DISTINCT o.espece) as nb_especes
            FROM observer o
            JOIN Parcelle p ON o.id_parcelle = p.id_parcelle
            GROUP BY o.id_parcelle, p.numeroP, p.secteurP
            HAVING COUNT(DISTINCT o.espece) > 1
            ORDER BY nb_especes DESC
        ");
        $biodiversite_data = $requeteBio->fetchAll();
    }

    // ==============================================================================
    // VUE TRÉSORIER / GESTIONNAIRE DES STOCKS
    // ==============================================================================
    if ($afficher_tresorier) {
        
        // --- ANALYSE 3 : Gestion des stocks de semences (Alerte Grainothèque) ---
        // Logique : Affichage conditionnel restrictif via WHERE.
        // On repère instantanément les semences dont le stock atteint un seuil de risque (<= 15)
        // Tri ascendant pour mettre les urgences absolues en premier.
        $requeteStock = $bdd->query("
            SELECT 
                nomS, 
                stock_mis_a_jour 
            FROM Semence 
            WHERE stock_mis_a_jour <= 15 
            ORDER BY stock_mis_a_jour ASC
        ");
        $stocks_data = $requeteStock->fetchAll();
    }

} catch (PDOException $e) {
    // Blocage sécurisé des erreurs : évitera le crash total de l'UI si les tables du MLD sont vides/manquantes.
    $erreur_sql = "Une erreur SQL est survenue lors de l'extraction des données : " . htmlspecialchars($e->getMessage());
}
?>

<div class="container">
    <div class="header">
        <div>
            <h1>Tableau de bord Decisionnel</h1>
            <p>Analyses complexes pour optimiser les ressources et le suivi de la biodiversité.</p>
        </div>
        <button class="btn btn-primary" onclick="window.print()">Exporter le rapport</button>
    </div>

    <?php if ($erreur_sql): ?>
        <div class="alert alert-error">
            <strong><?= icon('warning') ?> Attention :</strong> <?= $erreur_sql ?>
        </div>
    <?php endif; ?>

    <!-- CSS Grid Layout (Référé via style.css "dashboard-grid" = 2fr 1fr) -->
    <div class="dashboard-grid">
        
        <!-- COLONNE GAUCHE (2fr) : VUE RESPONSABLE (Analyses 1 & 2) -->
        <div class="main-column">
            <?php if ($afficher_responsable): ?>
                
                <article class="card">
                    <h3>Rotation des Cultures</h3>
                    <p>Suivi chronologique des plantations par parcelle pour anticiper et prévenir l'appauvrissement des sols.</p>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>Secteur</th>
                                <th>N° Parcelle</th>
                                <th>Variété plantée</th>
                                <th>Date de semis</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rotation_data)): ?>
                                <tr><td colspan="4">Aucune donnée de culture enregistrée pour le moment.</td></tr>
                            <?php else: ?>
                                <?php foreach ($rotation_data as $ligne): ?>
                                    <tr>
                                        <td><span class="badge badge-croissance">Sect. <?= htmlspecialchars($ligne['secteurp']) ?></span></td>
                                        <td><strong>Parcelle <?= htmlspecialchars($ligne['numerop']) ?></strong></td>
                                        <td><?= htmlspecialchars($ligne['nom_variete']) ?></td>
                                        <td><?= date("d/m/Y", strtotime($ligne['date_semis'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </article>

                <article class="card">
                    <h3>Biodiversite Observee</h3>
                    <p>Identification des parcelles présentant une richesse faunique/floristique (plus d'une espèce enregistrée).</p>

                    <table>
                        <thead>
                            <tr>
                                <th>Secteur</th>
                                <th>N° Parcelle</th>
                                <th>Espèces distinctes observées</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($biodiversite_data)): ?>
                                <tr><td colspan="3">Aucune parcelle ne présente de biodiversité significative pour le moment.</td></tr>
                            <?php else: ?>
                                <?php foreach ($biodiversite_data as $ligne): ?>
                                    <tr>
                                        <td>Sect. <?= htmlspecialchars($ligne['secteurp']) ?></td>
                                        <td><strong>Parcelle <?= htmlspecialchars($ligne['numerop']) ?></strong></td>
                                        <td><span class="badge badge-recolte">+ <?= htmlspecialchars($ligne['nb_especes']) ?> espèces</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </article>

            <?php else: ?>
                <div class="card alert alert-warning">
                    <h3><?= icon('lock') ?> Accès Restreint</h3>
                    <p>Les analyses concernant le suivi des parcelles, la rotation des sols et la biodiversité sont réservées aux Responsables du terrain.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- COLONNE DROITE (1fr) : VUE TRÉSORIER (Analyse 3) -->
        <div class="side-column">
            <?php if ($afficher_tresorier): ?>
                
                <article class="card">
                    <h3>Alerte Grainotheque</h3>
                    <p>Suivi des stocks de semences ayant atteint le seuil critique (≤ 15 unités). Action d'approvisionnement requise.</p>

                    <table>
                        <thead>
                            <tr>
                                <th>Nom de la Semence</th>
                                <th>Stock Actuel</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stocks_data)): ?>
                                <tr><td colspan="2"><?= icon('check') ?> Tous les stocks sont viables, aucune alerte.</td></tr>
                            <?php else: ?>
                                <?php foreach ($stocks_data as $ligne): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($ligne['noms']) ?></strong></td>
                                        <td>
                                            <span class="badge alert-error">
                                                <?= htmlspecialchars($ligne['stock_mis_a_jour']) ?> unités
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </article>

            <?php else: ?>
                <div class="card alert alert-warning">
                    <h3><?= icon('lock') ?> Accès Restreint</h3>
                    <p>Le suivi financier et l'état des stocks de la grainothèque sont réservés au Trésorier.</p>
                </div>
            <?php endif; ?>
        </div>
        
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>