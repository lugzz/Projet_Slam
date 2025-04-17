<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$connexion_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['connexion'])) {
    if (isset($_POST['login'], $_POST['password_connexion'])) {
        $login_input = htmlspecialchars($_POST['login']);
        $password_connexion = $_POST['password_connexion'];

        $host       = 'sql109.infinityfree.com';
        $dbname     = 'if0_38342359_brasserie';
        $username_db= 'if0_38342359';
        $password_db= 'gE0DeROeqK';

        try {
            $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username_db, $password_db);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $stmt = $pdo->prepare("SELECT * FROM user WHERE login = :login");
            $stmt->execute(['login' => $login_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password_connexion, $user['password'])) {
                // Initialiser les variables de session
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['role'] = $user['id_role'];
                $_SESSION['login'] = $user['login'];
               
                // Vérifier si c'est la première connexion
                $premiere_co = $user['premiere_co'];
                if($premiere_co == "0"){
                    header("Location: changement_mdp.php");
                    exit();
                }
                
                // Redirection simple vers l'index sans paramètres dans l'URL
                header("Location: index.php");
                exit();
            } else {
                $connexion_message = "<p class='w3-text-red'>Identifiants incorrects.</p>";
            }
        } catch (PDOException $e) {
            $connexion_message = "<p class='w3-text-red'>Erreur: " . $e->getMessage() . "</p>";
        }
    } else {
        $connexion_message = "<p class='w3-text-red'>Veuillez remplir tous les champs de connexion.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <title>Connexion - Terroirs et Saveurs</title>
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
        .btn {
            display: inline-block;
            padding: 15px 25px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
        }
        .btn:hover {
            background-color: #c0392b;
        }
    </style>
</head>
<body>
    <nav class="w3-sidebar w3-collapse w3-top w3-large w3-padding" style="z-index:3;width:300px;font-weight:bold;" id="mySidebar">
        <br>
        <div class="w3-container">
            <h3 class="w3-padding-64"><b>Terroirs<br>et Saveurs</b></h3>
        </div>
        <div class="w3-bar-block">
            <a href="index.php" class="w3-bar-item w3-button">Accueil</a>
            <a href="#connexion" class="w3-bar-item w3-button">Connexion</a>
        </div>
    </nav>

    <div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" id="myOverlay"></div>

    <div class="w3-main" style="margin-left:340px;margin-right:40px">
        <div class="w3-container" style="margin-top:80px" id="connexion">
            <h1 class="w3-xxxlarge w3-text-red"><b>Connexion</b></h1>
            <hr style="width:50px;border:5px solid red" class="w3-round">
            <?php echo $connexion_message; ?>
            <form action="" method="post">
                <div class="w3-section">
                    <label>Login</label>
                    <input class="w3-input w3-border" type="text" name="login" required>
                </div>
                <div class="w3-section">
                    <label>Mot de passe</label>
                    <input class="w3-input w3-border" type="password" name="password_connexion" required>
                </div>
                <input type="submit" name="connexion" class="w3-button w3-block w3-padding-large w3-red" value="Se connecter">
            </form>
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