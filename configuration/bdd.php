<?php
// configuration/bdd.php
// Connexion à la base de données PostgreSQL via PDO.

// getenv() va chercher les variables secrètes sur le serveur en production.
// Le "?:" (Elvis operator) signifie : "Si la variable n'existe pas, utilise la valeur locale par défaut".
$hote             = getenv('DB_HOST') ?: 'localhost';
$base             = getenv('DB_NAME') ?: 'jardin_db';
$utilisateur_bdd  = getenv('DB_USER') ?: 'postgres';
$mot_de_passe_bdd = getenv('DB_PASS') ?: 'postgres';
$port             = getenv('DB_PORT') ?: '5432';

$dsn = "pgsql:host=$hote;port=$port;dbname=$base;options='--client_encoding=UTF8'";

try {
    $bdd = new PDO($dsn, $utilisateur_bdd, $mot_de_passe_bdd, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (\PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>