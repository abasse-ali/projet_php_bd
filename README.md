# La Bòstia Verda — Plateforme de gestion d'un jardin partagé

Application web complète pour la gestion d'un jardin communautaire, développée pour répondre aux besoins de l'association **La Bòstia Verda** située à Naucelle (Aveyron).

---

## 1. Contexte du projet

La Bòstia Verda est une association citoyenne qui met à disposition des parcelles à cultiver pour des particuliers, dans une démarche d'agriculture urbaine et de lien social. L'application centralise :

- la gestion des **parcelles** et de leurs **attributions** aux adhérents,
- le suivi des **cultures** et **récoltes**,
- la **réservation d'outils** partagés,
- la diffusion de **conseils culturaux**,
- le déclenchement et la **propagation spatiale** d'alertes sanitaires (maladies, nuisibles),
- la gestion financière des **cotisations** et des **stocks** (semences + outils),
- un **journal météo** alimenté par une API externe (Open-Meteo).

---

## 2. Architecture & stack technique

### Architecture 3-tiers (modèle IBM)

| Niveau | Rôle | Implémentation |
|---|---|---|
| **Présentation** | Interface utilisateur | HTML5 + CSS3 natifs, balises sémantiques, responsive (menu burger CSS pur) |
| **Application** | Logique métier | PHP 8 (procédural, conforme cours André Aoun), routing via `index.php`, sessions, fonctions de sécurité par rôle |
| **Données** | Persistance | PostgreSQL via PDO, contraintes CHECK, clés étrangères, associations réflexives |

### Stack — 100% natif, zéro framework

- **Front** : HTML5, CSS3 (Flexbox + Grid + media queries), aucune dépendance JS
- **Back** : PHP 8 + PDO PostgreSQL
- **API externe** : Open-Meteo (météo, gratuite, sans clé)
- **Serveur local** : Laragon / Apache

### Arborescence

```
projet-jardin-php/
├── configuration/       # Connexion BDD + sessions
├── inclusions/          # en-tête, pied de page, fonctions globales
├── pages/               # Pages organisées par rôle
│   ├── public/          # accueil, connexion, inscription, plantes
│   ├── commun/          # déconnexion, profil
│   ├── adherent/        # cultures, outils, météo, signaler alerte
│   ├── tuteur/          # alertes sanitaires, conseils culturaux
│   ├── tresorier/       # trésorerie, stocks semences/outils
│   ├── responsable/     # parcelles, récoltes, tableau de bord, analyses
│   └── administrateur/  # supervision globale, gestion utilisateurs
├── ressources/
│   ├── bdd/             # schema.sql + donnees.sql
│   ├── styles/          # style.css
│   └── images/
├── index.php            # Front Controller (mapping URL → fichier)
└── reinitialiser_donnees.php
```

---

## 3. Modèle de données (extraits)

Les tables principales sont :

- `Utilisateur` (id, nom, prénom, rôle, mot de passe, email)
- `Parcelle` (id, surface, secteur, numéro)
- `Attribution` (parcelle ↔ utilisateur, dates)
- `Plante`, `Culture`, `Recolte`, `Semence`
- `Outil`, `Reservation`
- `Meteo`, `ConseilCultural`, `Contribution`
- `AlerteSanitaire` (déclenche la propagation spatiale)
- **Tables de liaison** : `est_voisine_de`, `saccorder`, `observer`, `s_inscrire`, `porter_sur`, `justifier`

L'association **réflexive `est_voisine_de`** permet le système de propagation d'alertes entre parcelles voisines.

---

## 4. Les 6 rôles utilisateurs

### Visiteur (compte en attente)
Personne qui s'est inscrite mais dont le dossier n'a pas encore été validé par l'administrateur.
- Consultation du **catalogue de plantes** (fiches publiques)
- Demande d'inscription en liste d'attente sur une parcelle

### Adhérent
Membre actif avec une parcelle attribuée.
- **Mon jardin** : voir ma parcelle, mes cultures en cours, plantation de nouvelles cultures
- **Réservation d'outils** : calendrier, demandes en attente / confirmées / annulables
- **Journal météo** : conditions du jour + prévisions 7 jours (API Open-Meteo)
- **Signaler une alerte sanitaire** sur ma propre culture (déclenche la propagation vers les voisins)
- Notifications personnelles (conseils, rappels, alertes voisinage)

