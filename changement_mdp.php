<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: connexion.php");
    exit();
}

$host       = 'sql109.infinityfree.com';
$dbname     = 'if0_38342359_brasserie';
$username_db= 'if0_38342359';
$password_db= 'gE0DeROeqK';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare("SELECT premiere_co FROM user WHERE id_utilisateur = :id");
    $stmt->execute(['id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || $user['premiere_co'] != "0") {
        header("Location: index.php");
        exit();
    }

    $message = "";
    
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['changer_mdp'])) {
        if (!empty($_POST['nouveau_mdp']) && !empty($_POST['confirmer_mdp'])) {
            $nouveau_mdp = $_POST['nouveau_mdp'];
            $confirmer_mdp = $_POST['confirmer_mdp'];
            
            if ($nouveau_mdp === "motdepasse") {
                $message = "<p class='w3-text-red'>Ce mot de passe est trop simple et ne peut pas être utilisé.</p>";
            }
            elseif ($nouveau_mdp === $confirmer_mdp) {
                $password_hash = password_hash($nouveau_mdp, PASSWORD_DEFAULT);

                $update_stmt = $pdo->prepare("UPDATE user SET password = :password, premiere_co = '1' WHERE id_utilisateur = :id");
                $update_stmt->execute([
                    'password' => $password_hash,
                    'id' => $_SESSION['user_id']
                ]);

                header("Location: index.php?login=" . urlencode($_SESSION['login']) . "&role=" . urlencode($_SESSION['role']));
                exit();
            } else {
                $message = "<p class='w3-text-red'>Les mots de passe ne correspondent pas.</p>";
            }
        } else {
            $message = "<p class='w3-text-red'>Veuillez remplir tous les champs.</p>";
        }
    }
} catch (PDOException $e) {
    $message = "<p class='w3-text-red'>Erreur: " . $e->getMessage() . "</p>";
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Changement de mot de passe - Terroirs et Saveurs</title>
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
            color: red;
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
        .container {
            max-width: 500px;
            margin: 100px auto;
            padding: 20px;
            background: rgba(0, 0, 0, 0.7);
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            border-radius: 8px;
        }
        .btn {
            display: inline-block;
            padding: 15px 25px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            margin: 10px;
            width: 100%;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>

<div class="container">
    <h2 class="w3-center">Changement de mot de passe</h2>
    <p class="w3-center">Veuillez choisir un nouveau mot de passe.</p>

    <?php echo $message; ?>

    <form action="" method="post">
        <label>Nouveau mot de passe</label>
        <input class="w3-input w3-border" type="password" name="nouveau_mdp" required>
        
        <label>Confirmer le mot de passe</label>
        <input class="w3-input w3-border" type="password" name="confirmer_mdp" required>

        <br>
        <button type="submit" name="changer_mdp" class="btn">Modifier</button>
    </form>
</div>
</body>
</html>