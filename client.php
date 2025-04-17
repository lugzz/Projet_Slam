<?php
session_start();

$host = 'sql109.infinityfree.com';
$dbname = 'if0_38342359_brasserie';
$username = 'if0_38342359';  
$password = 'gE0DeROeqK'; 

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}

if (!isset($_SESSION['user_id'])) {
    header('Location: connexion.php');
    exit;
}



// Gestion du panier
if(isset($_POST['id_produit']) && isset($_POST['quantite'])) {
    $id_produit = $_POST['id_produit'];
    $quantite = (int)$_POST['quantite'];
    
    if($quantite <= 0) {
        $quantite = 1;
    }
    
    // Récupérer les infos du produit
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id = ?");
    $stmt->execute([$id_produit]);
    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($produit) {
        // Initialiser le panier si nécessaire
        if(!isset($_SESSION['panier'])) {
            $_SESSION['panier'] = [];
        }
        
        // Vérifier si le produit est déjà dans le panier
        $produit_existe = false;
        foreach($_SESSION['panier'] as &$item) {
            if($item['id'] == $id_produit) {
                $item['quantite'] += $quantite;
                $produit_existe = true;
                break;
            }
        }
        
        if(!$produit_existe) {
            $_SESSION['panier'][] = [
                'id' => $id_produit,
                'nom' => $produit['nom'],
                'prix' => $produit['prix'],
                'quantite' => $quantite,
                'image' => $produit['url_img']
            ];
        }
        
        // Redirection vers la page du panier après ajout
        header('Location: panier.php');
        exit;
    }
}

$stmt = $pdo->prepare("SELECT * FROM client_fideliter WHERE id_utilisateur = ?");
$stmt->execute([$_SESSION['user_id']]);
$fidelite = $stmt->fetch(PDO::FETCH_ASSOC);

$pointsFidelite = $fidelite['cagnotte'];
$dateFidelisation = $fidelite['date_fidelisation'];



$stmt_commandes = $pdo->prepare("SELECT * FROM ventes WHERE id_utilisateur = ?");
$stmt_commandes->execute([$_SESSION['user_id']]);
$commandes = $stmt_commandes->fetchAll(PDO::FETCH_ASSOC);

$details_commandes = [];
if (isset($_GET['id_vente'])) {
    $id_vente = $_GET['id_vente'];
    $details_stmt = $pdo->prepare("SELECT * FROM ventes_details WHERE id_vente = ?");
    $details_stmt->execute([$id_vente]);
    $details_commandes = $details_stmt->fetchAll(PDO::FETCH_ASSOC);
}

