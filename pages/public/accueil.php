<?php
// Point d'entrée de l'accueil : Inclusion de l'en-tête (Architecture 3 Tiers - Vue)
require_once 'inclusions/entete.php';

// --- Récupération des statistiques dynamiques ---
$requete_parcelles = $bdd->query("SELECT COUNT(*) FROM Attribution WHERE date_fin IS NULL OR date_fin > CURRENT_DATE");
$nb_parcelles = $requete_parcelles->fetchColumn();

$requete_adherents = $bdd->query("SELECT COUNT(*) FROM Utilisateur WHERE roleU = 'Adhérent'");
$nb_adherents = $requete_adherents->fetchColumn();

// Pour les récoltes, on somme la quantité pour l'année en cours. COALESCE pour afficher 0 si la table est vide.
$annee_en_cours = date('Y');
try {
    // Correction du nom de la colonne 'quantiter' en 'quantiteR' conformément au schéma SQL.
    $requete_recoltes = $bdd->query("SELECT COALESCE(SUM(quantiteR), 0) FROM Recolte WHERE EXTRACT(YEAR FROM date_recolte) = $annee_en_cours");
    $kg_recoltes = $requete_recoltes->fetchColumn();
} catch (PDOException $e) {
    // Anti-crash : Si la colonne n'existe pas, on affiche 0 par défaut sur l'accueil
    $kg_recoltes = 0; 
}

$requete_outils = $bdd->query("SELECT COUNT(*) FROM Outil WHERE disponibiliteO = TRUE AND etat_physique = 'Opérationnel'");
$nb_outils = $requete_outils->fetchColumn();
?>

