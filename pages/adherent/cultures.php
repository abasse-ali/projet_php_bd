<?php
// pages/adherent/cultures.php
if (!isset($_SESSION['id_utilisateur'])) die("Accès interdit");

// Sécurisation : Seuls les Adhérents (et l'Administrateur) ont un jardin
// Correction du bug de logique d'accès pour l'Administrateur.
// Un administrateur n'a pas de jardin personnel. S'il accède à cette page,
// on le redirige immédiatement vers son tableau de bord principal. (Using roleU)
if (isset($_SESSION['roleU']) && $_SESSION['roleU'] === 'Administrateur') {
    header("Location: index.php?page=tableau_bord");
    exit;
}

// Sécurisation : Adhérents ET Visiteurs (pour voir leur statut d'attente)
exiger_role(['Adhérent', 'Visiteur']);

$id_utilisateur = $_SESSION['id_utilisateur'];
$msg_contrib = "";
$msg_culture = "";

// --- GESTION DES MESSAGES DE RETOUR (PRG Pattern) ---
// Variables globales initialisées proprement pour éviter les undefined
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'add_ok') $msg_culture = "<div class='alert alert-success'>Nouvelle plantation enregistrée !</div>";
    elseif ($_GET['msg'] === 'update_ok') $msg_culture = "<div class='alert alert-success'>Statut de la culture mis à jour.</div>";
    elseif ($_GET['msg'] === 'date_err') $msg_culture = "<div class='alert alert-error'>La date de semis ne peut pas être antérieure à l'attribution de votre parcelle.</div>";
    elseif ($_GET['msg'] === 'contrib_ok') $msg_contrib = "<div class='alert alert-success'>Votre proposition d'apport a bien été envoyée au Trésorier pour validation.</div>";
    elseif ($_GET['msg'] === 'contrib_err') $msg_contrib = "<div class='alert alert-error'>Erreur : Veuillez spécifier au moins un article avec sa quantité.</div>";
}

// --- TRAITEMENT : Si le visiteur soumet une proposition de contribution ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['type_apport'])) {
    $type_apport = $_POST['type_apport'];
    $desc_parts = []; // On va stocker les éléments ici pour les formater joliment

    // Traitement des Semences (si "Graines" ou "Mixte" est choisi)
    if (($type_apport === 'Graines' || $type_apport === 'Mixte') && !empty($_POST['semence_type'])) {
        $graines = [];
        for ($i = 0; $i < count($_POST['semence_type']); $i++) {
            $type = trim($_POST['semence_type'][$i]);
            $qte = trim($_POST['semence_qte'][$i]);
            if (!empty($type) && !empty($qte)) {
                $graines[] = htmlspecialchars($type) . " (" . htmlspecialchars($qte) . "g)";
            }
        }
        if (!empty($graines)) $desc_parts[] = "Semences : " . implode(", ", $graines);
    }

    // Traitement des Outils (si "Materiel" ou "Mixte" est choisi)
    if (($type_apport === 'Materiel' || $type_apport === 'Mixte') && !empty($_POST['outil_type'])) {
        $outils = [];
        for ($i = 0; $i < count($_POST['outil_type']); $i++) {
            $type = trim($_POST['outil_type'][$i]);
            $qte = trim($_POST['outil_qte'][$i]);
            if (!empty($type) && !empty($qte)) {
                $outils[] = htmlspecialchars($type) . " (" . htmlspecialchars($qte) . " unité(s))";
            }
        }
        if (!empty($outils)) $desc_parts[] = "Outils : " . implode(", ", $outils);
    }

    $desc = implode(" | ", $desc_parts);

    if (!empty($desc)) {
        // Correction du nom de la colonne 'statutc' en 'statutC' pour correspondre au schéma SQL.
        $requeteContrib = $bdd->prepare("INSERT INTO Contribution (date_contribution, statutC, type_apport, description, id_utilisateur_apporter) VALUES (CURRENT_DATE, 'en attente', ?, ?, ?)");
        if ($requeteContrib->execute([$type_apport, $desc, $id_utilisateur])) {
                header("Location: index.php?page=cultures&msg=contrib_ok");
                exit;
        }
    } else {
            header("Location: index.php?page=cultures&msg=contrib_err");
            exit;
    }
}

