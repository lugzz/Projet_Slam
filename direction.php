<?php

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] != 3) {
    header('Location: index.php');
    exit();
}

$host = 'sql109.infinityfree.com';
$dbname = 'if0_38342359_brasserie';
$username = 'if0_38342359';
$password = 'gE0DeROeqK';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Ajout de revenus
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_revenue'])) {
    try {
        $sql = "INSERT INTO ventes (total, date_vente) VALUES (:total, :date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':total' => $_POST['montant_recette'],
            ':date' => $_POST['date_recette'],
        ]);
        $message_recette = "Recette ajoutée avec succès !";
    } catch (PDOException $e) {
        $error_recette = "Erreur lors de l'ajout de la recette : " . $e->getMessage();
    }
}

// Ajout des dépenses
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_depense'])) {
    try {
        $sql = "INSERT INTO stock (quantite, prix_achat_ht, date_entree_stock) VALUES (:quantite, :prix, :date)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':quantite' => $_POST['quantite_depense'],
            ':prix' => $_POST['prix_depense'],
            ':date' => $_POST['date_depense'],
        ]);
        $message_depense = "Dépense ajoutée avec succès !";
    } catch (PDOException $e) {
        $error_depense = "Erreur lors de l'ajout de la dépense : " . $e->getMessage();
    }
}

$mois = isset($_GET['mois']) ? $_GET['mois'] : date('m');
$annee = isset($_GET['annee']) ? $_GET['annee'] : date('Y');

