<?php
/**
 * configuration/bdd.php
 *
 * Connexion sécurisée à la base de données PostgreSQL via l'extension PDO.
 * Extrait les identifiants d'accès depuis les variables d'environnement système
 * afin de protéger les secrets de production, tout en injectant des valeurs de repli
 * pour les environnements de développement locaux.
 *
 * Variables exposées :
 * @var PDO $bdd Instance de connexion active prête pour l'exécution des requêtes.
 */

// Récupération des paramètres de connexion (Pattern de repli via opérateur Elvis)
$hote = getenv('DB_HOST') ?: 'localhost';
$base = getenv('DB_NAME') ?: 'jardin_db';
$utilisateur_bdd = getenv('DB_USER') ?: 'postgres';
$mot_de_passe_bdd = getenv('DB_PASS') ?: 'postgres';
$port = getenv('DB_PORT') ?: '5432';

// Construction du Data Source Name (DSN) avec forçage de l'encodage client en UTF-8
$dsn = "pgsql:host=$hote;port=$port;dbname=$base;options='--client_encoding=UTF8'";

try {
    // Initialisation de l'instance PDO avec configuration stricte des attributs de sécurité
    $bdd = new PDO($dsn, $utilisateur_bdd, $mot_de_passe_bdd, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Lève des exceptions PDOException en cas d'erreur
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Force le mode d'extraction en tableau associatif
    ]);
} catch (\PDOException $e) {
    // Interception des anomalies d'infrastructure de connexion et arrêt de sécurité du script
    die("Erreur de connexion : " . $e->getMessage());
}