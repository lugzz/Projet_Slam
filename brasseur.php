<?php
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

// Message de confirmation
$message = '';

// Gestion des produits - Suppression
if (isset($_GET['deleteProductId'])) {
    $stmt = $pdo->prepare('DELETE FROM produits WHERE id = :id');
    $stmt->execute(['id' => $_GET['deleteProductId']]);
    $message = 'Produit supprimé avec succès.';
}

// Gestion des produits - Modification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['productId'])) {
    $stmt = $pdo->prepare('UPDATE produits SET nom = :nom, prix = :prix, stock_produits_finis = :stock, id_categorie = :categorie, etat_produit = :etat WHERE id = :id');
    $stmt->execute([
        'id' => $_POST['productId'],
        'nom' => $_POST['productNom'],
        'prix' => $_POST['productPrix'],
        'stock' => $_POST['productStock'],
        'categorie' => $_POST['productCategorie'],
        'etat' => $_POST['productEtat']
    ]);
    $message = 'Produit modifié avec succès.';
}

// Gestion des produits - Ajout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['addProduct'])) {
    $stmt = $pdo->prepare('INSERT INTO produits (nom, prix, stock_produits_finis, id_categorie, etat_produit) VALUES (:nom, :prix, :stock, :categorie, :etat)');
    $stmt->execute([
        'nom' => $_POST['productNom'],
        'prix' => $_POST['productPrix'],
        'stock' => $_POST['productStock'],
        'categorie' => $_POST['productCategorie'],
        'etat' => $_POST['productEtat']
    ]);
    $message = 'Produit ajouté avec succès.';
}

// Récupération des produits
$sql_produits = "SELECT * FROM produits";
$stmt_produits = $pdo->prepare($sql_produits);
$stmt_produits->execute();
$produits = $stmt_produits->fetchAll(PDO::FETCH_ASSOC);

