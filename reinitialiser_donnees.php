<?php
/**
 * reinitialiser_donnees.php
 *
 * Outil de réinitialisation de la base : exécute en bloc le fichier `donnees.sql`,
 * qui constitue la seule source de vérité du jeu de données de test (TRUNCATE + INSERT).
 *
 * Une page HTML récapitulative est affichée à l'issue avec :
 * - le nombre d'enregistrements par table principale,
 * - la liste des comptes de test disponibles (mot de passe `1234`).
 */

require 'configuration/bdd.php';
require 'inclusions/fonctions.php';

/**
 * @var string $fichier_sql Chemin absolu vers le fichier contenant le jeu de données SQL.
 */
$fichier_sql = __DIR__ . '/ressources/bdd/donnees.sql';

// Génération de l'en-tête HTML pour l'affichage du récapitulatif
echo "<!DOCTYPE html><html lang='fr'><head><meta charset='UTF-8'>";
echo "<title>Réinitialisation des données — La Bòstia Verda</title>";
echo "<link rel='stylesheet' href='ressources/styles/style.css'>";
echo "</head><body style='padding: 2rem; max-width: 800px; margin: auto;'>";

echo "<h1>" . icon('broom') . " Réinitialisation des données…</h1>";

// Arrêt du script si le fichier contenant les données de test est introuvable
if (!file_exists($fichier_sql)) {
    echo "<div class='alert alert-error'>" . icon('warning') . " Fichier <code>donnees.sql</code> introuvable.</div>";
    echo "</body></html>";
    exit;
}

/**
 * @var string $sql Contenu brut des requêtes SQL d'initialisation.
 */
$sql = file_get_contents($fichier_sql);

try {
    // Exécution en bloc. PostgreSQL accepte plusieurs requêtes séparées par ';' dans un seul appel à exec().
    $bdd->exec($sql);

    // Compteurs par table — permet une confirmation visuelle que l'import s'est bien déroulé et est complet.
    $nb_utilisateurs = (int) $bdd->query("SELECT COUNT(*) FROM Utilisateur")->fetchColumn();
    $nb_parcelles    = (int) $bdd->query("SELECT COUNT(*) FROM Parcelle")->fetchColumn();
    $nb_plantes      = (int) $bdd->query("SELECT COUNT(*) FROM Plante")->fetchColumn();
    $nb_outils       = (int) $bdd->query("SELECT COUNT(*) FROM Outil")->fetchColumn();
    $nb_cultures     = (int) $bdd->query("SELECT COUNT(*) FROM Culture")->fetchColumn();
    $nb_recoltes     = (int) $bdd->query("SELECT COUNT(*) FROM Recolte")->fetchColumn();
    $nb_alertes      = (int) $bdd->query("SELECT COUNT(*) FROM AlerteSanitaire")->fetchColumn();
    $nb_meteo        = (int) $bdd->query("SELECT COUNT(*) FROM Meteo")->fetchColumn();
    $nb_reservations = (int) $bdd->query("SELECT COUNT(*) FROM Reservation")->fetchColumn();
    $nb_contribs     = (int) $bdd->query("SELECT COUNT(*) FROM Contribution")->fetchColumn();
    $nb_conseils     = (int) $bdd->query("SELECT COUNT(*) FROM ConseilCultural")->fetchColumn();

    echo "<div class='alert alert-success'>" . icon('check') . " Base de données réinitialisée avec succès.</div>";

    // Affichage structuré des statistiques d'importation
    echo "<h2 style='margin-top:2rem;'>" . icon('sparkles') . " Récapitulatif</h2>";
    echo "<ul style='line-height:1.8;'>";
    echo "<li><strong>$nb_utilisateurs</strong> utilisateurs (tous rôles confondus)</li>";
    echo "<li><strong>$nb_parcelles</strong> parcelles</li>";
    echo "<li><strong>$nb_plantes</strong> variétés de plantes</li>";
    echo "<li><strong>$nb_outils</strong> outils dans le cabanon</li>";
    echo "<li><strong>$nb_cultures</strong> cultures (en cours et terminées)</li>";
    echo "<li><strong>$nb_recoltes</strong> récoltes enregistrées</li>";
    echo "<li><strong>$nb_alertes</strong> alertes sanitaires</li>";
    echo "<li><strong>$nb_meteo</strong> relevés météo</li>";
    echo "<li><strong>$nb_reservations</strong> réservations d'outils</li>";
    echo "<li><strong>$nb_contribs</strong> contributions des membres</li>";
    echo "<li><strong>$nb_conseils</strong> conseils culturaux publiés</li>";
    echo "</ul>";

    // Tableau listant l'ensemble des comptes de test pour faciliter la navigation des développeurs/testeurs
    echo "<h2 style='margin-top:2rem;'>Comptes de test (mot de passe : <code>1234</code>)</h2>";
    echo "<table style='border-collapse: collapse; width: 100%;'>";
    echo "<thead><tr style='background: var(--color-input-bg);'><th style='padding:0.5rem; text-align:left;'>Rôle</th><th style='padding:0.5rem; text-align:left;'>Email</th></tr></thead>";
    echo "<tbody>";
    
    foreach ($bdd->query("SELECT roleU, email FROM Utilisateur ORDER BY id_utilisateur") as $u) {
        echo "<tr><td style='padding:0.5rem; border-top:1px solid #eee;'>" . htmlspecialchars($u['roleu']) . "</td>";
        echo "<td style='padding:0.5rem; border-top:1px solid #eee;'><code>" . htmlspecialchars($u['email']) . "</code></td></tr>";
    }
    
    echo "</tbody></table>";

    // Lien de redirection vers le Front Controller pour reprendre l'utilisation de l'application
    echo "<p style='margin-top:2rem;'><a class='btn btn-primary' href='index.php?page=connexion'>Aller à la page de connexion</a></p>";

} catch (PDOException $e) {
    // Interception et affichage sécurisé des erreurs liées à l'exécution du fichier SQL
    echo "<div class='alert alert-error'>" . icon('warning') . " Erreur lors de l'exécution de <code>donnees.sql</code> :<br><br>";
    echo "<code>" . htmlspecialchars($e->getMessage()) . "</code></div>";
}

echo "</body></html>";
?>