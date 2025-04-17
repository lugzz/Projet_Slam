<?php
session_start();

$host = 'sql109.infinityfree.com';
$dbname = 'if0_38342359_brasserie';
$username = 'if0_38342359';  
$password = 'gE0DeROeqK';

function ajouterLog($message) {
    $fichier = 'logs.txt';
    $date = date('Y-m-d H:i:s');
    $ligne = "[$date] $message" . PHP_EOL;
    file_put_contents($fichier, $ligne, FILE_APPEND);
}
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}

$id_utilisateur = $_SESSION['user_id'];

// Supprimer un produit du panier
if (isset($_GET['supprimer'])) {
    $id_supprimer = $_GET['supprimer'];
    if (isset($_SESSION['panier'])) {
        foreach ($_SESSION['panier'] as $key => $item) {
            if ($item['id'] == $id_supprimer) {
                unset($_SESSION['panier'][$key]);
                $_SESSION['panier'] = array_values($_SESSION['panier']); // Réindexer le tableau
                break;
            }
        }
    }
    header('Location: panier.php');
    exit;
}

// Vider le panier
if (isset($_GET['vider'])) {
    $_SESSION['panier'] = [];
    header('Location: panier.php');
    exit;
}

// Valider la commande
if (isset($_POST['valider_commande']) && !empty($_SESSION['panier'])) {
    try {
        // Calculer le total
        $total = 0;
        foreach ($_SESSION['panier'] as $item) {
            $total += $item['prix'] * $item['quantite'];
        }
        
        // Commencer une transaction
        $pdo->beginTransaction();
        
        // Insérer dans la table ventes
        $date_actuelle = date('Y-m-d');
        $stmt = $pdo->prepare("INSERT INTO ventes (reductions, date_vente, total, type_vente, id_utilisateur) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([0, $date_actuelle, $total, 'réservation', $id_utilisateur]);

        
        // Récupérer l'ID de la vente
        $id_vente = $pdo->lastInsertId();
        
        // Insérer chaque produit dans ventes_details
        foreach ($_SESSION['panier'] as $item) {
            $stmt = $pdo->prepare("INSERT INTO ventes_details (id_produit, id_vente, quantite, prix_unitaire) VALUES (?, ?, ?, ?)");
            $stmt->execute([$item['id'], $id_vente, $item['quantite'], $item['prix']]);
        }
        
        // Valider la transaction
        $pdo->commit();
        ajouterLog("Panier validé par :  " . $_SESSION['login']);
        
        // Vider le panier après la commande
        $_SESSION['panier'] = [];
        
        // Rediriger avec un message de succès
        header('Location: client.php?commande_success=1');
        exit;
    } catch (PDOException $e) {
        // En cas d'erreur, annuler la transaction
        $pdo->rollBack();
        $error_message = "Erreur lors de la commande: " . $e->getMessage();
    }
}

// Calculer le total du panier
$total = 0;
if (isset($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        $total += $item['prix'] * $item['quantite'];
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Panier - Terroirs et Saveurs</title>
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
        
        .cart-container {
            background-color: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 5px;
            color: black;
        }
        
        .cart-item {
            display: flex;
            align-items: center;
            border-bottom: 1px solid #ddd;
            padding: 15px 0;
        }
        
        .cart-item img {
            width: 80px;
            height: auto;
            margin-right: 15px;
            border-radius: 5px;
        }
        
        .cart-item-details {
            flex-grow: 1;
        }
        
        .cart-item-actions {
            margin-left: 15px;
        }
        
        .cart-total {
            margin-top: 20px;
            font-size: 1.2em;
            font-weight: bold;
            text-align: right;
        }
        
        .empty-cart {
            text-align: center;
            padding: 30px;
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
            <a href="client.php" class="w3-bar-item w3-button">Retour aux produits</a>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="connexion.php" class="w3-bar-item w3-button">Connexion</a>
            <?php else: ?>
                <a href="logout.php" class="w3-bar-item w3-button">Déconnexion</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="w3-main" style="margin-left:340px;margin-right:40px">
        <div class="w3-container" style="margin-top:80px">
            <h1 class="w3-xxxlarge"><b>Mon Panier</b></h1>
            <hr style="width:50px;border:5px solid #dfaf2c" class="w3-round">
            
            <div class="product-card">
                <?php if (isset($_SESSION['panier']) && !empty($_SESSION['panier'])): ?>
                    <?php foreach ($_SESSION['panier'] as $item): ?>
                        <div class="cart-item">
                            <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['nom']) ?>">
                            <div class="cart-item-details">
                                <h3><?= htmlspecialchars($item['nom']) ?></h3>
                                <p>Prix unitaire: <?= htmlspecialchars($item['prix']) ?> €</p>
                                <p>Quantité: <?= htmlspecialchars($item['quantite']) ?></p>
                                <p><strong>Total: <?= htmlspecialchars($item['prix'] * $item['quantite']) ?> €</strong></p>
                            </div>
                            <div class="cart-item-actions">
                                <a href="panier.php?supprimer=<?= htmlspecialchars($item['id']) ?>" class="w3-button w3-red">Supprimer</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <div class="cart-total">
                        <p>Total de la commande: <?= htmlspecialchars($total) ?> €</p>
                    </div>
                    
                    <div class="w3-padding-16">
                        <form method="post" action="panier.php">
                            <button type="submit" name="valider_commande" class="w3-button w3-green w3-right">Réserver</button>
                        </form>
                        <a href="panier.php?vider=1" class="w3-button w3-red">Vider le panier</a>
                    </div>
                    
                    <?php if (isset($error_message)): ?>
                        <div class="w3-panel w3-red">
                            <p><?= htmlspecialchars($error_message) ?></p>
                        </div>
                    <?php endif; ?>
                    
                <?php else: ?>
                    <div class="empty-cart">
                        <p>Votre panier est vide.</p>
                        <a href="client.php" class="w3-button w3-amber">Retourner aux produits</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>