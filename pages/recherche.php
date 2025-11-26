<?php
// pages/recherche.php

require_once "Donnees.inc.php";
include_once "fonctions.php";
include "favoris.php";

global $Recettes, $Hierarchie;

if (isset($_GET['recette']) && ctype_digit($_GET['recette'])) {
    $recetteDetailId = (int) $_GET['recette'];
} else {
    $recetteDetailId = null;
}


function recetteContientAliment($recette, $cible, $hierarchie, $memo = array()) {
    foreach ($recette['index'] as $ing) {
        if (alimentCompatible($ing, $cible, $hierarchie, $memo)) {
            return true;
        }
    }
    return false;
}

/**
 * Retourne true si $ing == $cible ou si $cible est dans la chaîne
 * des super-catégories de $ing.
 */
function alimentCompatible($ing, $cible, $hierarchie, &$memo) {
    $key = $ing . '|' . $cible;
    if (isset($memo[$key])) {
        return $memo[$key];
    }

    if ($ing === $cible) {
        return $memo[$key] = true;
    }

    if (!isset($hierarchie[$ing]['super-categorie'])) {
        return $memo[$key] = false;
    }

    foreach ($hierarchie[$ing]['super-categorie'] as $parent) {
        if (alimentCompatible($parent, $cible, $hierarchie, $memo)) {
            return $memo[$key] = true;
        }
    }

    return $memo[$key] = false;
}


/**
 * ça remplit 3 tableaux :
 * -$souhaites : aliments reconnus recherché
 * -$nonSouhaites : aliments reconnus qu'on exclut
 * -$nonReconnu : morceaux de requête pas reconnus
 * Retourne un message d'erreur de syntaxe si mauvais nombre de guillemets
 */
function analyserRequete($requete, $hierarchie, &$souhaites, &$nonSouhaites, &$nonReconnu) {
    $souhaites = array();
    $nonSouhaites = array();
    $nonReconnu = array();

    // vérification des guillemets
    if (substr_count($requete, '"') % 2 !== 0) {
        return "Problème de syntaxe dans votre requête : nombre impair de double-quotes";
    }

    // découpage : soit "truc avec espaces", soit mots séparés par des espaces
    $tokens = array();
    preg_match_all('/"[^"]*"|[^\s]+/', $requete, $matches);
    if (!empty($matches[0])) {
        $tokens = $matches[0];
    }

    // ensemble des aliments de la hiérarchie
    $alimentsConnus = array_keys($hierarchie);

    foreach ($tokens as $token) {

        $token = trim($token);
        if ($token === '') continue;

        // signe (+ ou -)
        $signe = '+';
        if ($token[0] === '+' || $token[0] === '-') {
            $signe = $token[0];
            $token = substr($token, 1);
            $token = trim($token);
        }

        // suppression des guillemets
        if (strlen($token) >= 2 && $token[0] === '"' && $token[strlen($token) - 1] === '"') {
            $token = substr($token, 1, -1);
        }

        if ($token === '') continue;

        // reconnaissance stricte dans la hiérarchie (respect de la casse)
        if (in_array($token, $alimentsConnus, true)) {
            if ($signe === '-') {
                $nonSouhaites[] = $token;
            } else {
                $souhaites[] = $token;
            }
        } else {
            $nonReconnu[] = $token;
        }
    }

    return null; // pas d'erreur de syntaxe
}

/**
 * Calcule le score de satisfaction d'une recette
 */
function calculerScoreRecette($recette, $souhaites, $nonSouhaites, $hierarchie) {
    $memo = array();

    $criteres = array_merge($souhaites, $nonSouhaites);
    $nbCriteres = count($criteres);
    if ($nbCriteres === 0) {
        return array(0, false);
    }

    $points = 0;

    // aliments souhaités : la recette doit les contenir
    foreach ($souhaites as $alim) {
        if (recetteContientAliment($recette, $alim, $hierarchie, $memo)) {
            $points++;
        }
    }

    // aliments non souhaités : la recette doit NE PAS les contenir
    foreach ($nonSouhaites as $alim) {
        if (!recetteContientAliment($recette, $alim, $hierarchie, $memo)) {
            $points++;
        }
    }

    if ($points === 0) {
        return array(0, false);
    }

    $score = round($points * 100 / $nbCriteres);
    $entier = ($points === $nbCriteres);

    return array($score, $entier);
}


$requete = isset($_GET['requete']) ? $_GET['requete'] : '';

?>