// Récupération du produit à modifier
$editProduct = null;
if (isset($_GET['editProductId'])) {
    $stmt = $pdo->prepare('SELECT * FROM produits WHERE id = :id');
    $stmt->execute(['id' => $_GET['editProductId']]);
    $editProduct = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Récupération des recettes
$recettes = $pdo->query('SELECT * FROM recettes')->fetchAll(PDO::FETCH_ASSOC);
$stocks = $pdo->query('SELECT * FROM stock')->fetchAll(PDO::FETCH_ASSOC);

// Si c'est un calcul de recette
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['volume_biere'])) {
    $volume_biere = floatval($_POST['volume_biere']);
    $degre_alcool = floatval($_POST['degre_alcool']);
    $ebc_grains = array_map('floatval', explode(',', $_POST['ebc_grains']));

    $qtt_mal = ($volume_biere * $degre_alcool) / 20;
    $eau_brassage = $qtt_mal * 2.8;
    $eau_rincage = ($volume_biere * 1.25) - ($eau_brassage * 0.7);

    $poids_grains = array_fill(0, count($ebc_grains), $qtt_mal / count($ebc_grains));
    $somme_ebc_poids = 0;

    for ($i = 0; $i < count($ebc_grains); $i++) {
        $somme_ebc_poids += $ebc_grains[$i] * $poids_grains[$i];
    }

    $MCU = (4.23 * $somme_ebc_poids) / $volume_biere;
    $EBC = 2.9396 * pow($MCU, 0.6859);
    $SRM = 0.508 * $EBC;
    $amerisant = $volume_biere * 3;
    $aromatique = $amerisant / 3;
    $levure = $volume_biere / 2;
    
    // Si c'est un enregistrement de recette
    if (isset($_POST['save_recipe'])) {
        $stmt = $pdo->prepare('INSERT INTO recettes (brassage, rincage, MCU, EBC, SRM, amerisant, aromatique, levure, volume, pourcentage, ebc_grains) 
                    VALUES (:brassage, :rincage, :MCU, :EBC, :SRM, :amerisant, :aromatique, :levure, :volume, :pourcentage, :ebc_grains)');

        $stmt->execute([
            'brassage' => $eau_brassage,
            'rincage' => $eau_rincage,
            'MCU' => $MCU,
            'EBC' => $EBC,
            'SRM' => $SRM,
            'amerisant' => $amerisant,
            'aromatique' => $aromatique,
            'levure' => $levure,
            'volume' => $volume_biere,
            'pourcentage' => $degre_alcool,
            'ebc_grains' => implode(',', $ebc_grains)
        ]);
        
        $message = 'La recette a été enregistrée avec succès!';
        
        // Récupération des recettes mises à jour
        $recettes = $pdo->query('SELECT * FROM recettes')->fetchAll(PDO::FETCH_ASSOC);
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
        
        .success-message {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .form-container {
            background-color: rgba(0, 0, 0, 0.7);
            padding: 20px;
            border-radius: 5px;
            margin-top: 20px;
        }
        
        .form-container input[type="text"],
        .form-container input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        
        .form-container button {
            padding: 10px 15px;
            background-color: #dfaf2c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .form-container button:hover {
            background-color: #c99c27;
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
                    <a href="log.php" class="w3-bar-item w3-button">log</a>
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

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="connexion.php" class="w3-bar-item w3-button">Connexion</a>
            <?php else: ?>
                <a href="logout.php" class="w3-bar-item w3-button">Déconnexion</a>
            <?php endif; ?>
        </div>
    </nav>

    <div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" id="myOverlay"></div>

    <div class="w3-main" style="margin-left:340px;margin-right:40px">

        <!-- Message de confirmation -->
        <?php if (!empty($message)): ?>
            <div class="success-message"><?= $message ?></div>
        <?php endif; ?>

        <div class="w3-container" style="margin-top:80px" id="accueil">
        
        <div class="w3-container w3-padding-32" id="produits">
            <h2 class="w3-xxlarge w3-text-amber"><b>Gestion des Produits</b></h2>
            <div class="w3-responsive">
                <table class="w3-table-all w3-hoverable w3-card-4">
                    <thead>
                        <tr class="w3-amber">
                            <th>ID</th>
                            <th>Nom du produit</th>
                            <th>Prix</th>
                            <th>Quantité</th>
                            <th>Catégorie</th>
                            <th>Etat du produit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produits as $produit): ?>
                            <tr>
                                <td><?= htmlspecialchars($produit['id']) ?></td>
                                <td><?= htmlspecialchars($produit['nom']) ?></td>
                                <td><?= htmlspecialchars($produit['prix']) ?> €</td>
                                <td><?= htmlspecialchars($produit['stock_produits_finis']) ?></td>
                                <td><?= htmlspecialchars($produit['id_categorie']) ?></td>
                                <td><?= htmlspecialchars($produit['etat_produit']) ?></td>
                                <td>
                                    <a href="?editProductId=<?= $produit['id'] ?>" class="w3-button w3-small w3-amber">Modifier</a>
                                    <a href="?deleteProductId=<?= $produit['id'] ?>" class="w3-button w3-small w3-red" onclick="return confirm('Êtes-vous sûr de vouloir supprimer ce produit ?');">Supprimer</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <p><a href="?action=addProduct" class="w3-button w3-amber w3-margin-top">Ajouter un produit</a></p>
            
            <!-- Formulaire de modification de produit -->
            <?php if ($editProduct): ?>
                <div class="form-container">
                    <h3 class="w3-large w3-text-amber"><b>Modifier le produit</b></h3>
                    <form method="post" action="">
                        <input type="hidden" name="productId" value="<?= htmlspecialchars($editProduct['id']) ?>">
                        
                        <label for="productNom"><b>Nom du produit</b></label>
                        <input type="text" name="productNom" value="<?= htmlspecialchars($editProduct['nom']) ?>" required>
                        
                        <label for="productPrix"><b>Prix (€)</b></label>
                        <input type="number" step="0.01" name="productPrix" value="<?= htmlspecialchars($editProduct['prix']) ?>" required>
                        
                        <label for="productStock"><b>Quantité en stock</b></label>
                        <input type="number" name="productStock" value="<?= htmlspecialchars($editProduct['stock_produits_finis']) ?>" required>
                        
                        <label for="productCategorie"><b>Catégorie</b></label>
                        <input type="number" name="productCategorie" value="<?= htmlspecialchars($editProduct['id_categorie']) ?>" required>
                        
                        <label for="productEtat"><b>État du produit</b></label>
                        <input type="text" name="productEtat" value="<?= htmlspecialchars($editProduct['etat_produit']) ?>" required>
                        
                        <button type="submit" class="w3-button w3-amber w3-margin-top">Enregistrer les modifications</button>
                        <a href="brasseur.php" class="w3-button w3-gray w3-margin-top">Annuler</a>
                    </form>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire d'ajout de produit -->
            <?php if (isset($_GET['action']) && $_GET['action'] === 'addProduct'): ?>
                <div class="form-container">
                    <h3 class="w3-large w3-text-amber"><b>Ajouter un nouveau produit</b></h3>
                    <form method="post" action="">
                        <input type="hidden" name="addProduct" value="1">
                        
                        <label for="productNom"><b>Nom du produit</b></label>
                        <input type="text" name="productNom" required>
                        
                        <label for="productPrix"><b>Prix (€)</b></label>
                        <input type="number" step="0.01" name="productPrix" required>
                        
                        <label for="productStock"><b>Quantité en stock</b></label>
                        <input type="number" name="productStock" required>
                        
                        <label for="productCategorie"><b>Catégorie</b></label>
                        <input type="number" name="productCategorie" required>
                        
                        <label for="productEtat"><b>État du produit</b></label>
                        <input type="text" name="productEtat" required>
                        
                        <button type="submit" class="w3-button w3-amber w3-margin-top">Ajouter le produit</button>
                        <a href="brasseur.php" class="w3-button w3-gray w3-margin-top">Annuler</a>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <div class="w3-container w3-padding-32">
            <h2 class="w3-xxlarge w3-text-amber"><b>Formulaire de Calcul du Brassage</b></h2>
            <form method="post" class="w3-card w3-padding w3-dark-grey">
                <div>
                    <label><b>Volume de bière (L)</b></label>
                    <input class="w3-input w3-border w3-margin-bottom" type="number" step="0.01" name="volume_biere" required>
                </div>
                <div class="w3-section">
                    <label><b>Degré d'alcool (%)</b></label>
                    <input class="w3-input w3-border w3-margin-bottom" type="number" step="0.1" name="degre_alcool" required>
                </div>
                <div class="w3-section">
                    <label><b>EBC des grains (séparés par une virgule)</b></label>
                    <input class="w3-input w3-border w3-margin-bottom" type="text" name="ebc_grains" required>
                </div>
                <button type="submit" class="w3-button w3-block w3-padding-large w3-amber w3-margin-bottom w3-hover-opacity">
                    <i class="fas fa-calculator"></i> Calculer
                </button>
            </form>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['volume_biere'])): ?>
                <div class="w3-container w3-padding-32">
                    <div class="w3-card w3-padding w3-dark-grey">
                        <h3 class="w3-xlarge w3-text-amber"><b>Résultats :</b></h3>
                        <ul class="w3-ul">
                            <li>Volume de bière : <?= htmlspecialchars($volume_biere) ?> L</li>
                            <li>Pourcentage d'alcool : <?= htmlspecialchars($degre_alcool) ?> %</li>
                            <li>EBC des grains : <?= htmlspecialchars(implode(',', $ebc_grains)) ?></li>
                            <li>Eau de brassage : <?= htmlspecialchars($eau_brassage) ?> L</li>
                            <li>Eau de rinçage : <?= htmlspecialchars($eau_rincage) ?> L</li>
                            <li>MCU : <?= htmlspecialchars($MCU) ?></li>
                            <li>EBC : <?= htmlspecialchars($EBC) ?></li>
                            <li>SRM : <?= htmlspecialchars($SRM) ?></li>
                            <li>Houblon amérisant : <?= htmlspecialchars($amerisant) ?> g</li>
                            <li>Houblon aromatique : <?= htmlspecialchars($aromatique) ?> g</li>
                            <li>Levure utilisée : <?= htmlspecialchars($levure) ?> g</li>
                        </ul>
                        
                        <!-- Bouton d'enregistrement -->
                        <form method="post">
                            <input type="hidden" name="volume_biere" value="<?= htmlspecialchars($volume_biere) ?>">
                            <input type="hidden" name="degre_alcool" value="<?= htmlspecialchars($degre_alcool) ?>">
                            <input type="hidden" name="ebc_grains" value="<?= htmlspecialchars(implode(',', $ebc_grains)) ?>">
                            <input type="hidden" name="eau_brassage" value="<?= htmlspecialchars($eau_brassage) ?>">
                            <input type="hidden" name="eau_rincage" value="<?= htmlspecialchars($eau_rincage) ?>">
                            <input type="hidden" name="MCU" value="<?= htmlspecialchars($MCU) ?>">
                            <input type="hidden" name="EBC" value="<?= htmlspecialchars($EBC) ?>">
                            <input type="hidden" name="SRM" value="<?= htmlspecialchars($SRM) ?>">
                            <input type="hidden" name="amerisant" value="<?= htmlspecialchars($amerisant) ?>">
                            <input type="hidden" name="aromatique" value="<?= htmlspecialchars($aromatique) ?>">
                            <input type="hidden" name="levure" value="<?= htmlspecialchars($levure) ?>">
                            <input type="hidden" name="save_recipe" value="1">
                            <button type="submit" class="w3-button w3-block w3-padding-large w3-green w3-margin-top w3-hover-opacity">
                                <i class="fas fa-save"></i> Enregistrer cette recette
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="w3-container w3-padding-32">
            <h2 class="w3-xxlarge w3-text-amber"><b>Gestion des recettes</b></h2>
            <div class="w3-responsive">
                <table class="w3-table-all w3-hoverable w3-card-4">
                    <thead>
                        <tr class="w3-amber">
                            <th>ID</th>
                            <th>Volume</th>
                            <th>% alcool</th>
                            <th>EBC grains</th>
                            <th>Brassage</th>
                            <th>Rinçage</th>
                            <th>MCU</th>
                            <th>EBC</th>
                            <th>SRM</th>
                            <th>Amérisant</th>
                            <th>Aromatique</th>
                            <th>Levure</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recettes as $recette): ?>
                            <tr>
                                <td><?= htmlspecialchars($recette['id']) ?></td>
                                <td><?= htmlspecialchars($recette['volume']) ?></td>
                                <td><?= htmlspecialchars($recette['pourcentage']) ?> %</td>
                                <td><?= htmlspecialchars($recette['ebc_grains']) ?></td>
                                <td><?= htmlspecialchars($recette['brassage']) ?> L</td>
                                <td><?= htmlspecialchars($recette['rincage']) ?> L</td>
                                <td><?= htmlspecialchars($recette['MCU']) ?></td>
                                <td><?= htmlspecialchars($recette['EBC']) ?></td>
                                <td><?= htmlspecialchars($recette['SRM']) ?></td>
                                <td><?= htmlspecialchars($recette['amerisant']) ?> g</td>
                                <td><?= htmlspecialchars($recette['aromatique']) ?> g</td>
                                <td><?= htmlspecialchars($recette['levure']) ?> g</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="w3-container w3-padding-32">
            <h2 class="w3-xxlarge w3-text-amber"><b>Gestion des stocks</b></h2>
            <div class="w3-responsive">
                <table class="w3-table-all w3-hoverable w3-card-4">
                    <thead>
                        <tr class="w3-amber">
                            <th>ID</th>
                            <th>Quantité</th>
                            <th>Prix achat HT</th>
                            <th>Nom matière</th>
                            <th>Date entrée</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stocks as $stock): ?>
                            <tr>
                                <td><?= htmlspecialchars($stock['id_stockmp']) ?></td>
                                <td><?= htmlspecialchars($stock['quantite']) ?></td>
                                <td><?= htmlspecialchars($stock['prix_achat_ht']) ?> €</td>
                                <td><?= htmlspecialchars($stock['nom_matiere']) ?></td>
                                <td><?= htmlspecialchars($stock['date_entree_stock']) ?></td>
                                <td><?= htmlspecialchars($stock['description_mp']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
     
    </div>

    <script>
        function w3_open() {
            document.getElementById("mySidebar").style.display = "block";
            document.getElementById("myOverlay").style.display = "block";
        }
        
        function w3_close() {
            document.getElementById("mySidebar").style.display = "none";
            document.getElementById("myOverlay").style.display = "none";
        }
    </script>
</body>
</html>