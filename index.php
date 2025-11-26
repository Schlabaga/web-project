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

        <!-- BOUTONS GAUCHES -->
        <div class="menu-left">
            <a href="index.php?page=navigation">Navigation</a>
            <a href="index.php?page=favoris">Recettes <strong>&#10084;</strong></a>
        </div>

        <!-- BARRE DE RECHERCHE AU CENTRE -->
        <div class="menu-center">
            <form method="get" action="index.php" class="search-form">
                <!-- on force la page "recherche" -->
                <input type="hidden" name="page" value="recherche">

                <label for="champ-recherche">Recherche&nbsp;:</label>
                <input
                    type="text"
                    id="champ-recherche"
                    name="requete"
                    value="<?php
                        // on laisse la requête affichée quand on est sur la page de recherche
                        if (isset($_GET['page']) && $_GET['page'] === 'recherche' && isset($_GET['requete'])) {
                            echo htmlspecialchars($_GET['requete']);
                        }
                    ?>"
                >
                <button type="submit" class="search-button" title="Rechercher">
                    &#128269;
                </button>
            </form>
        </div>

        <!-- ZONE DE CONNEXION À DROITE -->
        <div class="menu-right">
            <?php if (isset($_SESSION["login"])) { ?>

                <span class="login-display"><?= htmlspecialchars($_SESSION["login"]) ?></span>
                <a href="index.php?page=profil">Profil</a>
                <a href="index.php?page=deconnexion">Se déconnecter</a>

            <?php } else { ?>

                <form method="post" action="index.php?page=connexion" class="login-form">
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
    // page principale change selon l’action choisie
    if (isset($_GET["page"])) {
        $page = $_GET["page"];

        switch ($page) {

            case "favoris":
                include "pages/recettes.php";
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

            case "connexion":
                include "pages/connexion.php";
                break;

            case "deconnexion":
                include "pages/deconnexion.php";
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
