<?php

function getSousCategories($hierarchie, $aliment) {
    if (isset($hierarchie[$aliment]['sous-categorie'])) {
        return $hierarchie[$aliment]['sous-categorie'];
    }
    return array();
}

function buildChemin($hierarchie, $aliment) {
    $chemin = array($aliment);

    while ($aliment !== 'Aliment') {
        if (!isset($hierarchie[$aliment]['super-categorie']) ||
            empty($hierarchie[$aliment]['super-categorie'])) {
            break;
        }
        $parents = $hierarchie[$aliment]['super-categorie'];
        $aliment = $parents[0]; // on prend le premier parent
        array_unshift($chemin, $aliment);
    }

    return $chemin;
}

function collectAlimentsDescendants($hierarchie, $aliment, &$result) {
    if (!in_array($aliment, $result)) {
        $result[] = $aliment;
    }

    if (isset($hierarchie[$aliment]['sous-categorie'])) {
        foreach ($hierarchie[$aliment]['sous-categorie'] as $sous) {
            collectAlimentsDescendants($hierarchie, $sous, $result);
        }
    }
}

// nom de fichier image à partir du titre
function getImageFileNameFromTitle($titre) {
    // minuscules
    $file = strtolower($titre);

    // enlever accents
    $converted = @iconv('UTF-8', 'ASCII//TRANSLIT', $file);
    if ($converted !== false) {
        $file = $converted;
    }

    // ne garder que lettres + espaces
    $file = preg_replace('/[^a-z ]+/', '', $file);

    $file = trim($file);
    $file = preg_replace('/\s+/', '_', $file);

    if ($file === '') {
        return "default.jpg";
    }

    $file = ucfirst($file) . '.jpg';
    return $file;
}

function getImagePathForRecette($recette) {
    $baseDir = "Photos/";
    $fileName = getImageFileNameFromTitle($recette['titre']);
    $path = $baseDir . $fileName;

    if (!file_exists($path)) {
        $path = $baseDir . "default.jpg";
    }
    return $path;
}

function isFavorite($idRecette) {
    return isset($_SESSION['favoris']) && in_array($idRecette, $_SESSION['favoris']);
}


// FONCTIONS DE VALIDATION

function validerLogin($login) {
    return preg_match('/^[a-zA-Z0-9]+$/', $login);
}

function validerNomPrenom($texte) {
    // lettres (accentuées ou non), espaces, tirets et apostrophes
    // tiret et apostrophe doivent être encadrés par des lettres
    return preg_match('/^[a-zA-ZÀ-ÿ]+([- \'][a-zA-ZÀ-ÿ]+)*$/', $texte);
}

function validerDateNaissance($date) {
    // format de la date
    $dateObj = DateTime::createFromFormat('Y-m-d', $date);
    if (!$dateObj) {
        return false;
    }

    // âge doit être valide
    $aujourdhui = new DateTime();
    $age = $aujourdhui->diff($dateObj)->y;

    // vérifier que la personne a au moins 18 ans
    return $age >= 18;
}