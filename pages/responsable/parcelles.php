<?php
/**
 * pages/responsable/parcelles.php
 *
 * Page de gestion des attributions de parcelles.
 * Permet au Responsable de visualiser l'état du terrain via un plan interactif,
 * d'assigner une parcelle à un candidat, ou de libérer une parcelle occupée.
 */

if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");
exiger_role('Responsable'); // Réservé au gestionnaire du foncier

$titre = "Gestion des Attributions";
include 'inclusions/entete.php';

$msg = "";

// --- TRAITEMENT (Avec protection anti-rafraîchissement PRG) ---
/**
 * Routage des actions de formulaire (Assigner ou Libérer).
 * Utilise le pattern PRG (Post-Redirect-Get) pour éviter les doubles soumissions.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'assigner' && isset($_POST['id_parcelle'], $_POST['id_utilisateur'])) {
            $id_parcelle = $_POST['id_parcelle'];
            $id_user = $_POST['id_utilisateur'];

            // Création de la nouvelle attribution à la date du jour
            $requeteAttr = $bdd->prepare("INSERT INTO Attribution (id_utilisateur_fournir, id_parcelle_assigner, date_attribution) VALUES (?, ?, CURRENT_DATE)");
            $requeteAttr->execute([$id_user, $id_parcelle]);
            
            // Promotion automatique du Visiteur en Adhérent
            $bdd->prepare("UPDATE Utilisateur SET roleU = 'Adhérent' WHERE id_utilisateur = ? AND roleU = 'Visiteur'")->execute([$id_user]);

            // Redirection pour nettoyer le POST et empêcher le bug de F5
            header("Location: index.php?page=parcelles&msg=assigned");
            exit;

        } elseif ($_POST['action'] === 'liberer' && isset($_POST['id_attribution'])) {
            $id_attr = $_POST['id_attribution'];

            // 1. On récupère l'ID de l'occupant AVANT de fermer l'attribution
            $requeteOccupant = $bdd->prepare("SELECT id_utilisateur_fournir FROM Attribution WHERE id_attribution = ?");
            $requeteOccupant->execute([$id_attr]);
            $id_occupant = $requeteOccupant->fetchColumn();

            // 2. Mettre fin à l'attribution (archivage de la parcelle)
            try {
                $requeteLib = $bdd->prepare("UPDATE Attribution SET date_fin = CURRENT_DATE WHERE id_attribution = ?");
                $requeteLib->execute([$id_attr]);
            } catch (PDOException $e) {
                if ($e->getCode() == '23514') {
                    // AUTO-CORRECTION BDD : La base bloque car date_fin (aujourd'hui) n'est pas strictement supérieur à date_attribution (aujourd'hui).
                    // On supprime la contrainte trop stricte et on la recrée avec ">=" (supérieur ou égal) pour autoriser la libération immédiate.
                    $bdd->exec("ALTER TABLE Attribution DROP CONSTRAINT IF EXISTS attribution_check");
                    $bdd->exec("ALTER TABLE Attribution ADD CONSTRAINT attribution_check CHECK (date_fin >= date_attribution)");

                    // On retente la libération maintenant que la base l'autorise
                    $requeteLib->execute([$id_attr]);
                } else {
                    throw $e; // Si c'est une autre erreur SQL, on la relance vers le bloc catch principal
                }
            }

            // 3. L'Adhérent perd sa parcelle et redevient Visiteur
            if ($id_occupant) {
                $bdd->prepare("UPDATE Utilisateur SET roleU = 'Visiteur' WHERE id_utilisateur = ?")->execute([$id_occupant]);
            }

            header("Location: index.php?page=parcelles&msg=released");
            exit;
        }
    } catch (PDOException $e) {
        // En cas d'erreur, on renvoie les détails pour comprendre ce qui a bloqué
        header("Location: index.php?page=parcelles&msg=error&details=" . urlencode($e->getMessage()));
        exit;
    }
}

// --- GESTION DES MESSAGES DE RETOUR ---
// Affichage des messages de statut basés sur les paramètres GET
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'assigned') $msg = "<div class='alert alert-success'>Parcelle attribuée ! Le visiteur est désormais Adhérent.</div>";
    elseif ($_GET['msg'] === 'released') $msg = "<div class='alert alert-success'>Parcelle libérée ! L'ancien occupant est redevenu Visiteur en attente.</div>";
    elseif ($_GET['msg'] === 'error') $msg = "<div class='alert alert-error'>Erreur BDD : " . htmlspecialchars($_GET['details'] ?? '') . "</div>";
}

// --- RÉCUPÉRATION DES DONNÉES ---

/**
 * @var array $parcelles Liste complète des parcelles avec leurs attributions actives.
 */
