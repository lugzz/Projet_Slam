<?php
$host     = 'sql109.infinityfree.com';
$dbname   = 'if0_38342359_brasserie';
$username = 'if0_38342359';
$password = 'gE0DeROeqK';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
