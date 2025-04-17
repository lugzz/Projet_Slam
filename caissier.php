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
    die("Erreur de connexion : " . $e->getMessage());
}

function ajouterLog($message) {
    $fichier = 'logs.txt';
    $date = date('Y-m-d H:i:s');
    $ligne = "[$date] $message" . PHP_EOL;
    file_put_contents($fichier, $ligne, FILE_APPEND);
}

// Récupération des produits disponibles (état = 1)
try {
    $stmt = $pdo->prepare("SELECT id, nom, prix, stock_produits_finis FROM produits WHERE etat_produit = 1");
    $stmt->execute();
    $produits = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des produits : " . $e->getMessage());
}

// Récupération des réservations en attente
try {
    $stmt = $pdo->prepare("SELECT v.id_vente, v.date_vente, v.total, u.nom, u.prenom 
                          FROM ventes v 
                          JOIN user u ON v.id_utilisateur = u.id_utilisateur 
                          WHERE v.type_vente = 'reservation'");
    $stmt->execute();
    $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des réservations : " . $e->getMessage());
}

// Récupération des clients pour la sélection
try {
    $stmt = $pdo->prepare("SELECT id_utilisateur, nom, prenom FROM user WHERE id_role = 4");
    $stmt->execute();
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur lors de la récupération des clients : " . $e->getMessage());
}

$message = "";

// Traitement de la création d'un compte client
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['creer_client'])) {
    $prenom = strtolower(trim($_POST['prenom']));
    $nom = strtolower(trim($_POST['nom']));
    $email = $_POST['email'];
    $login = substr($prenom, 0, 1) . $nom . "25";

    // Vérifier que le login n'existe pas déjà
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user WHERE login = :login');
    $stmt->execute(['login' => $login]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        $message = "Erreur : Ce login existe déjà. Veuillez essayer avec un autre nom.";
    } else {
        // Hachage du mot de passe par défaut
        $password_hashed = password_hash("motdepasse", PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();
            
            // Insertion dans la table user
            $stmt = $pdo->prepare('INSERT INTO user 
                (nom, prenom, email, creer_le, etat_compte, premiere_co, login, password, id_role) 
                VALUES (:nom, :prenom, :email, NOW(), :etat_compte, :premiere_co, :login, :password, :id_role)');
            
            $stmt->execute([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'etat_compte' => 1, // compte actif
                'premiere_co' => 0, // première connexion = vrai (l'utilisateur devra changer le mot de passe)
                'login' => $login,
                'password' => $password_hashed,
                'id_role' => 4, // Rôle client
            ]);
            
            $user_id = $pdo->lastInsertId();
            
            // Création du compte fidélité
            $stmt = $pdo->prepare('INSERT INTO client_fideliter (id_utilisateur, cagnotte, date_fidelisation) VALUES (:id_utilisateur, 0, NOW())');
            $stmt->execute(['id_utilisateur' => $user_id]);
            
            $pdo->commit();
            
            $message = "Client ajouté avec succès. Login : <strong>$login</strong>";
            ajouterLog("Client créé : $login par " . $_SESSION['login']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            $message = "Erreur lors de la création du client : " . $e->getMessage();
        }
    }
}

// Traitement de la validation d'une réservation
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['valider_reservation'])) {
    $reservation_id = $_POST['reservation_id'];

    try {
        $stmt = $pdo->prepare("UPDATE ventes SET type_vente = 'validée' WHERE id_vente = :id_vente AND type_vente = 'reservation'");
        $stmt->execute(['id_vente' => $reservation_id]);
        
        if ($stmt->rowCount() > 0) {
            $message = "Réservation n°$reservation_id validée avec succès.";
            ajouterLog("Réservation n°$reservation_id validée par " . $_SESSION['login']);
        } else {
            $message = "Aucune réservation trouvée avec cet ID ou déjà validée.";
        }
    } catch (PDOException $e) {
        $message = "Erreur lors de la validation de la réservation : " . $e->getMessage();
    }
}