### Tuteur (encadrant pédagogique)
- **Déclaration d'alertes sanitaires** sur toutes les cultures du jardin
- **Publication de conseils culturaux** (associations bénéfiques, traitements, saisonnalité)
- Liaison `justifier` : un conseil peut être justifié par un relevé météo

### Trésorier
- **Gestion financière** : contributions des adhérents (validées / en attente)
- **Stock de semences** : suivi des quantités, alertes stock bas
- **Stock d'outils** : inventaire, états physiques (Opérationnel / Abîmé / HS)

### Responsable du terrain
- **Plan des parcelles** (vue secteurs Nord / Sud / Est, occupation)
- **Attribution des parcelles** aux adhérents (gestion des dates de fin)
- **Historique des récoltes** (par culture, quantité, qualité)
- **Tableau de bord** : indicateurs globaux du terrain
- **Analyses décisionnelles** : statistiques de production, biodiversité

### Administrateur
- **Supervision globale** : tous les indicateurs en un coup d'œil
- **Gestion des utilisateurs** : validation des candidatures, modification de rôles
- **Gestion des ressources** : confirmation des réservations d'outils
- **Gestion du terrain** : vue d'ensemble parcelles + attributions
- Super-droits : accès à tous les écrans de tous les rôles

---

## 5. Fonctionnalités transversales remarquables

### Propagation spatiale des alertes
Quand une alerte sanitaire est déclarée, le système :
1. Identifie la parcelle d'origine via la culture concernée
2. Interroge `est_voisine_de` (association réflexive) pour trouver les parcelles limitrophes
3. Insère une notification ciblée pour chaque exploitant voisin **+** tous les tuteurs
4. L'auteur du signalement n'est pas re-notifié

→ Visible dans [inclusions/fonctions.php](inclusions/fonctions.php) : `propager_alerte_voisinage()`

### API météo Open-Meteo
- Récupération via `curl` natif PHP (pas de bibliothèque externe)
- 7 jours de prévisions en un appel
- Mapping des codes WMO vers libellés français + icônes SVG
- Anti-doublons via transaction PDO

### Responsive design — menu burger pur CSS
- Aucune ligne de JavaScript : *checkbox hack*
- 3 paliers : tablette (≤ 992px), mobile (≤ 768px), petit mobile (≤ 480px)
- Animation des 3 traits → croix au clic, en CSS uniquement

### Sécurité
- Mots de passe en **BCRYPT** (`password_hash` + `password_verify`)
- **PRG pattern** (Post / Redirect / Get) sur tous les formulaires
- Préparation systématique des requêtes (PDO + placeholders) → anti-injection SQL
- Vérification de rôle (`exiger_role()`) sur chaque page privée
- Variables d'environnement pour les identifiants BDD (`getenv()`)

---

## 6. Comptes de test

Tous les mots de passe : **`1234`**

| Rôle | Email |
|---|---|
| Administrateur | administrateur@mail.com |
| Responsable | jacques.leroy@mail.com |
| Trésorier | luc.moreau@mail.com |
| Tuteur | marie.roux@mail.com |
| Adhérent | alice.martin@mail.com |
| Visiteur | marc.durand@mail.com |

→ Pour réinitialiser le jeu de données : `http://localhost/projet-jardin-php/reinitialiser_donnees.php`

---

## 7. Comment lancer le site

Deux options : soit en **local** via Laragon + pgAdmin, soit en **ligne** sur le serveur déployé.

---

### Option A — Accès direct au site déployé (le plus simple)

Le site est accessible directement à cette URL :

> 🌐 **https://www.la-bostia-verda.fr**

Aucune installation, aucune configuration : il suffit d'ouvrir le lien dans n'importe quel navigateur (Chrome, Firefox, Edge, Safari). Le site est responsive, donc il fonctionne aussi sur smartphone et tablette.

Pour tester directement avec les comptes de la section 6, utilisez le mot de passe `1234` avec n'importe quel email du tableau ou vous pouvez créer votre propre compte.

---

### Option B — Installation locale avec Laragon + pgAdmin

#### Pré-requis

Installer ces 3 logiciels (tous gratuits, disponibles sur Windows / macOS / Linux) :

