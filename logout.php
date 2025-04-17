<?php
function ajouterLog($message) {
    $fichier = 'logs.txt';
    $date = date('Y-m-d H:i:s');
    $ligne = "[$date] $message" . PHP_EOL;
    file_put_contents($fichier, $ligne, FILE_APPEND);
}

session_start();

if (isset($_SESSION['login'])) {
    ajouterLog("Déconnexion de l'utilisateur " . $_SESSION['login']);
} else {
    ajouterLog("Déconnexion d'un utilisateur non identifié");
}

session_unset();
session_destroy();
header("Location: index.php");
exit();
?>