<section class="recherche-page">

    <?php if ($recetteDetailId !== null && isset($Recettes[$recetteDetailId])) {

        // AFFICHAGE DETAILLE
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
                   href="index.php?page=recherche&amp;requete=<?= urlencode($requete) ?>&amp;recette=<?= $recetteDetailId ?>&amp;toggle=<?= $recetteDetailId ?>">
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

            <p><a href="index.php?page=recherche&amp;requete=<?= urlencode($requete) ?>">Retour aux résultats de recherche</a></p>
        </article>

    <?php } else { ?>

        <!-- AFFICHAGE SYNTHETIQUE -->
        <h2>Recherche de recettes</h2>

        <p>Requête saisie :
            <strong><?= htmlspecialchars($requete) ?></strong>
        </p>

        <?php
        if ($requete === '') {
            ?>
            <p>Saisissez une requête dans la barre de recherche en haut pour trouver des cocktails.</p>
            <?php
        } else {

            // analyse
            $souhaites = $nonSouhaites = $nonReconnu = array();
            $erreurSyntaxe = analyserRequete($requete, $Hierarchie, $souhaites, $nonSouhaites, $nonReconnu);
            ?>

            <h3>Analyse de la requête</h3>

            <?php if ($erreurSyntaxe !== null) { ?>
                <p><?= htmlspecialchars($erreurSyntaxe) ?></p>
            <?php
            } else {

                // affichage des aliments reconnus / non reconnus
                if (!empty($souhaites)) { ?>
                    <p><strong>Liste des aliments souhaités :</strong>
                        <?= htmlspecialchars(implode(', ', $souhaites)) ?>
                    </p>
                <?php }

                if (!empty($nonSouhaites)) { ?>
                    <p><strong>Liste des aliments non souhaités :</strong>
                        <?= htmlspecialchars(implode(', ', $nonSouhaites)) ?>
                    </p>
                <?php }

                if (!empty($nonReconnu)) { ?>
                    <p><strong>Éléments non reconnus dans la requête :</strong>
                        <?= htmlspecialchars(implode(', ', $nonReconnu)) ?>
                    </p>
                <?php }

                // Cas où aucun aliment n'est reconnu
                if (empty($souhaites) && empty($nonSouhaites)) { ?>
                    <p><strong>Problème dans votre requête : recherche impossible</strong></p>
                <?php
                } else {
                    // Recherche possible
                    $criteres = array_merge($souhaites, $nonSouhaites);
                    $nbCriteres = count($criteres);

                    $resultats = array();
                    $nbEntierement = 0;
                    $nbPartiellement = 0;

                    foreach ($Recettes as $idRecette => $recette) {
                        list($score, $entier) = calculerScoreRecette($recette, $souhaites, $nonSouhaites, $Hierarchie);

                        if ($score > 0) {
                            $resultats[] = array(
                                'id' => $idRecette,
                                'recette' => $recette,
                                'score' => $score,
                                'entier' => $entier
                            );
                            if ($entier) {
                                $nbEntierement++;
                            } else {
                                $nbPartiellement++;
                            }
                        }
                    }

                    // Tri décroissant sur le score, puis par titre
                    usort($resultats, function ($a, $b) {
                        if ($a['score'] === $b['score']) {
                            return strcmp($a['recette']['titre'], $b['recette']['titre']);
                        }
                        return $b['score'] - $a['score'];
                    });

                    // affichage des infos
                    ?>
                    <h3>Résultat de la recherche</h3>

                    <?php if ($nbCriteres === 1) { ?>
                        <p>Nombre de recettes qui satisfont la recherche : <?= $nbEntierement ?></p>
                    <?php } else { ?>
                        <p>
                            Nombre de recettes qui satisfont entièrement la recherche : <?= $nbEntierement ?><br>
                            Nombre de recettes qui satisfont partiellement la recherche : <?= $nbPartiellement ?>
                        </p>
                    <?php } ?>

                    <?php if (empty($resultats)) { ?>
                        <p>Aucune recette ne correspond aux critères reconnus.</p>
                    <?php } else { ?>

                        <div class="card-list">
                            <?php foreach ($resultats as $r) {
                                $id = $r['id'];
                                $recette = $r['recette'];
                                $score = $r['score'];
                                $entier = $r['entier'];

                                $imagePath = getImagePathForRecette($recette);
                                $estFav = isFavorite($id);
                                ?>
                                <article class="card-recette">
                                    <header class="card-header">
                                        <h3>
                                            <a href="index.php?page=recherche&amp;requete=<?= urlencode($requete) ?>&amp;recette=<?= $id ?>">
                                                <?= htmlspecialchars($recette['titre']) ?>
                                            </a>
                                        </h3>
                                        <a
                                            class="heart <?= $estFav ? 'heart-full' : 'heart-empty' ?>"
                                            href="index.php?page=recherche&amp;requete=<?= urlencode($requete) ?>&amp;toggle=<?= $id ?>"
                                            title="<?= $estFav ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>"
                                        >
                                            <?= $estFav ? "&#10084;" : "&#9825;" ?>
                                        </a>
                                    </header>

                                    <p class="score-satisfaction">
                                        Score de satisfaction : <?= $score ?> %
                                        <?= $entier ? "(recette qui satisfait entièrement la requête)" : "" ?>
                                    </p>

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

                    <?php } // fin affichage résultats ?>
                <?php } // fin "recherche possible"
            } // fin else pas d'erreur syntaxe
        } // fin else requete non vide
        ?>

    <?php } ?>
</section>