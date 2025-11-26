<?php
// pages/recherche.php

require_once "Donnees.inc.php";
include_once "fonctions.php";
include "favoris.php";   // pour gérer les cœurs

global $Recettes, $Hierarchie;

/* ========================= */
/*  Fonctions utilitaires    */
/* ========================= */

/**
 * Vérifie si un aliment $cible est présent dans la recette (en tenant compte
 * de la hiérarchie : un "Jus de fruits" est satisfait par "Jus de tomate", etc.)
 */
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
 * Analyse la chaîne de requête.
 * Remplit 3 tableaux :
 *  - $souhaites : aliments reconnus recherchés
 *  - $nonSouhaites : aliments reconnus à exclure
 *  - $nonReconnu : morceaux de requête non reconnus
 * Retourne un message d’erreur de syntaxe si nombre impair de guillemets.
 */
function analyserRequete($requete, $hierarchie, &$souhaites, &$nonSouhaites, &$nonReconnu) {
    $souhaites = array();
    $nonSouhaites = array();
    $nonReconnu = array();

    // Vérification des guillemets
    if (substr_count($requete, '"') % 2 !== 0) {
        return "Problème de syntaxe dans votre requête : nombre impair de double-quotes";
    }

    // Découpage : soit "truc avec espaces", soit mots séparés par des espaces
    $tokens = array();
    preg_match_all('/"[^"]*"|[^\s]+/', $requete, $matches);
    if (!empty($matches[0])) {
        $tokens = $matches[0];
    }

    // Ensemble des aliments de la hiérarchie
    $alimentsConnus = array_keys($hierarchie);

    foreach ($tokens as $token) {

        $token = trim($token);
        if ($token === '') continue;

        // Signe (+ ou -)
        $signe = '+';
        if ($token[0] === '+' || $token[0] === '-') {
            $signe = $token[0];
            $token = substr($token, 1);
            $token = trim($token);
        }

        // Suppression des guillemets
        if (strlen($token) >= 2 && $token[0] === '"' && $token[strlen($token) - 1] === '"') {
            $token = substr($token, 1, -1);
        }

        if ($token === '') continue;

        // Reconnaissance stricte dans la hiérarchie (respect de la casse)
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

    return null; // pas d’erreur de syntaxe
}

/**
 * Calcule le score de satisfaction d’une recette
 * par rapport aux aliments souhaités / non souhaités.
 * Retourne un tableau [score(0-100), satisfaitEntierement(bool)].
 */
function calculerScoreRecette($recette, $souhaites, $nonSouhaites, $hierarchie) {
    $memo = array();

    $criteres = array_merge($souhaites, $nonSouhaites);
    $nbCriteres = count($criteres);
    if ($nbCriteres === 0) {
        return array(0, false);
    }

    $points = 0;

    // Aliments souhaités : la recette doit les contenir
    foreach ($souhaites as $alim) {
        if (recetteContientAliment($recette, $alim, $hierarchie, $memo)) {
            $points++;
        }
    }

    // Aliments non souhaités : la recette doit NE PAS les contenir
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

/* ========================= */
/*   Traitement de requête   */
/* ========================= */

$requete = isset($_GET['requete']) ? $_GET['requete'] : '';

?>

<section class="recherche-page">
    <h2>Recherche de recettes</h2>

    <p>Requête saisie :
        <strong><?= htmlspecialchars($requete) ?></strong>
    </p>

    <?php
    if ($requete === '') {
        // aucune requête : message d’info simple
        ?>
        <p>Saisissez une requête dans la barre de recherche en haut pour trouver des cocktails.</p>
        <?php
    } else {

        // Analyse
        $souhaites = $nonSouhaites = $nonReconnu = array();
        $erreurSyntaxe = analyserRequete($requete, $Hierarchie, $souhaites, $nonSouhaites, $nonReconnu);
        ?>

        <h3>Analyse de la requête</h3>

        <?php if ($erreurSyntaxe !== null) { ?>
            <p><?= htmlspecialchars($erreurSyntaxe) ?></p>
        <?php
        } else {

            // Affichage des aliments reconnus / non reconnus
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

            // Cas où aucun aliment n’est reconnu
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

                // Affichage des infos de synthèse
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
                                    <h3><?= htmlspecialchars($recette['titre']) ?></h3>
                                    <a
                                        class="heart <?= $estFav ? 'heart-full' : 'heart-empty' ?>"
                                        href="index.php?page=recherche&amp;requete=<?= urlencode($requete) ?>&amp;toggle=<?= $id ?>"
                                        title="<?= $estFav ? 'Retirer des favoris' : 'Ajouter aux favoris' ?>"
                                    >
                                        <?= $estFav ? "&#10084;" : "&#9825;" ?>
                                    </a>
                                </header>

                                <p style="font-size: 12px; margin: 4px 0;">
                                    Score de satisfaction : <?= $score ?> %
                                    <?= $entier ? "(recette qui satisfait entièrement la requête)" : "" ?>
                                </p>

                                <div class="card-img">
                                    <img src="<?= htmlspecialchars($imagePath) ?>"
                                         alt="Photo du cocktail <?= htmlspecialchars($recette['titre']) ?>">
                                </div>

                                <ul class="card-index">
                                    <?php foreach ($recette['index'] as $ing) { ?>
                                        <li><?= htmlspecialchars($ing) ?></li>
                                    <?php } ?>
                                </ul>
                            </article>
                        <?php } ?>
                    </div>

                <?php } // fin affichage résultats ?>
            <?php } // fin "recherche possible"
        } // fin else pas d'erreur syntaxe
    } // fin else requete non vide
    ?>
</section>
