<?php
/**
 * pages/tuteur/alertes.php
 *
 * Script de contrôleur et vue permettant aux Tuteurs de déclarer une alerte sanitaire.
 * Ce script implémente le design pattern PRG (Post-Redirect-Get) pour éviter les doubles
 * soumissions de formulaire. Il gère l'insertion de l'alerte en base de données et 
 * déclenche la notification des parcelles voisines.
 */

// Sécurisation de base de la session
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");

// Vérification stricte des droits : Seul le rôle 'Tuteur' peut déclarer des alertes sanitaires
exiger_role('Tuteur');

// --- TRAITEMENT POST (PRG Pattern) AVANT le header ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Extraction et nettoyage des données soumises
    $id_culture  = $_POST['id_culture'] ?? '';
    $nom_menace  = trim($_POST['nom_menace'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $gravite     = $_POST['niveau_gravite'] ?? '';
    $traitement  = trim($_POST['traitement_applique'] ?? '');

    // Validation 1 : Vérification de la présence des champs obligatoires
    if (empty($id_culture) || empty($nom_menace) || empty($description) || empty($gravite)) {
        header("Location: index.php?page=alertes&msg=missing");
        exit;
    }

    // Validation 2 : La gravité doit correspondre strictement aux valeurs de l'ENUM de la base
    $gravites_autorisees = ['faible', 'modéré', 'élevé', 'critique'];
    if (!in_array($gravite, $gravites_autorisees, true)) {
        header("Location: index.php?page=alertes&msg=gravite_invalid");
        exit;
    }

    try {
        // Début de la transaction pour garantir l'intégrité de la base
        // (Insertion de l'alerte + Création des notifications)
        $bdd->beginTransaction();
        
        // Insertion de la nouvelle alerte sanitaire
        $requete = $bdd->prepare("
            INSERT INTO AlerteSanitaire (descriptionALR, date_detection, est_resolue, nom_menace, niveau_gravite, traitement_applique, id_culture_est_signalee_sur)
            VALUES (?, CURRENT_DATE, FALSE, ?, ?, ?, ?)
        ");
        $requete->execute([$description, $nom_menace, $gravite, $traitement, $id_culture]);

        // Propagation spatiale : notification automatique des exploitants des parcelles voisines et des Tuteurs
        $nb_notif = propager_alerte_voisinage($bdd, $id_culture, $nom_menace, $gravite, $_SESSION['id_utilisateur']);

        // Validation de la transaction
        $bdd->commit();
        
        // Redirection en cas de succès avec le nombre de notifications générées
        header("Location: index.php?page=alertes&msg=ok&notif=" . $nb_notif);
        exit;
        
    } catch (PDOException $e) {
        // Annulation des modifications en cas d'erreur SQL
        if ($bdd->inTransaction()) $bdd->rollBack();
        header("Location: index.php?page=alertes&msg=db_err");
        exit;
    }
}

$titre = "Déclarer une alerte sanitaire";
include 'inclusions/entete.php';

// Gestion de l'affichage des messages de notification post-redirection
$msg = "";
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'ok':
            $nb = isset($_GET['notif']) ? (int)$_GET['notif'] : 0;
            $sfx = $nb > 0
                ? " <strong>$nb exploitant(s) voisin(s) ou tuteur(s)</strong> ont été notifiés automatiquement via l'association spatiale <code>est_voisine_de</code>."
                : " Aucune parcelle voisine active à notifier.";
            $msg = "<div class='alert alert-success'>" . icon('check') . " L'alerte sanitaire a bien été déclarée." . $sfx . "</div>";
            break;
        case 'missing':         
            $msg = "<div class='alert alert-error'>" . icon('warning') . " Veuillez remplir tous les champs obligatoires.</div>"; 
            break;
        case 'gravite_invalid': 
            $msg = "<div class='alert alert-error'>" . icon('warning') . " Niveau de gravité non reconnu.</div>"; 
            break;
        case 'db_err':          
            $msg = "<div class='alert alert-error'>" . icon('warning') . " Erreur lors de l'enregistrement de l'alerte.</div>"; 
            break;
    }
}

/**
 * @var array $cultures_actives 
 * Récupération de toutes les cultures actives (sur une attribution non clôturée).
 * Sert à alimenter la liste déroulante `<select>` du formulaire de déclaration d'alerte.
 */
$cultures_actives = $bdd->query("
    SELECT c.id_culture, p.nom_variete, parc.secteurP, parc.numeroP, u.prenomU, u.nomU
    FROM Culture c
    JOIN Plante p ON c.id_plante_definir = p.id_plante
    JOIN Attribution a ON c.id_attribution_seffectuer = a.id_attribution
    JOIN Parcelle parc ON a.id_parcelle_assigner = parc.id_parcelle
    JOIN Utilisateur u ON a.id_utilisateur_fournir = u.id_utilisateur
    WHERE c.statut_Culture = 'en cours'
      AND (a.date_fin IS NULL OR a.date_fin > CURRENT_DATE)
    ORDER BY parc.secteurP, parc.numeroP
")->fetchAll();
?>

<div class="container">
    <div class="header">
        <div>
            <h1><?= icon('warning') ?> Déclarer une Alerte Sanitaire</h1>
            <p>Signalez une maladie ou un nuisible détecté sur une parcelle pour éviter sa propagation.</p>
        </div>
    </div>

    <?= $msg ?>

    <div class="form-card" style="max-width: 800px; margin: auto;">
        <?php if (empty($cultures_actives)): ?>
            <div class="alert alert-warning">
                <?= icon('warning') ?> Aucune culture en cours sur le terrain pour le moment. Aucune alerte ne peut être déclarée.
            </div>
        <?php else: ?>
            <form method="POST" action="index.php?page=alertes">

                <label for="id_culture">Culture touchée (Parcelle) :</label>
                <select name="id_culture" id="id_culture" required>
                    <option value="" disabled selected>-- Sélectionner la culture infectée --</option>
                    <?php foreach($cultures_actives as $c): ?>
                        <option value="<?= $c['id_culture'] ?>">
                            Secteur <?= htmlspecialchars($c['secteurp']) ?>-<?= htmlspecialchars($c['numerop']) ?> :
                            <?= htmlspecialchars($c['nom_variete']) ?>
                            (<?= htmlspecialchars($c['prenomu'] . ' ' . $c['nomu']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="form-grid-half">
                    <div>
                        <label for="nom_menace">Nom de la menace :</label>
                        <input type="text" name="nom_menace" id="nom_menace" placeholder="Ex: Mildiou, Pucerons..." required>
                    </div>
                    <div>
                        <label for="niveau_gravite">Niveau de gravité :</label>
                        <select name="niveau_gravite" id="niveau_gravite" required>
                            <option value="faible">Faible</option>
                            <option value="modéré">Modéré</option>
                            <option value="élevé">Élevé</option>
                            <option value="critique">Critique</option>
                        </select>
                    </div>
                </div>

                <label for="description">Description et symptômes observés :</label>
                <textarea name="description" id="description" rows="4" style="width:100%; padding:0.8rem; margin-bottom:1.2rem; border-radius:12px; border:none; background-color:var(--color-input-bg);" required></textarea>

                <label for="traitement_applique">Traitement préconisé / appliqué :</label>
                <input type="text" name="traitement_applique" id="traitement_applique" placeholder="Ex: Bouillie bordelaise, Savon noir...">

                <button type="submit" class="btn-danger w-100">Lancer l'alerte</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'inclusions/pied_de_page.php'; ?>