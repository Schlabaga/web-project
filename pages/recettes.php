<?php
require_once "Donnees.inc.php";

$favorisListe = array();

if($_SESSION["favoris"] and !empty($_SESSION["favoris"]) !== null) {
    $favorisListe = $_SESSION["favoris"];
}

foreach ($favorisListe as $favoris) {
    echo $favoris;

}
