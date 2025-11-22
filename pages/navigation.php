<?php
// pages/navigation.php

// On a déjà session_start() dans index.php
require_once "Donnees.inc.php";

/* ---------- Gestion des favoris (session) ---------- */

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

/* ---------- Paramètres de navigation ---------- */

// Aliment courant (racine = 'Aliment')
if (isset($_GET['aliment'])) {
    $alimentCourant = $_GET['aliment'];
} else {
    $alimentCourant = 'Aliment';
}

// Recette en vue détaillée (id)
if (isset($_GET['recette']) && ctype_digit($_GET['recette'])) {
    $recetteDetailId = (int) $_GET['recette'];
} else {
    $recetteDetailId = null;
}

/* ---------- Fonctions utilitaires ---------- */

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

/* ---------- Préparation des données ---------- */

// Fil d’Ariane
$chemin = buildChemin($Hierarchie, $alimentCourant);

// Sous-catégories
$sousCategories = getSousCategories($Hierarchie, $alimentCourant);

// Ensemble des aliments (aliment courant + descendants)
$alimentsRecherches = array();
collectAlimentsDescendants($Hierarchie, $alimentCourant, $alimentsRecherches);

// Recettes qui utilisent cet aliment ou ses descendants
$recettesCorrespondantes = array();

foreach ($Recettes as $id => $recette) {
    foreach ($recette['index'] as $alimentRecette) {
        if (in_array($alimentRecette, $alimentsRecherches)) {
            $recettesCorrespondantes[$id] = $recette;
            break;
        }
    }
}
?>

<div class="nav-wrapper">

    <!-- COLONNE GAUCHE : ALIMENT COURANT -->
    <section class="nav-left">
        <h2>Aliment courant</h2>

        <p class="fil-ariane">
            <?php
            $nb = count($chemin);
            foreach ($chemin as $i => $alim) {
                if ($i > 0) {
                    echo " / ";
                }
                ?>
                <a href="index.php?page=navigation&amp;aliment=<?= urlencode($alim) ?>">
                    <?= htmlspecialchars($alim) ?>
                </a>
                <?php
            }
            ?>
        </p>

        <h3>Sous-catégories :</h3>

        <?php if (!empty($sousCategories)) { ?>
            <ul class="liste-sous-categories">
                <?php foreach ($sousCategories as $sous) { ?>
                    <li>
                        <a href="index.php?page=navigation&amp;aliment=<?= urlencode($sous) ?>">
                            <?= htmlspecialchars($sous) ?>
                        </a>
                    </li>
                <?php } ?>
            </ul>
        <?php } else { ?>
            <p>(Aucune sous-catégorie)</p>
        <?php } ?>
    </section>

    <!-- COLONNE DROITE : LISTE DES COCKTAILS -->
    <section class="nav-right">

        <?php if ($recetteDetailId !== null && isset($Recettes[$recetteDetailId])) {

            // ======= AFFICHAGE DETAILLE =======
            $recette = $Recettes[$recetteDetailId];
            $imagePath = getImagePathForRecette($recette);
            $ingredientsDetail = explode('|', $recette['ingredients']);
            $estFav = isFavorite($recetteDetailId);
            ?>

            <h2>Recette détaillée</h2>

            <article class="card-detail">
                <header class="card-header">
                    <h3><?= htmlspecialchars($recette['titre']) ?></h3>
                    <a class="heart <?= $estFav ? 'heart-full' : 'heart-empty' ?>"
                       href="index.php?page=navigation&amp;aliment=<?= urlencode($alimentCourant) ?>&amp;recette=<?= $recetteDetailId ?>&amp;toggle=<?= $recetteDetailId ?>">
                        <?= $estFav ? "&#10084;" : "&#9825;" ?>
                    </a>
                </header>

                <div class="card-detail-body">
                    <div class="card-img">
                        <img src="<?= htmlspecialchars($imagePath) ?>"
                             alt="Photo du cocktail <?= htmlspecialchars($recette['titre']) ?>">
                    </div>

                    <div class="card-text">
                        <h4>Ingrédients</h4>
                        <ul>
                            <?php foreach ($ingredientsDetail as $ligneIng) { ?>
                                <li><?= htmlspecialchars(trim($ligneIng)) ?></li>
                            <?php } ?>
                        </ul>

                        <h4>Préparation</h4>
                        <p><?= nl2br(htmlspecialchars($recette['preparation'])) ?></p>
                    </div>
                </div>
            </article>

        <?php } else { ?>

            <!-- ======= AFFICHAGE SYNTHETIQUE ======= -->
            <h2>Liste des cocktails</h2>

            <?php if (empty($recettesCorrespondantes)) { ?>
                <p>Aucune recette pour cet aliment.</p>
            <?php } else { ?>

                <div class="card-list">
                    <?php foreach ($recettesCorrespondantes as $idRecette => $recette) {
                        $imagePath = getImagePathForRecette($recette);
                        $estFav = isFavorite($idRecette);
                        ?>
                        <article class="card-recette">
                            <header class="card-header">
                                <h3>
                                    <a href="index.php?page=navigation&amp;aliment=<?= urlencode($alimentCourant) ?>&amp;recette=<?= $idRecette ?>">
                                        <?= htmlspecialchars($recette['titre']) ?>
                                    </a>
                                </h3>
                                <a class="heart <?= $estFav ? 'heart-full' : 'heart-empty' ?>"
                                   href="index.php?page=navigation&amp;aliment=<?= urlencode($alimentCourant) ?>&amp;toggle=<?= $idRecette ?>">
                                    <?= $estFav ? "&#10084;" : "&#9825;" ?>
                                </a>
                            </header>

                            <div class="card-body">
                                <div class="card-img">
                                    <img src="<?= htmlspecialchars($imagePath) ?>"
                                         alt="Photo du cocktail <?= htmlspecialchars($recette['titre']) ?>">
                                </div>
                                <ul class="card-index">
                                    <?php foreach ($recette['index'] as $ing) { ?>
                                        <li><?= htmlspecialchars($ing) ?></li>
                                    <?php } ?>
                                </ul>
                            </div>
                        </article>
                    <?php } ?>
                </div>

            <?php } ?>

        <?php } ?>

    </section>

</div>