// Traitement de l'enregistrement d'une vente
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['enregistrer_vente'])) {
    $id_client = $_POST['id_client'];
    $remise = floatval($_POST['remise'] ?? 0);
    $produits_commandes = $_POST['quantite'] ?? [];
    $utiliser_points = isset($_POST['utiliser_points']) ? true : false;
    
    // Vérification de l'existence du client
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user WHERE id_utilisateur = :id_utilisateur AND id_role = 4");
    $stmt->execute(['id_utilisateur' => $id_client]);
    $client_existe = $stmt->fetchColumn();
    
    if (!$client_existe) {
        $message = "Erreur : Client non trouvé.";
    } else {
        try {
            $pdo->beginTransaction();
            
            // Calcul du total et vérification des stocks
            $total = 0;
            $produits_valides = [];
            
            foreach ($produits_commandes as $id_produit => $quantite) {
                if ($quantite > 0) {
                    $stmt = $pdo->prepare("SELECT prix, stock_produits_finis FROM produits WHERE id = :id");
                    $stmt->execute(['id' => $id_produit]);
                    $produit = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($produit && $produit['stock_produits_finis'] >= $quantite) {
                        $total += $produit['prix'] * $quantite;
                        $produits_valides[$id_produit] = [
                            'quantite' => $quantite,
                            'prix' => $produit['prix']
                        ];
                    } else {
                        throw new Exception("Stock insuffisant pour le produit ID: $id_produit");
                    }
                }
            }
            
            if (empty($produits_valides)) {
                throw new Exception("Aucun produit valide dans la commande");
            }
            
            // Application de la remise
            $montant_remise = $total * ($remise / 100);
            $total_apres_remise = $total - $montant_remise;
            
            // Gestion des points fidélité
            $points_utilises = 0;
            if ($utiliser_points) {
                $stmt = $pdo->prepare("SELECT cagnotte FROM client_fideliter WHERE id_utilisateur = :id_utilisateur");
                $stmt->execute(['id_utilisateur' => $id_client]);
                $client_fidelite = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($client_fidelite && $client_fidelite['cagnotte'] > 0) {
                    $points_utilises = min($client_fidelite['cagnotte'], $total_apres_remise);
                    $total_apres_remise -= $points_utilises;
                    
                    // Mise à jour des points fidélité
                    $stmt = $pdo->prepare("UPDATE client_fideliter SET cagnotte = cagnotte - :points WHERE id_utilisateur = :id_utilisateur");
                    $stmt->execute([
                        'points' => $points_utilises,
                        'id_utilisateur' => $id_client
                    ]);
                }
            }
            
            // Enregistrement de la vente
            $stmt = $pdo->prepare("INSERT INTO ventes (reductions, date_vente, total, type_vente, id_utilisateur) 
                                  VALUES (:reductions, NOW(), :total, 'vente', :id_utilisateur)");
            $stmt->execute([
                'reductions' => $remise,
                'total' => $total_apres_remise,
                'id_utilisateur' => $id_client
            ]);
            
            $id_vente = $pdo->lastInsertId();
            
            // Enregistrement des détails de la vente
            foreach ($produits_valides as $id_produit => $info) {
                $stmt = $pdo->prepare("INSERT INTO ventes_details (id_produit, id_vente, quantite, prix_unitaire) 
                       VALUES (:id_produit, :id_vente, :quantite, :prix_unitaire)");
                $stmt->execute([
                    'id_produit' => $id_produit,
                    'id_vente' => $id_vente,
                    'quantite' => $info['quantite'],
                    'prix_unitaire' => $info['prix']
                ]);
        
                // Mise à jour du stock
                $stmt = $pdo->prepare("UPDATE produits SET stock_produits_finis = stock_produits_finis - :quantite 
                                      WHERE id = :id_produit");
                $stmt->execute([
                    'quantite' => $info['quantite'],
                    'id_produit' => $id_produit
                ]);
            }
            
            // Ajout des points de fidélité (10% du montant payé)
            $points_gagnes = $total_apres_remise * 0.1;
            $stmt = $pdo->prepare("UPDATE client_fideliter SET cagnotte = cagnotte + :points 
                                  WHERE id_utilisateur = :id_utilisateur");
            $stmt->execute([
                'points' => $points_gagnes,
                'id_utilisateur' => $id_client
            ]);
            
            $pdo->commit();
            
            $message = "Vente enregistrée avec succès. Montant total: " . number_format($total_apres_remise, 2) . " €";
            if ($points_utilises > 0) {
                $message .= " (Points utilisés: " . number_format($points_utilises, 2) . " €)";
            }
            $message .= ". Points fidélité gagnés: " . number_format($points_gagnes, 2);
            
            ajouterLog("Vente n°$id_vente enregistrée par " . $_SESSION['login'] . " pour le client #$id_client");
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = "Erreur lors de l'enregistrement de la vente : " . $e->getMessage();
        }
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
    <link rel="stylesheet" href="caissier.css">
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
                <a href="log.php" class="w3-bar-item w3-button">Log</a>
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
    <div class="w3-container" style="margin-top:80px" id="caissier">
        <h1 class="w3-xxxlarge"><b>Système de Caisse</b></h1>
        
        <?php if (!empty($message)): ?>
            <div class="message <?php echo (strpos($message, 'Erreur') !== false) ? 'error' : 'success'; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <!-- Onglets pour les différentes fonctionnalités -->
        <div class="w3-bar w3-amber">
            <button class="w3-bar-item w3-button" onclick="openTab('vente')">Enregistrer une vente</button>
            <button class="w3-bar-item w3-button" onclick="openTab('client')">Créer un compte client</button>
            <button class="w3-bar-item w3-button" onclick="openTab('reservation')">Valider une réservation</button>
        </div>

        <!-- Formulaire d'enregistrement de vente -->
        <div id="vente" class="tab-content">
            <div class="form-container">
                <h3>Enregistrer une vente</h3>
                <form method="POST" action="">
                    <div class="w3-row-padding">
                        <div class="w3-half">
                            <label for="id_client">Sélectionner un client :</label>
                            <select name="id_client" id="id_client" class="w3-select" required>
                                <option value="">Choisir un client</option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id_utilisateur']; ?>">
                                        <?php echo htmlspecialchars($client['prenom'] . ' ' . $client['nom']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="w3-half">
                            <label for="remise">Remise globale (%) :</label>
                            <input type="number" name="remise" id="remise" class="w3-input" min="0" max="100" value="0">
                        </div>
                    </div>
                    
                    <div class="w3-row-padding">
                        <div class="w3-col">
                            <label for="utiliser_points">
                                <input type="checkbox" name="utiliser_points" id="utiliser_points">
                                Utiliser les points de fidélité disponibles
                            </label>
                        </div>
                    </div>
                    
                    <hr>
                    <h4>Produits disponibles :</h4>
                    
                    <?php if (empty($produits)): ?>
                        <p>Aucun produit disponible en stock.</p>
                    <?php else: ?>
                        <?php foreach ($produits as $produit): ?>
                            <div class="produit-item">
                                <div class="w3-row">
                                    <div class="w3-col m6">
                                        <strong><?php echo htmlspecialchars($produit['nom']); ?></strong>
                                        <p>Prix : <?php echo number_format($produit['prix'], 2); ?> € | 
                                           Stock : <?php echo $produit['stock_produits_finis']; ?></p>
                                    </div>
                                    <div class="w3-col m6">
                                        <label for="quantite_<?php echo $produit['id']; ?>">Quantité :</label>
                                        <input type="number" name="quantite[<?php echo $produit['id']; ?>]" 
                                               id="quantite_<?php echo $produit['id']; ?>" class="w3-input" 
                                               min="0" max="<?php echo $produit['stock_produits_finis']; ?>" value="0">
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    
                    <div class="w3-margin-top">
                        <button type="submit" name="enregistrer_vente" class="w3-button w3-green w3-right">
                            Enregistrer la vente
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Formulaire de création de compte client -->
        <div id="client" class="tab-content" style="display:none">
            <div class="form-container">
                <h3>Créer un compte client</h3>
                <form method="POST" action="">
                    <div class="w3-row-padding">
                        <div class="w3-half">
                            <label for="nom">Nom :</label>
                            <input type="text" name="nom" id="nom" class="w3-input" required>
                        </div>
                        <div class="w3-half">
                            <label for="prenom">Prénom :</label>
                            <input type="text" name="prenom" id="prenom" class="w3-input" required>
                        </div>
                    </div>
                    
                    <div class="w3-row-padding">
                        <div class="w3-col">
                            <label for="email">Email :</label>
                            <input type="email" name="email" id="email" class="w3-input" required>
                        </div>
                    </div>
                    
                    <div class="w3-margin-top">
                        <p>Un mot de passe par défaut sera généré : "motdepasse"</p>
                        <p>Le client devra changer son mot de passe lors de sa première connexion.</p>
                        <button type="submit" name="creer_client" class="w3-button w3-blue w3-right">
                            Créer le compte client
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Formulaire de validation de réservation -->
        <div id="reservation" class="tab-content" style="display:none">
            <div class="form-container">
                <h3>Valider une réservation</h3>
                
                <?php if (empty($reservations)): ?>
                    <p>Aucune réservation en attente.</p>
                <?php else: ?>
                    <table class="w3-table-all w3-hoverable w3-card-4">
                        <thead>
                            <tr class="w3-amber">
                                <th>ID</th>
                                <th>Client</th>
                                <th>Date</th>
                                <th>Montant</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $reservation): ?>
                                <tr>
                                    <td><?php echo $reservation['id_vente']; ?></td>
                                    <td><?php echo htmlspecialchars($reservation['prenom'] . ' ' . $reservation['nom']); ?></td>
                                    <td><?php echo $reservation['date_vente']; ?></td>
                                    <td><?php echo number_format($reservation['total'], 2); ?> €</td>
                                    <td>
                                        <form method="POST" action="" style="display:inline;">
                                            <input type="hidden" name="reservation_id" value="<?php echo $reservation['id_vente']; ?>">
                                            <button type="submit" name="valider_reservation" class="w3-button w3-green w3-small">
                                                Valider
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function openTab(tabName) {
    var i;
    var x = document.getElementsByClassName("tab-content");
    for (i = 0; i < x.length; i++) {
        x[i].style.display = "none";  
    }
    document.getElementById(tabName).style.display = "block";
}

// Ouvrir l'onglet par défaut
document.addEventListener('DOMContentLoaded', function() {
    openTab('vente');
});
</script>

</body>
</html>