// --- TRAITEMENT : Actions de l'Adhérent sur ses Cultures (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_culture'])) {
    if ($_POST['action_culture'] === 'add') {
        $id_plante = $_POST['id_plante_definir'];
        $date_semis = $_POST['date_semis'];
        $date_recolte = $_POST['date_recolte_prevue'];
        $id_attribution = $_POST['id_attribution_seffectuer'];
        $date_attr = $_POST['date_attribution'];

        // Contrainte forte : La date de semis doit être >= à la date d'attribution
        if ($date_semis < $date_attr) {
            header("Location: index.php?page=cultures&msg=date_err");
            exit;
        } else {
            // Insertion avec le statut par défaut 'en cours'
            $requeteAdd = $bdd->prepare("INSERT INTO Culture (date_semis, statut_Culture, date_recolte_prevue, id_attribution_seffectuer, id_plante_definir) VALUES (?, 'en cours', ?, ?, ?)");
            if ($requeteAdd->execute([$date_semis, $date_recolte, $id_attribution, $id_plante])) {
                header("Location: index.php?page=cultures&msg=add_ok");
                exit;
            }
        }
    } elseif ($_POST['action_culture'] === 'update_status') {
        $id_culture = $_POST['id_culture'];
        $nouveau_statut = $_POST['nouveau_statut']; // Accepte 'terminée' ou 'abandonnée'
        
        $requeteUpdate = $bdd->prepare("UPDATE Culture SET statut_Culture = ? WHERE id_culture = ?");
        if ($requeteUpdate->execute([$nouveau_statut, $id_culture])) {
            header("Location: index.php?page=cultures&msg=update_ok");
            exit;
        }
    }
}

// --- INCLUSION DU HEADER (Maintenant que tout traitement de redirection est terminé) ---
$titre = "Mon Jardin";
include 'inclusions/entete.php';

// 1. Vérification de l'attribution active de l'utilisateur
$attribution = obtenir_attribution_active($bdd, $id_utilisateur);

// 2. Récupération des cultures liées à l'attribution (Toutes, pas seulement 'en cours')
$cultures = $attribution ? obtenir_cultures_par_attribution($bdd, $attribution['id_attribution']) : [];

// 3. Récupération de toutes les plantes pour le menu déroulant de plantation
$plantes = $bdd->query("SELECT id_plante, nom_variete FROM Plante ORDER BY nom_variete")->fetchAll();

// 4. Récupérer les 5 dernières notifications non lues
$notifs = obtenir_notifications_non_lues($bdd, $id_utilisateur);

// 5. Dernier relevé météo enregistré dans le journal (pour la carte "Météo")
$dernier_meteo = $bdd->query("SELECT jour, tempM, precM, descM FROM Meteo ORDER BY jour DESC LIMIT 1")->fetch();
?>

