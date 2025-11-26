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
    $pageActuelle = isset($_GET['page']) ? $_GET['page'] : 'favoris';
    $redirect = "index.php?page=" . $pageActuelle;

    // Conserver les paramètres selon la page
    if ($pageActuelle === 'navigation') {
        // Conserver l'aliment courant
        if (isset($_GET['aliment'])) {
            $redirect .= "&aliment=" . urlencode($_GET['aliment']);
        }
        // Conserver la recette si on est en vue détaillée ET que ce n'est pas celle qu'on toggle
        if (isset($_GET['recette']) && $_GET['recette'] != $_GET['toggle']) {
            $redirect .= "&recette=" . urlencode($_GET['recette']);
        }
    } elseif ($pageActuelle === 'recherche') {
        // Conserver la requête de recherche
        if (isset($_GET['requete'])) {
            $redirect .= "&requete=" . urlencode($_GET['requete']);
        }
        // Conserver la recette si on est en vue détaillée ET que ce n'est pas celle qu'on toggle
        if (isset($_GET['recette']) && $_GET['recette'] != $_GET['toggle']) {
            $redirect .= "&recette=" . urlencode($_GET['recette']);
        }
    } elseif ($pageActuelle === 'favoris') {
        // Conserver la recette si on est en vue détaillée ET que ce n'est pas celle qu'on toggle
        if (isset($_GET['recette']) && $_GET['recette'] != $_GET['toggle']) {
            $redirect .= "&recette=" . urlencode($_GET['recette']);
        }
    }

    header("Location: $redirect");
    exit();
}


?>