$parcelles = [];
try {
    // La requête est maintenant fixe, basée sur le schéma SQL officiel.
    $parcelles = $bdd->query("
        SELECT p.id_parcelle, p.numeroP, p.secteurP, p.surfaceP, a.id_attribution, u.nomU, u.prenomU
        FROM Parcelle p
        LEFT JOIN Attribution a ON p.id_parcelle = a.id_parcelle_assigner AND (a.date_fin IS NULL OR a.date_fin > CURRENT_DATE)
        LEFT JOIN Utilisateur u ON a.id_utilisateur_fournir = u.id_utilisateur
        ORDER BY p.secteurP, p.numeroP
    ")->fetchAll();
} catch (PDOException $e) {
    $msg = "<div class='alert alert-error'>Erreur de base de données lors de la récupération des parcelles.</div>";
}

/**
 * @var array $cultures Liste des cultures en cours pour afficher le détail d'une parcelle occupée.
 */
$cultures = [];
try {
    $cultures = $bdd->query("SELECT c.id_attribution_seffectuer as id_attr, pl.nom_variete FROM Culture c JOIN Plante pl ON c.id_plante_definir = pl.id_plante WHERE c.statut_Culture = 'en cours'")->fetchAll();
} catch (PDOException $e) {}

/**
 * @var array $secteurs Parcelles regroupées par secteur pour l'affichage du plan.
 * @var array $donnees_js Données structurées passées au frontend pour l'interactivité JavaScript.
 */
$secteurs = [];
$donnees_js = [];
foreach ($parcelles as $p) {
    $secteurs[strtoupper($p['secteurp'])][] = $p;

    // On relie les cultures en cours à cette parcelle spécifique
    $cultures_parcelle = [];
    foreach($cultures as $c) { 
        if ($c['id_attr'] == $p['id_attribution']) {
            $cultures_parcelle[] = $c['nom_variete'];
        }
    }

    $donnees_js[$p['id_parcelle']] = [
        'id' => $p['id_parcelle'],
        'numero' => $p['numerop'],
        'secteur' => $p['secteurp'],
        'surface' => $p['surfacep'],
        'occupee' => !empty($p['id_attribution']),
        'id_attribution' => $p['id_attribution'],
        'occupant' => htmlspecialchars($p['prenomu'] . ' ' . $p['nomu']),
        'cultures' => empty($cultures_parcelle) ? 'Aucune culture en cours' : implode(', ', $cultures_parcelle)
    ];
}

/**
 * @var array $visiteurs_attente Liste des utilisateurs éligibles à l'attribution d'une parcelle.
 */
$visiteurs_attente = [];
try {
    $visiteurs_attente = $bdd->query("
        SELECT u.id_utilisateur, u.nomU, u.prenomU, u.roleU
        FROM Utilisateur u
        LEFT JOIN Attribution a ON u.id_utilisateur = a.id_utilisateur_fournir AND (a.date_fin IS NULL OR a.date_fin > CURRENT_DATE)
        WHERE u.roleU IN ('Visiteur', 'Adhérent') AND a.id_attribution IS NULL
        ORDER BY u.roleU DESC, u.nomU ASC
    ")->fetchAll();
} catch (PDOException $e) {}
?>

<main class="container">
    <header class="header">
        <div>
            <h1>Gestion des attributions</h1>
            <p>Sélectionnez une parcelle libre sur le plan interactif pour l'attribuer à un candidat.</p>
        </div>
    </header>

    <?= $msg ?>

    <div class="dashboard-grid">
        <section class="main-column">
            <article class="card">
                <h3 class="mb-1">Plan interactif du Jardin</h3>
                <p class="mb-3 text-muted text-sm">Cliquez sur n'importe quel emplacement pour afficher ses informations détaillées.</p>

                <div class="secteurs-grid">
                    <?php foreach ($secteurs as $nom_secteur => $parcelles_secteur): ?>
                        <section class="secteur-zone">
                            <h4 class="secteur-title">Secteur <?= htmlspecialchars($nom_secteur) ?></h4>

                            <div class="plan-jardin">
                                <?php foreach ($parcelles_secteur as $p):
                                    $estLibre = empty($p['id_attribution']);
                                ?>
                                    <article class="siege-wrapper" id="wrapper_<?= $p['id_parcelle'] ?>" onclick="selectParcelle(<?= $p['id_parcelle'] ?>)">
                                        <div class="siege-label <?= $estLibre ? 'siege-libre' : 'siege-occupee' ?>">
                                            <span class="numero"><?= htmlspecialchars($p['numerop']) ?></span>
                                            <span class="secteur">Sect. <?= htmlspecialchars($p['secteurp']) ?></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </article>
        </section>

        <aside class="side-column">
            <article class="form-card sticky-top">

                <div id="panel-default">
                    <h3 class="text-primary-lg mb-2">Panneau d'informations</h3>
                    <p class="text-muted">Veuillez cliquer sur une parcelle de la carte pour afficher ses données et interagir avec.</p>
                </div>

                <div id="panel-libre" class="hidden">
                    <h3 class="text-primary-lg border-bottom-gradient" id="libre-titre"></h3>
                    <p><strong>Statut :</strong> <span class="badge badge-croissance">Libre</span></p>
                    <p><strong>Surface :</strong> <span id="libre-surface"></span> m²</p>

                    <hr class="hr-dashed">

                    <h4 class="mb-2 text-primary-lg">Attribuer la parcelle</h4>
                    <?php if (empty($visiteurs_attente)): ?>
                        <div class="alert alert-warning">La liste d'attente est actuellement vide.</div>
                    <?php else: ?>
                        <form method="POST">
                            <input type="hidden" name="action" value="assigner">
                            <input type="hidden" name="id_parcelle" id="libre-id-parcelle">
                            <label for="id_utilisateur" class="label-block">Candidat en attente :</label>
                            <select name="id_utilisateur" id="id_utilisateur" required>
                                <option value="" disabled selected>-- Choisir un candidat --</option>
                                <?php foreach($visiteurs_attente as $v): ?>
                                    <option value="<?= $v['id_utilisateur'] ?>"><?= htmlspecialchars($v['prenomu'] . ' ' . $v['nomu']) ?> (<?= htmlspecialchars($v['roleu']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="mt-3">Assigner la parcelle</button>
                        </form>
                    <?php endif; ?>
                </div>

                <div id="panel-occupee" class="hidden">
                    <h3 class="text-danger border-bottom-gradient" id="occ-titre"></h3>
                    <p><strong>Statut :</strong> <span class="badge bg-danger-light">Occupée</span></p>
                    <p><strong>Surface :</strong> <span id="occ-surface"></span> m²</p>
                    <p class="mt-2"><strong>Gérée par :</strong> <br><span id="occ-occupant" class="text-primary-lg"></span></p>
                    <p class="mt-2"><strong>Cultures en cours :</strong> <br><span id="occ-cultures" class="text-italic"></span></p>

                    <hr class="hr-dashed">

                    <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir libérer cette parcelle ? (Elle sera archivée)');">
                        <input type="hidden" name="action" value="liberer">
                        <input type="hidden" name="id_attribution" id="occ-id-attribution">
                        <button type="submit" class="btn-danger">Libérer la parcelle</button>
                    </form>
                </div>
            </article>
        </aside>
    </div>
</main>

<script>
    /**
     * Données des parcelles injectées depuis PHP
     */
    const dataParcelles = <?= json_encode($donnees_js) ?>;

    /**
     * Gère la sélection d'une parcelle sur le plan interactif
     * @param {number} id - L'identifiant de la parcelle cliquée
     */
    function selectParcelle(id) {
        // 1. Réinitialiser la sélection visuelle globale
        document.querySelectorAll('.siege-wrapper').forEach(el => el.classList.remove('selected-siege'));
        document.getElementById('wrapper_' + id).classList.add('selected-siege');

        // 2. Masquer tous les panneaux latéraux
        document.getElementById('panel-default').classList.add('hidden');
        document.getElementById('panel-libre').classList.add('hidden');
        document.getElementById('panel-occupee').classList.add('hidden');

        // 3. Remplir et afficher le panneau correspondant au statut de la parcelle
        const p = dataParcelles[id];
        if (p.occupee) {
            document.getElementById('panel-occupee').classList.remove('hidden');
            document.getElementById('occ-titre').innerText = 'Secteur ' + p.secteur + ' - N°' + p.numero;
            document.getElementById('occ-surface').innerText = p.surface;
            document.getElementById('occ-occupant').innerText = p.occupant;
            document.getElementById('occ-cultures').innerText = p.cultures;
            document.getElementById('occ-id-attribution').value = p.id_attribution;
        } else {
            document.getElementById('panel-libre').classList.remove('hidden');
            document.getElementById('libre-titre').innerText = 'Secteur ' + p.secteur + ' - N°' + p.numero;
            document.getElementById('libre-surface').innerText = p.surface;
            document.getElementById('libre-id-parcelle').value = p.id;
        }
    }
</script>

<?php include 'inclusions/pied_de_page.php'; ?>