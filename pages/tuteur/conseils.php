<?php
// pages/tuteur/conseils.php
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");
exiger_role('Tuteur');

$id_auteur = $_SESSION['id_utilisateur'];

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre    = trim($_POST['titre'] ?? '');
    $contenu  = trim($_POST['contenu'] ?? '');
    $meteo_id = !empty($_POST['meteo_id']) ? $_POST['meteo_id'] : null;

    if (empty($titre) || empty($contenu)) {
        header("Location: index.php?page=conseils&msg=missing");
        exit;
    }

    try {
        $bdd->beginTransaction();

        // 1. Insertion du conseil (RETURNING id_conseil propre à PostgreSQL)
        $requete = $bdd->prepare("INSERT INTO ConseilCultural (titreC, contenuC, date_publication, id_utilisateur_redigtuteur) VALUES (?, ?, CURRENT_DATE, ?) RETURNING id_conseil");
        $requete->execute([$titre, $contenu, $id_auteur]);
        $id_conseil_genere = $requete->fetchColumn();

        // 2. Lien météo facultatif
        if ($meteo_id && $id_conseil_genere) {
            $requeteJustifier = $bdd->prepare("INSERT INTO justifier (id_meteo, id_conseil) VALUES (?, ?)");
            $requeteJustifier->execute([$meteo_id, $id_conseil_genere]);
        }

        $bdd->commit();
        header("Location: index.php?page=conseils&msg=ok");
        exit;
    } catch (PDOException $e) {
        $bdd->rollBack();
        header("Location: index.php?page=conseils&msg=db_err");
        exit;
    }
}

$titre = "Publier un Conseil";
include 'inclusions/entete.php';

$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':       $msg = "<div class='alert alert-success'>" . icon('check') . " Conseil publié avec succès. Les adhérents en seront notifiés.</div>"; break;
        case 'missing':  $msg = "<div class='alert alert-error'>" . icon('warning') . " Le titre et le contenu sont obligatoires.</div>"; break;
        case 'db_err':   $msg = "<div class='alert alert-error'>" . icon('warning') . " Erreur lors de la publication du conseil.</div>"; break;
    }
}

// Récupération des situations météo (les 10 plus récentes) pour permettre la liaison
$meteos = $bdd->query("SELECT id_meteo, jour, descm, tempm FROM Meteo ORDER BY jour DESC LIMIT 10")->fetchAll();

// Liste des conseils déjà publiés par ce tuteur (historique sur la même page)
$requeteMine = $bdd->prepare("SELECT id_conseil, titreC, date_publication FROM ConseilCultural WHERE id_utilisateur_redigtuteur = ? ORDER BY date_publication DESC LIMIT 10");
$requeteMine->execute([$id_auteur]);
$mes_conseils = $requeteMine->fetchAll();
?>

<div class="container">
    <div class="header">
        <div>
            <h1><?= icon('pen') ?> Publier un Conseil Culturel</h1>
            <p>Partagez votre savoir avec les adhérents en liant un conseil à une situation météo.</p>
        </div>
    </div>

    <?= $msg ?>

    <div class="dashboard-grid">
        <div class="main-column">
            <div class="form-card">
                <h3>Nouveau conseil</h3>
                <form method="POST" action="index.php?page=conseils">
                    <label for="titre">Titre du conseil :</label>
                    <input type="text" name="titre" id="titre" required maxlength="120">

                    <label for="contenu">Contenu du conseil :</label>
                    <textarea name="contenu" id="contenu" rows="5" style="width:100%; padding:0.8rem; margin-bottom:1.2rem; border:none; background-color:var(--color-input-bg); border-radius:12px;" required></textarea>

                    <label for="meteo_id">Lier à une situation météo (facultatif) :</label>
                    <select name="meteo_id" id="meteo_id">
                        <option value="">— Aucune — conseil général</option>
                        <?php foreach ($meteos as $m): ?>
                            <option value="<?= $m['id_meteo'] ?>">
                                Relevé du <?= date("d/m/Y", strtotime($m['jour'])) ?> · <?= htmlspecialchars($m['tempm']) ?>°C
                                (<?= htmlspecialchars($m['descm']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <button type="submit" class="btn-primary w-100 mt-2">Publier le conseil</button>
                </form>
            </div>
        </div>

        <div class="side-column">
            <div class="card">
                <h3>Mes derniers conseils</h3>
                <?php if (empty($mes_conseils)): ?>
                    <p class="text-muted text-italic">Vous n'avez pas encore publié de conseil.</p>
                <?php else: ?>
                    <?php foreach ($mes_conseils as $c): ?>
                        <div class="notif-item">
                            <strong><?= htmlspecialchars($c['titrec']) ?></strong>
                            <span class="notif-date">Publié le <?= date("d/m/Y", strtotime($c['date_publication'])) ?></span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>