<main>
    <!-- 1. SECTION HÉROÏQUE -->
    <section class="hero-section" id="le-jardin">
        <h1>Cultivez l'autosuffisance au cœur de la ville</h1>
        <p>Rejoignez notre communauté urbaine. Gérez votre parcelle, réservez des outils partagés et bénéficiez de conseils d'arrosage personnalisés basés sur la météo localisée.</p>
        <div class="hero-actions">
            <?php if (!isset($_SESSION['id_utilisateur'])): ?>
                <!-- Boutons pour les visiteurs non connectés -->
                <a href="index.php?page=inscription" class="btn btn-primary">Demander une parcelle &rarr;</a>
                <a href="index.php?page=connexion" class="btn btn-secondary">Espace Adhérent</a>
            <?php else: ?>
                <!-- Bouton pour les utilisateurs connectés -->
                <a href="#parcelles" class="btn btn-primary">Explorer les statistiques &rarr;</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- 2. SECTION STATISTIQUES (Entités agrégées du MCD) -->
    <section class="stats-section" id="parcelles">
        <div class="stats-grid">
            <div class="stat-card">
                <!-- Icône d'épingle -->
                <svg viewBox="0 0 24 24"><path d="M12,2C8.13,2 5,5.13 5,9C5,14.25 12,22 12,22C12,22 19,14.25 19,9C19,5.13 15.87,2 12,2M12,11.5A2.5,2.5 0 0,1 9.5,9A2.5,2.5 0 0,1 12,6.5A2.5,2.5 0 0,1 14.5,9A2.5,2.5 0 0,1 12,11.5Z" /></svg>
                <div class="stat-number"><?= $nb_parcelles ?></div>
                <div class="stat-label">Parcelles cultivées</div>
            </div>
            <div class="stat-card">
                <!-- Icône groupe de personnes -->
                <svg viewBox="0 0 24 24"><path d="M16,11C17.66,11 18.99,9.66 18.99,8C18.99,6.34 17.66,5 16,5C14.34,5 13,6.34 13,8C13,9.66 14.34,11 16,11M8,11C9.66,11 10.99,9.66 10.99,8C10.99,6.34 9.66,5 8,5C6.34,5 5,6.34 5,8C5,9.66 6.34,11 8,11M8,13C5.67,13 1,14.17 1,16.5V19H15V16.5C15,14.17 10.33,13 8,13M16,13C15.71,13 15.38,13.04 15.03,13.1C16.22,13.88 17,14.93 17,16.5V19H23V16.5C23,14.17 18.33,13 16,13Z" /></svg>
                <div class="stat-number"><?= $nb_adherents ?></div>
                <div class="stat-label">Adhérents actifs</div>
            </div>
            <div class="stat-card">
                <!-- Icône récolte -->
                <svg viewBox="0 0 24 24"><path d="M12,22A2,2 0 0,0 14,20H10A2,2 0 0,0 12,22M18,16V11C18,7.9 16.03,5.36 13.18,4.71V4A1.18,1.18 0 0,0 12,2.82A1.18,1.18 0 0,0 10.82,4V4.71C7.97,5.36 6,7.9 6,11V16L4,18V19H20V18L18,16Z" /></svg>
                <div class="stat-number"><?= round($kg_recoltes, 1) ?> Kg</div>
                <div class="stat-label">Kg récoltés (<?= $annee_en_cours ?>)</div>
            </div>
            <div class="stat-card">
                <!-- Icône outil -->
                <svg viewBox="0 0 24 24"><path d="M13.78,15.3L19.78,21.3L21.89,19.14L15.89,13.14L13.78,15.3M17.5,10.1C17.11,10.1 16.69,10.05 16.36,9.91L4.97,21.25L2.86,19.14L11.4,10.6L9.27,8.47L10.69,7.05L12.82,9.18L14.24,7.76L12.11,5.64L13.5,4.22L15.65,6.34C17.13,4.12 20.08,3.58 22.25,5.18C22.25,5.18 20.07,7.34 20.07,7.34C20.07,7.34 22.18,9.45 22.18,9.45C22.18,9.45 20.07,11.56 20.07,11.56C19.26,10.6 18.4,10.1 17.5,10.1Z" /></svg>
                <div class="stat-number"><?= $nb_outils ?></div>
                <div class="stat-label">Outils partagés</div>
            </div>
        </div>
    </section>

    <!-- 3. SECTION FONCTIONNALITÉS (Lien avec le MCD) -->
    <section class="features-section" id="conseils">
        <div class="features-grid">
            <article class="feature-card">
                <svg viewBox="0 0 24 24"><path d="M12,3L2,12H5V20H19V12H22L12,3M12,7.7C14.1,7.7 15.8,9.4 15.8,11.5C15.8,14.5 12,18 12,18C12,18 8.2,14.5 8.2,11.5C8.2,9.4 9.9,7.7 12,7.7Z" /></svg>
                <h3>Gestion de parcelles</h3>
                <p>Suivez l'état de votre lopin en temps réel et planifiez vos rotations de cultures.</p>
            </article>
            <article class="feature-card">
                <svg viewBox="0 0 24 24"><path d="M19,4H18V2H16V4H8V2H6V4H5C3.89,4 3,4.9 3,6V20A2,2 0 0,0 5,22H19A2,2 0 0,0 21,20V6A2,2 0 0,0 19,4M19,20H5V10H19V20M19,8H5V6H19V8Z" /></svg>
                <h3>Réservation d'outils</h3>
                <p>Empruntez le matériel partagé de l'association via notre calendrier interactif.</p>
            </article>
            <article class="feature-card">
                <svg viewBox="0 0 24 24"><path d="M11,20H13V15.5C13,15.5 15,15.5 15.5,13.5C16.27,10.42 13.91,8.39 12.5,7.5V4H11.5V7.5C10.09,8.39 7.73,10.42 8.5,13.5C9,15.5 11,15.5 11,15.5V20M12,2L12,2C12,2 12,2 12,2C12,2 12,2 12,2" /></svg>
                <h3>Suivi des cultures</h3>
                <p>De la graine à la récolte, documentez la croissance de vos cultures sur votre parcelle attribuée.</p>
            </article>
            <article class="feature-card">
                <svg viewBox="0 0 24 24"><path d="M12,2A5,5 0 0,1 17,7C17,9.36 15.35,11.33 13.14,11.85C14.73,12.37 16,13.56 16.64,15.2C18.66,14.6 20,12.72 20,10.5A5.5,5.5 0 0,0 14.5,5H14.15C13.88,3.26 13.06,2 12,2M12,4C12,4 12,4 12,4C12,4 12,4 12,4M12,12C9.24,12 7,14.24 7,17V22H17V17C17,14.24 14.76,12 12,12M12,13.5A3.5,3.5 0 0,1 15.5,17V20H8.5V17A3.5,3.5 0 0,1 12,13.5Z" /></svg>
                <h3>Communauté & Conseils</h3>
                <p>Échangez avec les tuteurs pédagogiques et bénéficiez de conseils météo personnalisés pour optimiser votre arrosage.</p>
            </article>
        </div>
    </section>

    <!-- 4. SECTION PARCOURS UTILISATEUR -->
    <section class="journey-section" id="fonctionnement">
        <div class="journey-container">
            <div class="journey-content">
                <h2 class="journey-title">Comment rejoindre l'aventure urbaine ?</h2>
                <div class="journey-steps">
                    <div class="step-item">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Inscription</h3>
                            <p>Créez votre compte visiteur et validez votre cotisation auprès du trésorier.</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Liste d'attente</h3>
                            <p>L'administrateur valide votre dossier et vous place sur la liste pour une parcelle disponible.</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Attribution</h3>
                            <p>Le responsable de parcelle vous attribue un ou plusieurs lopins dédiés pour cultiver.</p>
                        </div>
                    </div>
                    <div class="step-item">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3>Cultivez !</h3>
                            <p>Plantez vos semences, entretenez vos cultures, partagez vos récoltes et contribuez au journal de bord.</p>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Bloc Visuel de droite simulé avec CSS -->
            <div class="journey-visual" style="position: relative; background: linear-gradient(to top right, #0a2119, #134233); border-radius: 12px; height: 100%; min-height: 400px; display: flex; align-items: center; justify-content: center;">
                <svg viewBox="0 0 24 24" width="120" height="120" fill="#205a46"><path d="M17,8C8,10 5.9,16.17 3.82,21.34L5.71,22L6.66,19.7C7.14,19.87 7.64,20 8,20C19,20 22,3 22,3C21,5 14,5.25 9,6.25C4,7.25 2,11.5 2,13.5C2,15.5 3.75,17.25 3.75,17.25C7,8 17,8 17,8Z" /></svg>
                <!-- Toast Notification superposée -->
                <div style="position: absolute; bottom: -20px; right: -20px; background: var(--color-text-light); color: var(--color-primary-dark); padding: 1.5rem; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.3); width: 250px;">
                    <strong style="display: block; font-size: 1.1rem; margin-bottom: 0.2rem;">Culture plantée !</strong>
                    <p style="font-size: 0.9rem; margin-bottom: 1rem; color: #555;">Tomates 'Marmande' plantées il y a 2 jours.</p>
                    <div style="width: 100%; background: #ddd; height: 6px; border-radius: 3px; overflow: hidden;">
                        <div style="width: 15%; background: var(--color-accent); height: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<?php
// Inclusion du pied de page
require_once 'inclusions/pied_de_page.php';
?>