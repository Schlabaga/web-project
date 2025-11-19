<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Gestion de cocktails</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header>
    <nav class="top-menu">

        <div class="menu-left">
            <a href="index.php?page=navigation">Navigation</a>
            <a href="index.php?page=favoris">Mes recettes</a>
            <a href="index.php?page=recherche">Recherche</a>
        </div>

        <div class="menu-right">
            <?php if (isset($_SESSION["login"])) { ?>

                <span class="login-display"><?= htmlspecialchars($_SESSION["login"]) ?></span>
                <a href="index.php?page=profil">Profil</a>
                <a href="index.php?page=logout">Se déconnecter</a>

            <?php } else { ?>

                <form method="post" action="index.php?page=login" class="login-form">
                    <input type="text" name="login" placeholder="Login">
                    <input type="password" name="password" placeholder="Mot de passe">
                    <button type="submit">Connexion</button>
                </form>

                <a href="index.php?page=inscription">S’inscrire</a>

            <?php } ?>
        </div>

    </nav>
</header>

<main>
    <?php
    // la page principale change selon l’action choisie
    if (isset($_GET["page"])) {
        $page = $_GET["page"];

        switch ($page) {
            case "navigation":
                include "pages/navigation.php";
                break;

            case "favoris":
                include "pages/favoris.php";
                break;

            case "recherche":
                include "pages/recherche.php";
                break;

            case "profil":
                include "pages/profil.php";
                break;

            case "inscription":
                include "pages/inscription.php";
                break;

            case "login":
                include "pages/login.php";
                break;

            case "logout":
                include "pages/logout.php";
                break;

            default:
                include "pages/navigation.php";
                break;
        }
    } else {
        include "pages/navigation.php";
    }
    ?>
</main>

<footer>
</footer>

</body>
</html>