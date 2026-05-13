<?php
// reinitialiser_donnees.php
// Réinitialise la base de données en exécutant donnees.sql.
// donnees.sql est la SEULE source de vérité pour le jeu de données de test.
require 'configuration/bdd.php';
require 'inclusions/fonctions.php';

$fichier_sql = __DIR__ . '/ressources/bdd/donnees.sql';

echo "<!DOCTYPE html><html lang='fr'><head><meta charset='UTF-8'>";
echo "<title>Réinitialisation des données — La Bòstia Verda</title>";
echo "<link rel='stylesheet' href='ressources/styles/style.css'>";
echo "</head><body style='padding: 2rem; max-width: 800px; margin: auto;'>";

echo "<h1>" . icon('broom') . " Réinitialisation des données…</h1>";

if (!file_exists($fichier_sql)) {
    echo "<div class='alert alert-error'>" . icon('warning') . " Fichier <code>data.sql</code> introuvable.</div>";
    echo "</body></html>";
    exit;
}

$sql = file_get_contents($fichier_sql);

try {
    // PostgreSQL accepte plusieurs requêtes séparées par ';' dans un seul exec().
    $bdd->exec($sql);

    // Quelques statistiques pour confirmer visuellement
    $nb_users      = (int) $bdd->query("SELECT COUNT(*) FROM Utilisateur")->fetchColumn();
    $nb_parcelles  = (int) $bdd->query("SELECT COUNT(*) FROM Parcelle")->fetchColumn();
    $nb_plantes    = (int) $bdd->query("SELECT COUNT(*) FROM Plante")->fetchColumn();
    $nb_outils     = (int) $bdd->query("SELECT COUNT(*) FROM Outil")->fetchColumn();
    $nb_cultures   = (int) $bdd->query("SELECT COUNT(*) FROM Culture")->fetchColumn();
    $nb_recoltes   = (int) $bdd->query("SELECT COUNT(*) FROM Recolte")->fetchColumn();
    $nb_alertes    = (int) $bdd->query("SELECT COUNT(*) FROM AlerteSanitaire")->fetchColumn();
    $nb_meteo      = (int) $bdd->query("SELECT COUNT(*) FROM Meteo")->fetchColumn();
    $nb_reservations = (int) $bdd->query("SELECT COUNT(*) FROM Reservation")->fetchColumn();
    $nb_contribs   = (int) $bdd->query("SELECT COUNT(*) FROM Contribution")->fetchColumn();
    $nb_conseils   = (int) $bdd->query("SELECT COUNT(*) FROM ConseilCultural")->fetchColumn();

    echo "<div class='alert alert-success'>" . icon('check') . " Base de données réinitialisée avec succès.</div>";

    echo "<h2 style='margin-top:2rem;'>" . icon('sparkles') . " Récapitulatif</h2>";
    echo "<ul style='line-height:1.8;'>";
    echo "<li><strong>$nb_users</strong> utilisateurs (tous rôles confondus)</li>";
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

    echo "<h2 style='margin-top:2rem;'>Comptes de test (mot de passe : <code>1234</code>)</h2>";
    echo "<table style='border-collapse: collapse; width: 100%;'>";
    echo "<thead><tr style='background: var(--color-input-bg);'><th style='padding:0.5rem; text-align:left;'>Rôle</th><th style='padding:0.5rem; text-align:left;'>Email</th></tr></thead>";
    echo "<tbody>";
    foreach ($bdd->query("SELECT roleU, email FROM Utilisateur ORDER BY id_utilisateur") as $u) {
        echo "<tr><td style='padding:0.5rem; border-top:1px solid #eee;'>" . htmlspecialchars($u['roleu']) . "</td>";
        echo "<td style='padding:0.5rem; border-top:1px solid #eee;'><code>" . htmlspecialchars($u['email']) . "</code></td></tr>";
    }
    echo "</tbody></table>";

    echo "<p style='margin-top:2rem;'><a class='btn btn-primary' href='index.php?page=connexion'>Aller à la page de connexion</a></p>";

} catch (PDOException $e) {
    echo "<div class='alert alert-error'>" . icon('warning') . " Erreur lors de l'exécution de <code>data.sql</code> :<br><br>";
    echo "<code>" . htmlspecialchars($e->getMessage()) . "</code></div>";
}

echo "</body></html>";