try {
    // Calcul des recettes (total des ventes)
    $sql_recettes = "SELECT SUM(total) as total_recettes 
                     FROM ventes 
                     WHERE MONTH(date_vente) = :mois AND YEAR(date_vente) = :annee";
    $stmt = $pdo->prepare($sql_recettes);
    $stmt->execute(['mois' => $mois, 'annee' => $annee]);
    $recettes = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_recettes = $recettes['total_recettes'] ?? 0;

    // Calcul des dépenses (somme des prix d'achat des stocks)
    $sql_depenses = "SELECT SUM(quantite * prix_achat_ht) as total_depenses 
                     FROM stock 
                     WHERE MONTH(date_entree_stock) = :mois AND YEAR(date_entree_stock) = :annee";
    $stmt = $pdo->prepare($sql_depenses);
    $stmt->execute(['mois' => $mois, 'annee' => $annee]);
    $depenses = $stmt->fetch(PDO::FETCH_ASSOC);
    $total_depenses = $depenses['total_depenses'] ?? 0;

    $bilan = $total_recettes - $total_depenses;

    // Requête des ventes par produit
    $sql_ventes_produits = "SELECT p.nom, SUM(vd.quantite) as total_vendu 
                            FROM ventes_details vd
                            JOIN produits p ON vd.id_produit = p.id
                            JOIN ventes v ON vd.id_vente = v.id_vente
                            WHERE MONTH(v.date_vente) = :mois AND YEAR(v.date_vente) = :annee
                            GROUP BY p.nom";
    $stmt = $pdo->prepare($sql_ventes_produits);
    $stmt->execute(['mois' => $mois, 'annee' => $annee]);
    $ventes_produits = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Erreur de requête : " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Terroirs et Saveurs - Bilan Financier</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Poppins">
    <style>
        body, h1, h2, h3, h4, h5 {
            font-family: "Poppins", sans-serif;
        }
        body {
            font-size: 16px;
            margin: 0;
            padding: 0;
            background-image: url('bar.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            color: white;
        }

        .w3-xxxlarge {
            color: #dfaf2c;
        }
        .w3-sidebar {
            background-color: rgb(93, 89, 89);
        }
        .w3-sidebar a {
            color: white;
        }
        .w3-sidebar a:hover {
            background-color: rgb(208, 196, 192);
        }

        .logo {
            width: 100px; 
            margin-bottom: 5px;
        }

        .financial-section {
            background-color: rgba(255,255,255,0.8);
            color: black;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .w3-table-all td, .w3-table-all th {
            color: black !important;
        }

        .form-container {
            background-color: rgba(255,255,255,0.9);
            color: black;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }

        .success-message {
            color: green;
            font-weight: bold;
        }

        .error-message {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <nav class="w3-sidebar w3-collapse w3-top w3-large w3-padding" style="z-index:3;width:300px;font-weight:bold;" id="mySidebar">
        <div class="w3-container">
            <img src="brasserie_logo.png" alt="Logo Brasserie" class="logo">
            <?php if (isset($_SESSION['login'])): ?>
                <a href="#" class="w3-bar-item w3-button">Bonjour, <?php echo htmlspecialchars($_SESSION['login']); ?></a>
            <?php endif; ?>
            <h3 class="w3-padding-64"><b>Terroirs<br>et Saveurs</b></h3>
        </div>
        <div class="w3-bar-block">
            <a href="index.php" class="w3-bar-item w3-button">Accueil</a>
            <?php if (isset($_SESSION['role'])): ?>
                <?php if ($_SESSION['role'] == '1'): ?>
                    <a href="admin.php" class="w3-bar-item w3-button">Administration</a>
                <?php elseif ($_SESSION['role'] == '2'): ?>
                    <a href="brasseur.php" class="w3-bar-item w3-button">Brasseur</a>
                <?php elseif ($_SESSION['role'] == '3'): ?>
                    <a href="direction.php" class="w3-bar-item w3-button">Direction</a>
                <?php elseif ($_SESSION['role'] == '4'): ?>
                    <a href="client.php" class="w3-bar-item w3-button">Client</a>
                <?php elseif ($_SESSION['role'] == '5'): ?>
                    <a href="caissier.php" class="w3-bar-item w3-button">Caissier</a>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="connexion.php" class="w3-bar-item w3-button">Connexion</a>
            <?php else: ?>
                <a href="logout.php" class="w3-bar-item w3-button">Déconnexion</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" id="myOverlay"></div>

    <div class="w3-main" style="margin-left:340px;margin-right:40px">
        <div class="w3-container" style="margin-top:80px" id="bilan">
            <h1 class="w3-xxxlarge"><b>Bilan Financier</b></h1>
            
            <form method="GET" class="w3-container w3-card-4 w3-light-grey w3-padding">
                <div class="w3-row-padding">
                    <div class="w3-col m6 l6">
                        <label for="mois">Mois :</label>
                        <input type="number" id="mois" name="mois" value="<?php echo $mois; ?>" min="1" max="12" class="w3-input">
                    </div>
                    <div class="w3-col m6 l6">
                        <label for="annee">Année :</label>
                        <input type="number" id="annee" name="annee" value="<?php echo $annee; ?>" class="w3-input">
                    </div>
                </div>
                <button type="submit" class="w3-button w3-amber w3-margin-top">Afficher</button>
            </form>

            <?php if (isset($message_recette)): ?>
                <p class="success-message"><?php echo $message_recette; ?></p>
            <?php endif; ?>
            <?php if (isset($error_recette)): ?>
                <p class="error-message"><?php echo $error_recette; ?></p>
            <?php endif; ?>

            <?php if (isset($message_depense)): ?>
                <p class="success-message"><?php echo $message_depense; ?></p>
            <?php endif; ?>
            <?php if (isset($error_depense)): ?>
                <p class="error-message"><?php echo $error_depense; ?></p>
            <?php endif; ?>

            <div class="financial-section">
                <h2>Résumé Financier</h2>
                <div class="w3-row">
                    <div class="w3-col m4">
                        <p><strong>Total Recettes :</strong> <?php echo number_format($total_recettes, 2); ?> €</p>
                    </div>
                    <div class="w3-col m4">
                        <p><strong>Total Dépenses :</strong> <?php echo number_format($total_depenses, 2); ?> €</p>
                    </div>
                    <div class="w3-col m4">
                        <p><strong>Bilan :</strong> <?php echo number_format($bilan, 2); ?> €</p>
                    </div>
                </div>
            </div>
            
            <div class="w3-row">
                <div class="w3-col m6">
                    <div class="form-container">
                        <h2>Ajouter une Recette</h2>
                        <form method="POST" class="w3-container">
                            <input type="hidden" name="add_revenue" value="1">
                            <div class="w3-margin-bottom">
                                <label>Montant de la Recette (€)</label>
                                <input type="number" step="0.01" name="montant_recette" class="w3-input" required>
                            </div>
                            <div class="w3-margin-bottom">
                                <label>Date de la Recette</label>
                                <input type="date" name="date_recette" class="w3-input" required>
                            </div>
                            <button type="submit" class="w3-button w3-green">Ajouter Recette</button>
                        </form>
                    </div>
                </div>
                <div class="w3-col m6">
                    <div class="form-container">
                        <h2>Ajouter une Dépense</h2>
                        <form method="POST" class="w3-container">
                            <input type="hidden" name="add_depense" value="1">
                            <div class="w3-margin-bottom">
                                <label>Quantité</label>
                                <input type="number" name="quantite_depense" class="w3-input" required>
                            </div>
                            <div class="w3-margin-bottom">
                                <label>Prix Unitaire HT (€)</label>
                                <input type="number" step="0.01" name="prix_depense" class="w3-input" required>
                            </div>
                            <div class="w3-margin-bottom">
                                <label>Date de la Dépense</label>
                                <input type="date" name="date_depense" class="w3-input" required>
                            </div>
                            <button type="submit" class="w3-button w3-red">Ajouter Dépense</button>
                        </form>
                    </div>
                </div>
            </div>
            
            <h1 class="w3-xxxlarge"><b>Bilan Commercial</b></h1>
            <div class="w3-responsive">
                <table class="w3-table-all w3-hoverable w3-card-4">
                    <thead>
                        <tr class="w3-amber">
                            <th>Produit</th>
                            <th>Quantité Vendue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventes_produits as $vente): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($vente['nom']); ?></td>
                                <td><?php echo htmlspecialchars($vente['total_vendu']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>