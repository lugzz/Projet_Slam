<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 2) {
    header("Location: index.php");
    exit();
}
?>
<h1>Bienvenue Admin</h1>
<a href="logout.php">Déconnexion</a>
