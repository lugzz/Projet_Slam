<?php
// Chemin du fichier de log
$fichierLog = 'logs.txt';

// Vérifie si le fichier existe
if (file_exists($fichierLog)) {
    echo "<h1>Logs du site</h1>";
    echo "<pre style='background-color: #f4f4f4; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars(file_get_contents($fichierLog));
    echo "</pre>";
} else {
    echo "<p style='color:red;'>Le fichier de log n'existe pas.</p>";
}
?>
