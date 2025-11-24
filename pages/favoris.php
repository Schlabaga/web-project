<?php

include_once "fonctions.php";

if (!isset($_SESSION['favoris'])) {
    $_SESSION['favoris'] = array();
}

// Toggle favoris
if (isset($_GET['toggle']) && ctype_digit($_GET['toggle'])) {
    $idRecette = (int) $_GET['toggle'];

    // la recette existe?
    if (isset($Recettes[$idRecette])) {
        // enlever des favoris si déjà
        if (in_array($idRecette, $_SESSION['favoris'])) {
            $key = array_search($idRecette, $_SESSION['favoris']);
            unset($_SESSION['favoris'][$key]);
            // enlever les trous
            $_SESSION['favoris'] = array_values($_SESSION['favoris']);
        }
        // ajouter
        else {
            $_SESSION['favoris'][] = $idRecette;
        }

        // SAUVEGARDER dans le fichier JSON si l'utilisateur est connecté
        if (isset($_SESSION['login'])) {
            $fichierUtilisateurs = "data/utilisateurs.json";

            if (file_exists($fichierUtilisateurs)) {
                $contenu = file_get_contents($fichierUtilisateurs);
                $utilisateurs = json_decode($contenu, true);

                if (isset($utilisateurs[$_SESSION['login']])) {
                    // Mettre à jour les favoris de l'utilisateur
                    $utilisateurs[$_SESSION['login']]['favoris'] = $_SESSION['favoris'];

                    // Sauvegarder dans le fichier
                    $json = json_encode($utilisateurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    file_put_contents($fichierUtilisateurs, $json);
                }
            }
        }
    }

    // Redirection pour éviter le double toggle au refresh
    $pageActuelle = isset($_GET['page']) ? $_GET['page'] : 'recettes';
    $redirect = "index.php?page=" . $pageActuelle;

    // Conserver les paramètres selon la page
    if ($pageActuelle === 'recettes') {
        // Conserver le tri, la recherche, etc.
        if (isset($_GET['tri'])) {
            $redirect .= "&tri=" . urlencode($_GET['tri']);
        }
        if (isset($_GET['recherche'])) {
            $redirect .= "&recherche=" . urlencode($_GET['recherche']);
        }
    }

    header("Location: $redirect");
    exit();
}


?>