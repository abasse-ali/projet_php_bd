<?php
// pages/adherent/meteo.php
// Synchronisation et affichage des relevés météo pour le jardin partagé de Naucelle.
// API : Open-Meteo (https://open-meteo.com) — gratuite, sans inscription, sans clé.
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");
exiger_role(['Adhérent', 'Tuteur']);

// --- Coordonnées géographiques de Naucelle (Aveyron) ---
$latitude  = 44.2024;
$longitude = 2.3392;

// --- Correspondance des codes météo Open-Meteo (WMO) vers descriptions FR + icône ---
function decrire_meteo($code) {
    if ($code == 0)                       return ['Ciel clair',                   'sun'];
    if (in_array($code, [1, 2, 3]))       return ['Partiellement nuageux',        'cloud'];
    if (in_array($code, [45, 48]))        return ['Brouillard',                   'fog'];
    if (in_array($code, [51, 53, 55]))    return ['Bruine',                       'drizzle'];
    if (in_array($code, [56, 57]))        return ['Bruine verglaçante',           'drizzle'];
    if (in_array($code, [61, 63, 65]))    return ['Pluie',                        'rain'];
    if (in_array($code, [66, 67]))        return ['Pluie verglaçante',            'rain'];
    if (in_array($code, [71, 73, 75]))    return ['Neige',                        'snow'];
    if ($code == 77)                      return ['Grains de neige',              'snow'];
    if (in_array($code, [80, 81, 82]))    return ['Averses',                      'rain'];
    if (in_array($code, [85, 86]))        return ['Averses de neige',             'snow'];
    if ($code == 95)                      return ['Orage',                        'storm'];
    if (in_array($code, [96, 99]))        return ['Orage avec grêle',             'storm'];
    return ['Conditions inconnues', 'cloud'];
}

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['synchroniser_meteo'])) {

    $url = "https://api.open-meteo.com/v1/forecast"
         . "?latitude=$latitude&longitude=$longitude"
         . "&daily=temperature_2m_max,temperature_2m_min,precipitation_sum,weathercode"
         . "&current_weather=true"
         . "&timezone=Europe%2FParis";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Évite les erreurs SSL en local sous Laragon
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $reponse_json = curl_exec($ch);
    $code_http    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $erreur_curl  = curl_error($ch);
    curl_close($ch);

    if ($reponse_json === false || $code_http !== 200) {
        header("Location: index.php?page=meteo&msg=api_err");
        exit;
    }

    $donnees = json_decode($reponse_json, true);
    if (!isset($donnees['daily']['time'])) {
        header("Location: index.php?page=meteo&msg=api_err");
        exit;
    }

    // --- Insertion / mise à jour des 7 prochains jours ---
    try {
        $bdd->beginTransaction();

        $requete_verifier = $bdd->prepare("SELECT id_meteo FROM Meteo WHERE jour = ?");
        $requete_inserer  = $bdd->prepare("INSERT INTO Meteo (jour, precM, tempM, descM) VALUES (?, ?, ?, ?)");
        $requete_modifier = $bdd->prepare("UPDATE Meteo SET precM = ?, tempM = ?, descM = ? WHERE id_meteo = ?");

        $nb_jours = count($donnees['daily']['time']);
        $nb_inseres = 0;
        $nb_modifies = 0;

        for ($i = 0; $i < $nb_jours; $i++) {
            $jour          = $donnees['daily']['time'][$i];
            $temp_max      = $donnees['daily']['temperature_2m_max'][$i] ?? 0;
            $precipitation = $donnees['daily']['precipitation_sum'][$i] ?? 0;
            $code_meteo    = $donnees['daily']['weathercode'][$i] ?? 0;
            list($description, $icone) = decrire_meteo($code_meteo);
            $description_complete = "Naucelle — " . $description;

            $requete_verifier->execute([$jour]);
            $existant = $requete_verifier->fetchColumn();

            if ($existant) {
                $requete_modifier->execute([$precipitation, $temp_max, $description_complete, $existant]);
                $nb_modifies++;
            } else {
                $requete_inserer->execute([$jour, $precipitation, $temp_max, $description_complete]);
                $nb_inseres++;
            }
        }

        $bdd->commit();
        header("Location: index.php?page=meteo&msg=ok&inseres=$nb_inseres&modifies=$nb_modifies");
        exit;
    } catch (PDOException $e) {
        if ($bdd->inTransaction()) $bdd->rollBack();
        header("Location: index.php?page=meteo&msg=sql_err");
        exit;
    }
}