$produits = [];
if (isset($_GET['commander']) || !isset($_GET['id_vente'])) {
    try {
        $sql = "SELECT * FROM produits WHERE etat_produit = 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        die("Erreur lors de la récupération des produits : " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Terroirs et Saveurs</title>
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

        .product-card {
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            padding: 20px;
            margin: 10px;
            background-color: #fff;
            text-align: center;
            transition: transform 0.3s ease-in-out;
            color: black;
        }
        .product-card img {
            width: 100%;
            height: auto;
            border-radius: 5px;
        }
        .product-card:hover {
            transform: translateY(-10px);
        }
        .product-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .logo {
            width: 100px; 
            margin-bottom: 5px;
        }

        .welcome-msg {
            padding: 10px;
            color: white;
            font-weight: bold;
        }
        .w3-table-all td, .w3-table-all th {
            color: black !important;
        }
        
        .cart-icon {
            position: relative;
            display: inline-block;
            margin-right: 15px;
        }
        
        .cart-count {
            position: absolute;
            top: -5px;
            right: -5px;
            background-color: #dfaf2c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
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
            
            <a href="panier.php" class="w3-bar-item w3-button">
                Panier
                <?php if(isset($_SESSION['panier']) && count($_SESSION['panier']) > 0): ?>
                    <span class="cart-count"><?php echo count($_SESSION['panier']); ?></span>
                <?php endif; ?>
            </a>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="connexion.php" class="w3-bar-item w3-button">Connexion</a>
            <?php else: ?>
                <a href="logout.php" class="w3-bar-item w3-button">Déconnexion</a>
            <?php endif; ?>
        </div>
    </nav>


    <div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" id="myOverlay"></div>


    <div class="w3-main" style="margin-left:340px;margin-right:40px">

        <div class="w3-container" style="margin-top:80px" id="accueil">
            <h1 class="w3-xxxlarge"><b>Voici votre Nombre de points de fidélité</b></h1>
            <hr style="width:50px;border:5px solid #dfaf2c" class="w3-round">
            <div class="w3-container" id="produits" style="margin-top:75px">
                <div class="product-container">
                    <p>Vous avez <?= htmlspecialchars($pointsFidelite) ?> points.</p>
                </div>
            </div>

            <h1 class="w3-xxxlarge"><b>Voir mes commandes : </b></h1>
            <hr style="width:50px;border:5px solid #dfaf2c" class="w3-round">

            <div class="w3-container w3-padding-32">
                <div class="w3-responsive">
                    <table class="w3-table-all w3-hoverable w3-card-4">
                        <thead>
                            <tr class="w3-amber">
                                <th>ID</th>
                                <th>Réductions</th>
                                <th>Date</th>
                                <th>Prix total</th>
                                <th>Type</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($commandes)): ?>
                                <?php foreach ($commandes as $commande): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($commande['id_vente']) ?></td>
                                        <td><?= htmlspecialchars($commande['reductions']) ?></td>
                                        <td><?= htmlspecialchars($commande['date_vente']) ?></td>
                                        <td><?= htmlspecialchars($commande['total']) ?> €</td>
                                        <td><?= htmlspecialchars($commande['type_vente']) ?></td>
                                        <td><a href="?id_vente=<?= htmlspecialchars($commande['id_vente']) ?>">Visualiser les Détails</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6">Aucune commande trouvée.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>


                <div class="w3-margin-top">
                    <a href="?commander=1" class="w3-button w3-amber">Commander</a>
                </div>


                <?php if (isset($_GET['id_vente']) && !empty($details_commandes)): ?>
                    <h2 class="w3-text-amber">Détails de la commande ID: <?= htmlspecialchars($_GET['id_vente']) ?></h2>
                    <div class="w3-responsive">
                        <table class="w3-table-all w3-hoverable w3-card-4">
                            <thead>
                                <tr class="w3-amber">
                                    <th>ID Produit</th>
                                    <th>Quantité</th>
                                    <th>Prix Unitaire</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($details_commandes as $detail): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($detail['id_produit']) ?></td>
                                        <td><?= htmlspecialchars($detail['quantite']) ?></td>
                                        <td><?= htmlspecialchars($detail['prix_unitaire']) ?> €</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>

                <?php if(!isset($_GET['id_vente'])): ?>
                    
                    <?php if (!empty($produits) && isset($_GET['commander'])): ?>
                        <h1 class="w3-xxxlarge w3-margin-top"><b>Nos produits</b></h1>
                    <hr style="width:50px;border:5px solid #dfaf2c" class="w3-round">
                    
                    <div class="product-container">
                        <?php foreach ($produits as $produit): ?>
                            <div class="product-card">
                                <img src="<?php echo htmlspecialchars($produit['url_img']); ?>" alt="<?php echo htmlspecialchars($produit['nom']); ?>">
                                <h3><?php echo htmlspecialchars($produit['nom']); ?></h3>
                                <p><?php echo htmlspecialchars($produit['description']); ?></p>
                                <p><strong><?php echo htmlspecialchars($produit['prix']); ?> €</strong></p>
                                <form method="post" action="client.php">
                                    <input type="hidden" name="id_produit" value="<?php echo htmlspecialchars($produit['id']); ?>">
                                    <label for="quantite_<?php echo htmlspecialchars($produit['id']); ?>">Quantité:</label>
                                    <input type="number" id="quantite_<?php echo htmlspecialchars($produit['id']); ?>" name="quantite" value="1" min="1" max="<?php echo htmlspecialchars($produit['stock_produits_finis']); ?>">
                                    <button type="submit" class="w3-button w3-amber">Ajouter au panier</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        
                    <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>