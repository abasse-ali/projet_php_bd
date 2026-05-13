<?php
// pages/commun/logout.php

// Destruction de la session utilisateur
session_unset();
session_destroy();

header("Location: index.php?page=connexion");
exit;
?>