| Logiciel | Rôle | Téléchargement |
|---|---|---|
| **Laragon** | Serveur web local (Apache + PHP) | https://laragon.org/ |
| **PostgreSQL** | Système de gestion de base de données | https://www.postgresql.org/download/ |
| **pgAdmin** | Interface graphique pour PostgreSQL (généralement installée avec PostgreSQL) | https://www.pgadmin.org/ |

#### Étape 1 — Récupérer le code source

Placer le projet dans le dossier `www` de Laragon :

```bash
# Avec Git
cd c:/laragon/www
git clone <url-du-depot> projet-jardin-php

# OU manuellement : copier le dossier "projet-jardin-php" dans c:/laragon/www/
```

Le chemin final doit être : `c:/laragon/www/projet-jardin-php/`

#### Étape 2 — Créer la base de données dans pgAdmin

1. Lancer **pgAdmin** (icône dans le menu Démarrer)
2. Entrer le mot de passe maître de PostgreSQL (défini à l'installation)
3. Dans l'arborescence à gauche : **Servers → PostgreSQL 16 → Databases**
4. Clic droit sur **Databases → Create → Database…**
5. Dans la fenêtre qui s'ouvre :
   - **Database** : `jardin_db`
   - **Owner** : `postgres`
   - Cliquer sur **Save**

#### Étape 3 — Importer le schéma + les données

Toujours dans pgAdmin :

1. Clic droit sur la nouvelle base **`jardin_db` → Query Tool**
2. Cliquer sur l'icône **dossier** (Open File) en haut à gauche
3. Ouvrir `c:/laragon/www/projet-jardin-php/ressources/bdd/schema.sql`
4. Cliquer sur l'icône **▶ Execute** (ou touche **F5**) → toutes les tables sont créées
5. Répéter l'opération avec `ressources/bdd/donnees.sql` → le jeu de données de test est inséré

Alternative en ligne de commande (depuis le dossier racine du projet) :

```bash
psql -U postgres -d jardin_db -f ressources/bdd/schema.sql
psql -U postgres -d jardin_db -f ressources/bdd/donnees.sql
```

#### Étape 4 — Vérifier les identifiants de connexion BDD

Ouvrir [configuration/bdd.php](configuration/bdd.php) et vérifier que les valeurs correspondent à votre installation PostgreSQL :

```php
$hote             = 'localhost';
$base             = 'jardin_db';
$utilisateur_bdd  = 'postgres';
$mot_de_passe_bdd = 'postgres';  // Mettre votre mot de passe PostgreSQL si différent
$port             = '5432';
```

> Si vous avez défini un autre mot de passe à l'installation de PostgreSQL, modifiez la ligne `$mot_de_passe_bdd`.

#### Étape 5 — Démarrer Laragon et lancer le site

1. Ouvrir **Laragon**
2. Cliquer sur **Démarrer tout** (ou **Start All**) → Apache passe au vert
3. Ouvrir un navigateur et taper :

> **http://localhost/projet-jardin-php/**

Le site doit s'afficher avec la page d'accueil. Vous pouvez maintenant vous connecter avec n'importe quel compte de test de la section 6.

#### Étape 6 (optionnelle) — Réinitialiser les données

Si à un moment vous voulez remettre la base à zéro avec le jeu de test d'origine, il suffit de visiter :

> **http://localhost/projet-jardin-php/reinitialiser_donnees.php**

Un récapitulatif s'affiche : nombre d'utilisateurs, parcelles, cultures rechargées…

---

### Résolution des problèmes courants

| Problème | Cause probable | Solution |
|---|---|---|
| « Erreur de connexion : … » | Mot de passe BDD incorrect | Modifier `$mot_de_passe_bdd` dans `configuration/bdd.php` |
| Page blanche | PHP n'est pas démarré | Vérifier que Laragon est lancé (icône verte) |
| « 404 Not Found » | Mauvaise URL | Doit être `http://localhost/projet-jardin-php/` (pas `/index.php`) |
| Accents qui s'affichent mal | Mauvais encodage | Vérifier que les fichiers SQL sont bien en UTF-8 |
| Les images ne s'affichent pas | Mauvais chemin | Vérifier que `ressources/images/` contient bien les PNG |

---

## 9. Équipe

- **Abasse Saadanne Ali**
- **Ayyub**

Projet réalisé dans le cadre du cours de PHP / PostgreSQL.