<div class="container">
    <div class="header">
        <div>
            <h1>Bienvenue, <?= htmlspecialchars($_SESSION['nom'] ?? 'Utilisateur') ?></h1>
            <p><?= ($_SESSION['roleU'] ?? '') === 'Visiteur' ? 'Dossier de Candidature' : 'Espace Adhérent' ?></p>
        </div>
        <!-- Le bouton déconnexion est maintenant dans le header global -->
    </div>

    <div class="dashboard-grid">
        
        <!-- COLONNE PRINCIPALE -->
        <div class="main-column">
            
            <!-- Messages globaux -->
            <?php if ($msg_culture): ?>
                <div class="mb-negative"><?= $msg_culture ?></div>
            <?php endif; ?>
            <?php if ($msg_contrib): ?>
                <div class="mb-negative"><?= $msg_contrib ?></div>
            <?php endif; ?>

            <?php if ($attribution): ?>
                <!-- MA PARCELLE -->
                <div class="card">
                    <h3 class="flex-title">
                        <svg class="icon-md icon-duotone-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                        Ma Parcelle : <?= htmlspecialchars($attribution['secteurp']) . '-' . htmlspecialchars($attribution['numerop']) ?>
                    </h3>
                    <div class="info-grid">
                        <div class="info-item">
                            <svg class="icon-md icon-duotone-surface" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h6v6"/><path d="M9 21H3v-6"/><path d="M21 3l-7 7"/><path d="M3 21l7-7"/></svg>
                            <span><strong>Surface :</strong> <?= $attribution['surfacep'] ?> m²</span>
                        </div>
                        <div class="info-item">
                            <svg class="icon-md icon-duotone-date" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <span><strong>Attribuée le :</strong> <?= date("d/m/Y", strtotime($attribution['date_attribution'])) ?></span>
                        </div>
                        <div class="info-item">
                            <svg class="icon-md icon-duotone-quality" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/></svg>
                            <span><strong>Cultures en cours :</strong>
                                <?= count(array_filter($cultures, fn($c) => $c['statut_culture'] === 'en cours')) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- MES CULTURES -->
                <div class="card">
                    <h3 class="flex-title">
                        <svg class="icon-md icon-duotone-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"></path><rect x="8" y="2" width="8" height="4" rx="1" ry="1"></rect></svg>
                        Mes Cultures
                    </h3>
                    <div class="cultures-grid">
                        <?php if (empty($cultures)): ?>
                            <p class="text-muted col-span-full">Aucune culture sur cette parcelle. Lancez vos premiers semis !</p>
                        <?php else: ?>
                            <?php foreach ($cultures as $c): ?>
                                <article class="card-culture <?= $c['statut_culture'] === 'en cours' ? 'status-encours' : 'status-terminee' ?>">
                                    <h4 class="culture-title"><?= htmlspecialchars($c['nom_variete']) ?></h4>
                                    <p><strong>Semé le :</strong> <?= date("d/m/Y", strtotime($c['date_semis'])) ?></p>
                                    <p><strong>Récolte :</strong> <?= date("d/m/Y", strtotime($c['date_recolte_prevue'])) ?></p>
                                    
                                    <div class="culture-actions">
                                        <span class="badge <?= $c['statut_culture'] === 'en cours' ? 'badge-croissance' : 'badge-terminee' ?> mb-1 d-inline-block">
                                            <?= ucfirst($c['statut_culture']) ?>
                                        </span>
                                        
                                        <?php if ($c['statut_culture'] === 'en cours'): ?>
                                            <div class="culture-action-buttons">
                                                <form method="POST" class="form-inline-action">
                                                    <input type="hidden" name="action_culture" value="update_status">
                                                    <input type="hidden" name="id_culture" value="<?= $c['id_culture'] ?>">
                                                    <input type="hidden" name="nouveau_statut" value="terminée">
                                                    <button type="submit" class="btn-small btn-secondary btn-icon-text-left" title="Marquer comme terminée">
                                                        <svg class="icon-sm" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>
                                                        Terminée
                                                    </button>
                                                </form>
                                                <form method="POST" class="form-inline-action" onsubmit="return confirm('Êtes-vous sûr de vouloir abandonner cette culture ?');">
                                                    <input type="hidden" name="action_culture" value="update_status">
                                                    <input type="hidden" name="id_culture" value="<?= $c['id_culture'] ?>">
                                                    <input type="hidden" name="nouveau_statut" value="abandonnée">
                                                    <button type="submit" class="btn-small btn-outline-danger" title="Abandonner">X</button>
                                                </form>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </article>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- NOUVELLE PLANTATION -->
                <div class="card" id="plantation">
                    <h3 class="flex-title">
                        <svg class="icon-md icon-duotone-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                        Nouvelle Plantation
                    </h3>
                    <form method="POST">
                        <input type="hidden" name="action_culture" value="add">
                        <input type="hidden" name="id_attribution_seffectuer" value="<?= $attribution['id_attribution'] ?>">
                        <input type="hidden" name="date_attribution" value="<?= $attribution['date_attribution'] ?>">
                        
                        <div class="form-grid-auto">
                            <div>
                                <label for="id_plante">Variété de la plante :</label>
                                <select name="id_plante_definir" id="id_plante" required class="mb-0">
                                    <option value="" disabled selected>-- Variété --</option>
                                    <?php foreach($plantes as $p): ?>
                                        <option value="<?= $p['id_plante'] ?>"><?= htmlspecialchars($p['nom_variete']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="date_semis">Date du semis :</label>
                                <input type="date" name="date_semis" id="date_semis" value="<?= date('Y-m-d') ?>" required class="mb-0">
                            </div>
                            <div>
                                <label for="date_recolte_prevue">Date de récolte estimée :</label>
                                <input type="date" name="date_recolte_prevue" id="date_recolte_prevue" required class="mb-0">
                            </div>
                        </div>
                        <button type="submit" class="btn-primary w-100 mt-3 btn-icon-text">Planter</button>
                    </form>
                </div>

            <?php else: ?>
                <!-- VISITEUR SANS PARCELLE -->
                <div class="card alert alert-warning mb-0">
                    <h3 class="flex-title mt-0">
                        <svg class="icon-md" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Aucune parcelle attribuée
                    </h3>
                    <p>Vous n'avez pas de parcelle attribuée pour le moment. Votre dossier est en cours de traitement par le Responsable.</p>
                </div>
                
                <?php if (($_SESSION['roleU'] ?? '') === 'Visiteur'): ?>
                    <div class="card form-card">
                        <h3 class="flex-title">
                            <svg class="icon-md icon-duotone-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>
                            Faire un don à la communauté
                        </h3>
                        <p class="text-muted mb-3">Pour accelerer la validation de votre dossier et devenir Adherent actif, vous pouvez faire un apport (graines, materiel) au jardin.</p>
                        
                        <form method="POST">
                            <label for="choix_apport">Que souhaitez-vous apporter ?</label>
                            <select name="type_apport" id="choix_apport" onchange="toggleApport()" required>
                                <option value="Graines" selected>Graines / Semences</option>
                                <option value="Materiel">Outils / Materiel</option>
                                <option value="Mixte">Les deux</option>
                            </select>
                            
                            <div id="bloc-graines" class="contribution-block">
                                <h4>Vos Semences</h4>
                                <div id="liste-graines">
                                    <div class="form-inline form-inline-contribution">
                                        <input type="text" name="semence_type[]" placeholder="Variete (ex: Tomate Marmande)">
                                        <input type="number" name="semence_qte[]" placeholder="Qte (g)" min="1">
                                    </div>
                                </div>
                                <button type="button" onclick="ajouterLigne('liste-graines', 'semence_type[]', 'semence_qte[]', 'Variete (ex: Radis)', 'Qte (g)')" class="btn-add-item mt-2">+ Ajouter une autre semence</button>
                            </div>

                            <div id="bloc-outils" class="contribution-block hidden">
                                <h4>Vos Outils</h4>
                                <div id="liste-outils">
                                    <div class="form-inline form-inline-contribution">
                                        <input type="text" name="outil_type[]" placeholder="Type d'outil (ex: Rateau)">
                                        <input type="number" name="outil_qte[]" placeholder="Qte (unité)" min="1">
                                    </div>
                                </div>
                                <button type="button" onclick="ajouterLigne('liste-outils', 'outil_type[]', 'outil_qte[]', 'Type d\'outil (ex: Pelle)', 'Qte (unité)')" class="btn-add-item mt-2">+ Ajouter un autre outil</button>
                            </div>

                            <button type="submit" class="btn-primary mt-3">Soumettre ma proposition</button>
                        </form>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

        </div> <!-- Fin de la colonne principale -->


        <!-- COLONNE LATERALE -->
        <div class="side-column">
            
            <!-- ACTIONS RAPIDES -->
            <div class="card">
                <h3 class="flex-title">
                    <svg class="icon-md icon-duotone-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon></svg>
                    Actions Rapides
                </h3>
                <div class="actions-grid">
                    <?php if ($_SESSION['roleU'] === 'Adhérent'): ?>
                        <a href="index.php?page=outils" class="btn-action">
                            <svg class="action-icon icon-duotone-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>
                            Réserver un outil
                        </a>
                        <a href="#plantation" class="btn-action">
                            <svg class="action-icon icon-duotone-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                            Nouvelle Plantation
                        </a>
                        <a href="index.php?page=meteo" class="btn-action">
                            <svg class="action-icon icon-duotone-water" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                            Journal Météo
                        </a>
                        <a href="index.php?page=alertes_adherent" class="btn-action btn-alert">
                            <svg class="action-icon icon-duotone-danger" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                            Alerte Maladie
                        </a>
                    <?php else: ?>
                        <!-- Vue restreinte pour le Visiteur -->
                        <a href="index.php?page=plantes" class="btn-action">
                            <svg class="icon-md icon-duotone-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 5 17 4.48 19 2c1 2 2 4.18 2 8 0 5.5-4.78 10-10 10Z"/><path d="M2 21c0-3 1.85-5.36 5.08-6C9.5 14.52 12 13 13 12"/></svg>
                            Parcourir le catalogue
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- MÉTÉO (Dernier relevé enregistré dans la table Meteo) -->
            <?php if ($attribution): ?>
            <div class="card">
                <h3 class="flex-title">
                    <svg class="icon-md icon-duotone-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.5 19H9a7 7 0 1 1 6.71-9h1.79a4.5 4.5 0 1 1 0 9Z"/></svg>
                    Météo
                    <?php if ($dernier_meteo): ?>
                        <span class="text-muted" style="font-weight: normal; font-size: 0.85rem; margin-left: auto;">
                            <?= date("d/m/Y", strtotime($dernier_meteo['jour'])) ?>
                        </span>
                    <?php endif; ?>
                </h3>
                <?php if ($dernier_meteo): ?>
                    <?php
                        $temp = is_numeric($dernier_meteo['tempm']) ? (float) $dernier_meteo['tempm'] : null;
                        $prec = is_numeric($dernier_meteo['precm']) ? (float) $dernier_meteo['precm'] : null;
                        // Conseil simple basé sur les précipitations du jour
                        if ($prec === null) {
                            $conseil = "Conseil : aucune donnée de précipitations disponible.";
                        } elseif ($prec >= 5) {
                            $conseil = "Conseil : arrosage non requis (précipitations significatives).";
                        } elseif ($prec >= 1) {
                            $conseil = "Conseil : arrosage léger conseillé en complément de la pluie.";
                        } else {
                            $conseil = "Conseil : pensez à arroser vos cultures aujourd'hui.";
                        }
                    ?>
                    <div class="meteo-flex-around">
                        <div class="meteo-stat">
                            <svg class="icon-xl icon-duotone-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>
                            <span class="meteo-temp"><?= $temp !== null ? htmlspecialchars(round($temp, 1)) . '°C' : 'N/A' ?></span>
                        </div>
                        <div class="meteo-divider"></div>
                        <div class="meteo-stat">
                            <svg class="icon-xl icon-duotone-water" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>
                            <span class="text-primary-lg"><?= $prec !== null ? htmlspecialchars(round($prec, 1)) . ' mm' : 'N/A' ?></span>
                        </div>
                    </div>
                    <div class="meteo-advice-box text-center mt-2">
                        <?= htmlspecialchars($conseil) ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted text-italic">Aucun relevé météo disponible.
                        <a href="index.php?page=meteo">Synchroniser la météo</a>
                    </p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- NOTIFICATIONS -->
            <div class="card">
                <div class="notif-header">
                    <h3 class="flex-title mb-0">
                        <!-- Remplacement par classe CSS (respect contraintes académiques) -->
                        <svg class="icon-md icon-bell-custom" viewBox="0 0 24 24" fill="none" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>
                        Notifications
                        <?php if (!empty($notifs)): ?>
                            <span class="badge badge-recolte" style="margin-left: auto;"><?= count($notifs) ?></span>
                        <?php endif; ?>
                    </h3>
                </div>
                <?php if (!empty($notifs)): ?>
                    <?php foreach($notifs as $n): ?>
                        <?php // Logique améliorée : on se base sur le type de la notification ('Alerte') plutôt que sur le contenu. ?>
                        <?php if ($n['typen'] === 'Alerte'): ?>
                            <div class="notif-alert-maladie">
                                <svg class="alert-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                <div class="alert-content">
                                    <strong><?= htmlspecialchars($n['titren']) ?></strong>
                                    <span class="notif-text"><?= htmlspecialchars($n['contenun']) ?></span>
                                    <span class="notif-date"><?= date("d/m/Y", strtotime($n['date_envoi'])) ?></span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="notif-item">
                                <strong><?= htmlspecialchars($n['titren']) ?></strong>
                                <span class="notif-text"><?= htmlspecialchars($n['contenun']) ?></span>
                                <span class="notif-date"><?= date("d/m/Y", strtotime($n['date_envoi'])) ?></span>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="text-muted text-italic">Aucune nouvelle notification.</p>
                <?php endif; ?>
            </div>

        </div> <!-- Fin de la colonne laterale -->
    </div> <!-- Fin du dashboard-grid -->
</div>

<!-- Script pour gérer l'ajout de lignes dynamiques -->
<script>
function toggleApport() {
    var choix = document.getElementById('choix_apport').value;
    document.getElementById('bloc-graines').classList.toggle('hidden', !(choix === 'Graines' || choix === 'Mixte'));
    document.getElementById('bloc-outils').classList.toggle('hidden', !(choix === 'Materiel' || choix === 'Mixte'));
}

function ajouterLigne(containerId, nameType, nameQte, placeType, placeQte) {
    var container = document.getElementById(containerId);
    var div = document.createElement('div');
    div.className = 'form-inline form-inline-contribution mt-1';
    div.innerHTML = `
        <input type="text" name="${nameType}" placeholder="${placeType}" required>
        <input type="number" name="${nameQte}" placeholder="${placeQte}" min="1" required>
        <button type="button" class="btn-outline-danger btn-remove-item" onclick="this.parentElement.remove()" title="Retirer">X</button>
    `;
    container.appendChild(div);
}
</script>

<?php include 'inclusions/pied_de_page.php'; ?>
