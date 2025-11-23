<?php
    if (!isset($_SESSION['favoris']) || !is_array($_SESSION['favoris'])) {
        $_SESSION['favoris'] = array();
    }

    // Ajout / suppression d'une recette des favoris
    if (isset($_GET['toggle']) && ctype_digit($_GET['toggle'])) {
        $idRecette = (int) $_GET['toggle'];

        if (in_array($idRecette, $_SESSION['favoris'])) {
            // retirer
            $_SESSION['favoris'] = array_values(
                array_diff($_SESSION['favoris'], array($idRecette))
            );
        } else {
            // ajouter
            $_SESSION['favoris'][] = $idRecette;
        }
    }