$titre = "Journal de bord Météo";
include 'inclusions/entete.php';

$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':
            $nb_inseres  = isset($_GET['inseres'])  ? (int) $_GET['inseres']  : 0;
            $nb_modifies = isset($_GET['modifies']) ? (int) $_GET['modifies'] : 0;
            $msg = "<div class='alert alert-success'>" . icon('check')
                 . " Synchronisation réussie : <strong>$nb_inseres</strong> nouveau(x) relevé(s) ajouté(s), "
                 . "<strong>$nb_modifies</strong> mis à jour.</div>";
            break;
        case 'api_err':
            $msg = "<div class='alert alert-error'>" . icon('warning')
                 . " Impossible de contacter l'API Open-Meteo. Vérifiez votre connexion Internet.</div>";
            break;
        case 'sql_err':
            $msg = "<div class='alert alert-error'>" . icon('warning')
                 . " Erreur SQL lors de l'enregistrement des relevés.</div>";
            break;
    }
}

// --- Récupération du journal météo ---
$journal = [];
try {
    $journal = $bdd->query("SELECT * FROM Meteo ORDER BY jour DESC")->fetchAll();
} catch (PDOException $e) {
    $msg = "<div class='alert alert-error'>" . icon('warning')
         . " Une erreur SQL est survenue : " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Séparation : prévisions à venir vs historique passé
$aujourdhui  = date('Y-m-d');
$previsions  = [];
$historique  = [];
foreach ($journal as $entree) {
    if ($entree['jour'] >= $aujourdhui) {
        $previsions[] = $entree;
    } else {
        $historique[] = $entree;
    }
}
// Les prévisions sont du plus proche au plus lointain (ordre chronologique)
$previsions = array_reverse($previsions);
?>

<div class="container">
    <div class="header">
        <div>
            <h1>Journal de bord Météo</h1>
            <p>Conditions climatiques du jardin partagé de Naucelle (Aveyron) — données fournies par Open-Meteo.</p>
        </div>

        <form method="POST" action="index.php?page=meteo" class="m-0">
            <input type="hidden" name="synchroniser_meteo" value="1">
            <button type="submit" class="btn-sync-meteo">
                Synchroniser la météo (7 jours)
            </button>
        </form>
    </div>

    <?= $msg ?>

    <?php if (!empty($previsions)): ?>
        <div class="card">
            <h3>Prévisions à venir</h3>
            <div class="meteo-grid">
                <?php foreach ($previsions as $p):
                    // Extraire l'icône depuis la description (format "Naucelle — Pluie")
                    $parties = explode(' — ', $p['descm'] ?? '');
                    $libelle = $parties[1] ?? ($p['descm'] ?? 'N/A');
                ?>
                    <article class="meteo-card<?= $p['jour'] === $aujourdhui ? ' meteo-card-today' : '' ?>">
                        <div class="meteo-date">
                            <?php if ($p['jour'] === $aujourdhui): ?>
                                <strong>Aujourd'hui</strong>
                            <?php else: ?>
                                <strong><?= date('l', strtotime($p['jour'])) ?></strong>
                                <span><?= date('d/m', strtotime($p['jour'])) ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="meteo-icon"><?= icon(icone_meteo($libelle)) ?></div>
                        <div class="meteo-temp"><?= htmlspecialchars($p['tempm']) ?>°C</div>
                        <div class="meteo-rain">
                            <?= icon('water') ?> <?= htmlspecialchars($p['precm']) ?> mm
                        </div>
                        <div class="meteo-desc"><?= htmlspecialchars($libelle) ?></div>
                    </article>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Historique des relevés</h3>
        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Température max</th>
                        <th>Précipitations</th>
                        <th>Conditions</th>
                    </tr>
                </thead>
                <tbody>
                <?php if (empty($historique)): ?>
                    <tr><td colspan="4" class="text-center text-muted">Aucun relevé historique. Cliquez sur le bouton de synchronisation !</td></tr>
                <?php else: ?>
                    <?php foreach($historique as $r): ?>
                        <tr>
                            <td><strong><?= date("d/m/Y", strtotime($r['jour'])) ?></strong></td>
                            <td><span class="badge badge-recolte"><?= htmlspecialchars($r['tempm'] ?? 'N/A') ?> °C</span></td>
                            <td><span class="badge badge-croissance"><?= icon('water') ?> <?= htmlspecialchars($r['precm'] ?? 'N/A') ?> mm</span></td>
                            <td><?= htmlspecialchars($r['descm'] ?? 'N/A') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>
