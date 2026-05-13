# 🌱 La Bòstia Verda — Plateforme de gestion d'un jardin partagé

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

### 👤 Visiteur (compte en attente)
Personne qui s'est inscrite mais dont le dossier n'a pas encore été validé par l'administrateur.
- Consultation du **catalogue de plantes** (fiches publiques)
- Demande d'inscription en liste d'attente sur une parcelle

### 🌿 Adhérent
Membre actif avec une parcelle attribuée.
- **Mon jardin** : voir ma parcelle, mes cultures en cours, plantation de nouvelles cultures
- **Réservation d'outils** : calendrier, demandes en attente / confirmées / annulables
- **Journal météo** : conditions du jour + prévisions 7 jours (API Open-Meteo)
- **Signaler une alerte sanitaire** sur ma propre culture (déclenche la propagation vers les voisins)
- Notifications personnelles (conseils, rappels, alertes voisinage)

### 🎓 Tuteur (encadrant pédagogique)
- **Déclaration d'alertes sanitaires** sur toutes les cultures du jardin
- **Publication de conseils culturaux** (associations bénéfiques, traitements, saisonnalité)
- Liaison `justifier` : un conseil peut être justifié par un relevé météo

### 💰 Trésorier
- **Gestion financière** : contributions des adhérents (validées / en attente)
- **Stock de semences** : suivi des quantités, alertes stock bas
- **Stock d'outils** : inventaire, états physiques (Opérationnel / Abîmé / HS / En réparation)

### 🌾 Responsable du terrain
- **Plan des parcelles** (vue secteurs Nord / Sud / Est, occupation)
- **Attribution des parcelles** aux adhérents (gestion des dates de fin)
- **Historique des récoltes** (par culture, quantité, qualité)
- **Tableau de bord** : indicateurs globaux du terrain
- **Analyses décisionnelles** : statistiques de production, biodiversité

### 🛡️ Administrateur
- **Supervision globale** : tous les indicateurs en un coup d'œil
- **Gestion des utilisateurs** : validation des candidatures, modification de rôles
- **Gestion des ressources** : confirmation des réservations d'outils
- **Gestion du terrain** : vue d'ensemble parcelles + attributions
- Super-droits : accès à tous les écrans de tous les rôles

---

## 5. Fonctionnalités transversales remarquables

### 🚨 Propagation spatiale des alertes
Quand une alerte sanitaire est déclarée, le système :
1. Identifie la parcelle d'origine via la culture concernée
2. Interroge `est_voisine_de` (association réflexive) pour trouver les parcelles limitrophes
3. Insère une notification ciblée pour chaque exploitant voisin **+** tous les tuteurs
4. L'auteur du signalement n'est pas re-notifié

→ Visible dans [inclusions/fonctions.php](inclusions/fonctions.php) : `propager_alerte_voisinage()`

### 🌤️ API météo Open-Meteo
- Récupération via `curl` natif PHP (pas de bibliothèque externe)
- 7 jours de prévisions en un appel
- Mapping des codes WMO vers libellés français + icônes SVG
- Anti-doublons via transaction PDO

### 📱 Responsive design — menu burger pur CSS
- Aucune ligne de JavaScript : *checkbox hack*
- 3 paliers : tablette (≤ 992px), mobile (≤ 768px), petit mobile (≤ 480px)
- Animation des 3 traits → croix au clic, en CSS uniquement

### 🔐 Sécurité
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
| Administrateur | admin@mail.com |
| Responsable | jacques.leroy@mail.com |
| Trésorier | luc.moreau@mail.com |
| Tuteur | marie.roux@mail.com |
| Adhérent | alice.martin@mail.com |
| Visiteur | marc.durand@mail.com |

→ Pour réinitialiser le jeu de données : `http://localhost/projet-jardin-php/reinitialiser_donnees.php`

---

## 7. Plan de présentation orale — 10 minutes

### 🎤 Partie 1 — Abasse (5 minutes)

| Durée | Section | Contenu à présenter |
|---|---|---|
| **1 min** | **Contexte & objectifs** | Présenter l'association La Bòstia Verda, le besoin métier (centraliser la gestion d'un jardin partagé), et les 6 rôles utilisateurs |
| **1 min** | **Architecture 3-tiers** | Schéma : Présentation (HTML/CSS) → Application (PHP+PDO) → Données (PostgreSQL). Insister sur l'absence de framework |
| **1 min** | **Modèle de données** | Montrer le MCD : tables principales + tables de liaison (`est_voisine_de`, `saccorder`). Souligner l'association réflexive |
| **2 min** | **Démo côté membre** | Connexion en **Adhérent (Alice)** → tour du jardin, cultures en cours, météo (synchronisation API live), signaler une alerte sanitaire. Puis basculer en **Tuteur (Marie)** → publication d'un conseil cultural |

### 🎤 Partie 2 — Ayyub (5 minutes)

| Durée | Section | Contenu à présenter |
|---|---|---|
| **1 min 30** | **Démo côté gestion** | Connexion en **Trésorier (Luc)** → stocks de semences (alerte stock critique), validation contributions. Puis **Responsable (Jacques)** → plan des parcelles par secteur, tableau de bord |
| **1 min 30** | **Démo administrateur** | Connexion en **Administrateur (Anne)** → supervision globale, validation d'un candidat en liste d'attente (Visiteur → Adhérent), confirmation d'une réservation d'outil |
| **1 min** | **Fonctionnalité spatiale** | Démontrer la propagation d'alerte : depuis Alice (parcelle Nord 101), déclarer une alerte mildiou → montrer les notifications reçues automatiquement par Sophie (Nord 102, voisine) et par les tuteurs |
| **1 min** | **Responsive + conclusion** | Ouvrir l'inspecteur du navigateur, passer en mode mobile → montrer le menu burger CSS pur. Conclure sur la conformité 100% au cours (zéro framework, PHP+PDO+HTML/CSS uniquement) |

---

## 8. Installation locale

```bash
# 1. Cloner le projet dans le dossier www de Laragon
git clone <repo> c:/laragon/www/projet-jardin-php

# 2. Créer la base PostgreSQL "jardin_db"
# (via pgAdmin ou ligne de commande psql)

# 3. Importer le schéma puis les données
psql -U postgres -d jardin_db -f ressources/bdd/schema.sql
psql -U postgres -d jardin_db -f ressources/bdd/donnees.sql

# 4. Ouvrir dans le navigateur
http://localhost/projet-jardin-php/
```

Identifiants BDD par défaut dans [configuration/bdd.php](configuration/bdd.php) :
- hôte : `localhost`
- base : `jardin_db`
- utilisateur : `postgres`
- mot de passe : `postgres`

---

## 9. Équipe

- **Abasse Saadanne Ali**
- **Ayyub**

Projet réalisé dans le cadre du cours de PHP / PostgreSQL.
