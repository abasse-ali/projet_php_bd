<?php
/**
 * pages/tuteur/conseils.php
 *
 * Script permettant à un tuteur de rédiger et publier un conseil culturel.
 * Le conseil peut être optionnellement lié à un relevé météorologique spécifique.
 * Implémente le pattern PRG (Post-Redirect-Get) pour sécuriser la soumission du formulaire.
 */

// Sécurisation de l'accès à la page
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");

// Vérification des droits : réservé au rôle 'Tuteur'
exiger_role('Tuteur');

/**
 * @var int $id_auteur Identifiant en session du tuteur rédigeant le conseil.
 */
$id_auteur = $_SESSION['id_utilisateur'];

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération et nettoyage des données soumises
    $titre    = trim($_POST['titre'] ?? '');
    $contenu  = trim($_POST['contenu'] ?? '');
    $meteo_id = !empty($_POST['meteo_id']) ? $_POST['meteo_id'] : null;

    // Validation des champs obligatoires
    if (empty($titre) || empty($contenu)) {
        header("Location: index.php?page=conseils&msg=missing");
        exit;
    }

    try {
        // Démarrage de la transaction : on garantit l'insertion du conseil 
        // ET de son lien météo facultatif en une seule opération cohérente.
        $bdd->beginTransaction();

        // 1. Insertion du conseil
        // Utilisation de "RETURNING id_conseil" (spécificité PostgreSQL) pour obtenir 
        // directement l'ID de la ligne nouvellement insérée sans requête supplémentaire.
        $requete = $bdd->prepare("INSERT INTO ConseilCultural (titreC, contenuC, date_publication, id_utilisateur_redigtuteur) VALUES (?, ?, CURRENT_DATE, ?) RETURNING id_conseil");
        $requete->execute([$titre, $contenu, $id_auteur]);
        $id_conseil_genere = $requete->fetchColumn();

        // 2. Lien météo facultatif
        // Si l'utilisateur a sélectionné une situation météo, on lie les deux tables (table de jointure `justifier`).
        if ($meteo_id && $id_conseil_genere) {
            $requeteJustifier = $bdd->prepare("INSERT INTO justifier (id_meteo, id_conseil) VALUES (?, ?)");
            $requeteJustifier->execute([$meteo_id, $id_conseil_genere]);
        }

        // Validation et sauvegarde des modifications dans la base de données
        $bdd->commit();
        header("Location: index.php?page=conseils&msg=ok");
        exit;
    } catch (PDOException $e) {
        // Annulation des opérations en cas d'erreur
        $bdd->rollBack();
        header("Location: index.php?page=conseils&msg=db_err");
        exit;
    }
}

$titre = "Publier un Conseil";
include 'inclusions/entete.php';

// Gestion et affichage des messages d'état post-redirection
$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':       
            $msg = "<div class='alert alert-success'>" . icon('check') . " Conseil publié avec succès. Les adhérents en seront notifiés.</div>"; 
            break;
        case 'missing':  
            $msg = "<div class='alert alert-error'>" . icon('warning') . " Le titre et le contenu sont obligatoires.</div>"; 
            break;
        case 'db_err':   
            $msg = "<div class='alert alert-error'>" . icon('warning') . " Erreur lors de la publication du conseil.</div>"; 
            break;
    }
}

/**
 * @var array $meteos 
 * Extraction des 10 relevés météo les plus récents pour alimenter 
 * la liste déroulante optionnelle du formulaire.
 */
$meteos = $bdd->query("SELECT id_meteo, jour, descm, tempm FROM Meteo ORDER BY jour DESC LIMIT 10")->fetchAll();

/**
 * @var array $mes_conseils 
 * Récupération de l'historique des conseils spécifiquement publiés 
 * par le tuteur actuellement connecté.
 */
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