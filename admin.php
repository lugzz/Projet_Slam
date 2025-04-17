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

if ($_SESSION['role'] != 1) {
    header('Location: index.php');
    exit;
}

function ajouterLog($message) {
    $fichier = 'logs.txt';
    $date = date('Y-m-d H:i:s');
    $ligne = "[$date] $message" . PHP_EOL;
    file_put_contents($fichier, $ligne, FILE_APPEND);
}

#Modification de l'utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Id'])) {

 

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user WHERE login = :login AND id_utilisateur != :id_utilisateur'); 
    $stmt->execute(['login' => $login, 'id_utilisateur' => $_POST['Id']]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        die("Erreur : Ce login existe déjà. Veuillez en choisir un autre.");
    }

    $stmt = $pdo->prepare('UPDATE user SET nom = :nom, prenom = :prenom, email = :email, id_role = :id_role, etat_compte = :etat_compte, login = :login WHERE id_utilisateur = :id_utilisateur');
    $stmt->execute([
        'id_utilisateur' => $_POST['Id'],
        'nom' => $_POST['Nom'],
        'prenom' => $_POST['Prenom'],
        'email' => $_POST['Email'],
        'id_role' => $_POST['Role'],
        'etat_compte' => $_POST['EtatCompte'],
        'login' => $login,  
    ]);
    echo "Utilisateur modifié avec succès.";
}
# Ajout d'un Utilisateur
if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && !isset($_POST['Id']) 
    && isset($_POST['Nom'], $_POST['Prenom'], $_POST['Email'], $_POST['Role'], $_POST['EtatCompte'])) {


    $prenom = strtolower(trim($_POST['Prenom']));
    $nom = strtolower(trim($_POST['Nom']));
    $login = substr($prenom, 0, 1) . $nom . "25";

    // Vérifier que le login n'existe pas déjà
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM user WHERE login = :login');
    $stmt->execute(['login' => $login]);
    $exists = $stmt->fetchColumn();

    if ($exists) {
        die("Erreur : Ce login existe déjà. Veuillez en choisir un autre.");
    }

    // Hachage du mot de passe par défaut
    $password_hashed = password_hash("motdepasse", PASSWORD_DEFAULT);


    $stmt = $pdo->prepare('INSERT INTO user 
        (nom, prenom, email, creer_le, etat_compte, premiere_co, login, password, id_role) 
        VALUES (:nom, :prenom, :email, NOW(), :etat_compte, :premiere_co, :login, :password, :id_role)');
    
    $stmt->execute([
        'nom' => $_POST['Nom'],
        'prenom' => $_POST['Prenom'],
        'email' => $_POST['Email'],
        'etat_compte' => $_POST['EtatCompte'],
        'premiere_co' => 1, 
        'login' => $login,
        'password' => $password_hashed,
        'id_role' => $_POST['Role'],
    ]);

    echo "Utilisateur ajouté avec succès. Login : <strong>$login</strong>";
    ajouterLog("Utilisateur créé : $login par " . $user['login']);
}

#Suppression de l'utilisateur
if (isset($_GET['deleteId'])) {
    $stmt = $pdo->prepare('DELETE FROM user WHERE id_utilisateur = :id_utilisateur');
    $stmt->execute(['id_utilisateur' => $_GET['deleteId']]);
    echo "Utilisateur supprimé avec succès.";
}


$users = $pdo->query('SELECT * FROM user')->fetchAll(PDO::FETCH_ASSOC);


$editUser = null;
if (isset($_GET['editId'])) {
    $stmt = $pdo->prepare('SELECT * FROM user WHERE id_utilisateur = :id_utilisateur');
    $stmt->execute(['id_utilisateur' => $_GET['editId']]);
    $editUser = $stmt->fetch(PDO::FETCH_ASSOC);
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
        
            <h1 class="w3-xxxlarge"><b>Voir les utilisateurs</b></h1>

            <div class="w3-container w3-padding-32">
            <div class="w3-responsive">
                <table class="w3-table-all w3-hoverable w3-card-4">
                    <thead>
                        <tr class="w3-amber">
                        <th>ID</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>État du Compte</th>
                <th>Login</th>
                <th>Actions</th>

                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                    <td><?= htmlspecialchars($user['id_utilisateur']) ?></td>
                    <td><?= htmlspecialchars($user['nom']) ?></td>
                    <td><?= htmlspecialchars($user['prenom']) ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['id_role']) ?></td>
                    <td><?= htmlspecialchars($user['etat_compte']) ?></td>
                    <td><?= htmlspecialchars($user['login']) ?></td>
                    <td>
                        <a href="?editId=<?= $user['id_utilisateur'] ?>">Modifier</a> |
                        <a href="?deleteId=<?= $user['id_utilisateur'] ?>" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?');">Supprimer</a>
                    </td>
                </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($editUser): ?>
        <h2>Modifier Utilisateur</h2>
        <form action="" method="post">
            <input type="hidden" name="Id" value="<?= htmlspecialchars($editUser['id_utilisateur']) ?>">
            <label for="Nom">Nom:</label>
            <input type="text" name="Nom" value="<?= htmlspecialchars($editUser['nom']) ?>"><br>
            <label for="Prenom">Prénom:</label>
            <input type="text" name="Prenom" value="<?= htmlspecialchars($editUser['prenom']) ?>"><br>
            <label for="Email">Email:</label>
            <input type="email" name="Email" value="<?= htmlspecialchars($editUser['email']) ?>"><br>
            <label for="Role">Rôle:</label>
            <input type="text" name="Role" value="<?= htmlspecialchars($editUser['id_role']) ?>"><br>
            <label for="EtatCompte">État du Compte:</label>
            <input type="text" name="EtatCompte" value="<?= htmlspecialchars($editUser['etat_compte']) ?>"><br>
            <button type="submit">Modifier</button>
        </form>
    <?php endif; ?>

            <p><a href="?action=add" class="button">Ajouter Utilisateur</a></p>


    <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
        <h2>Ajouter Utilisateur</h2>
        <form action="" method="post">
            <label for="Nom">Nom:</label>
            <input type="text" name="Nom" required><br>
            <label for="Prenom">Prénom:</label>
            <input type="text" name="Prenom" required><br>
            <label for="Email">Email:</label>
            <input type="email" name="Email" required><br>
            <label for="Role">Rôle:</label>
            <input type="text" name="Role" required><br>
            <label for="EtatCompte">État du Compte:</label>
            <input type="text" name="EtatCompte" required><br>
            <button type="submit">Ajouter</button>
        </form>
    <?php endif; ?>
        </div>
    </div>
</body>
</html>