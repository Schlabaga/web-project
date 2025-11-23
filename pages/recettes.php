<?php
require_once "Donnees.inc.php";
global $Recettes;

include("favoris.php");
include_once "fonctions.php";

// Get les favoris
$favorisListe = array();
if (isset($_SESSION['favoris']) && !empty($_SESSION['favoris'])) {
    $favorisListe = $_SESSION['favoris'];
}

// filtrer recettes
$recettesFavoris = array();
foreach ($Recettes as $idRecette => $recette) {
    if (in_array($idRecette, $favorisListe)) {
        $recettesFavoris[$idRecette] = $recette;
    }
}



?>

<section class="favoris-page">
    <h2>Mes recettes préférées</h2>

    <?php if (empty($recettesFavoris)) { ?>
        <p>Vous n'avez pas encore de recettes favorites.</p>
        <p><a href="index.php?page=recettes">Découvrir les recettes</a></p>
    <?php } else { ?>
        <div class="card-list">
            <?php foreach ($recettesFavoris as $idRecette => $recette) {
                $imagePath = getImagePathForRecette($recette);
                ?>
                <article class="card-recette">
                    <header class="card-header">
                        <h3>
                            <a href="index.php?page=recettes&amp;recette=<?= $idRecette ?>">
                                <?= htmlspecialchars($recette['titre']) ?>
                            </a>
                        </h3>
                        <a class="heart heart-full"
                           href="index.php?page=favoris&amp;toggle=<?= $idRecette ?>">
                            &#10084;
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
</section>
