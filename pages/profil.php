<?php
require_once "fonctions.php"; // Pour les fonctions partagées de validation

// Vérifier si l'utilisateur est connecté
if(!isset($_SESSION['login'])) {
    header("Location: index.php?page=connexion");
    exit();
}

$login = $_SESSION['login'];
$fichierUtilisateurs = "data/utilisateurs.json";
$message = "";
$classMessage = ""; // Pour gérer le style en cas d'erreur

// Chargement des utilisateurs
$utilisateurs = array();
if (file_exists($fichierUtilisateurs)) {
    $json = file_get_contents($fichierUtilisateurs);
    $utilisateurs = json_decode($json, true);
}

// Récupération des données de l'utilisateur
$userData = isset($utilisateurs[$login]) ? $utilisateurs[$login] : array();
$nom = isset($userData['nom']) ? $userData['nom'] : '';
$prenom = isset($userData['prenom']) ? $userData['prenom'] : '';
$sexe = isset($userData['sexe']) ? $userData['sexe'] : '';
$date_naissance = isset($userData['date_naissance']) ? $userData['date_naissance'] : '';


// TRAITEMENT DU FORMULAIRE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $nouveauNom = trim($_POST['nom']);
    $nouveauPrenom = trim($_POST['prenom']);
    $nouveauSexe = isset($_POST['sexe']) ? $_POST['sexe'] : '';
    $nouvelleDateNaissance = $_POST['date_naissance'];

    $erreurs = array();

    // Utilisation des fonctions de validation (fonctions.php)
    if (!empty($nouveauNom) && !validerNomPrenom($nouveauNom)) {
        $erreurs[] = "Le nom contient des caractères invalides (lettres, tirets, espaces uniquement).";
    }

    if (!empty($nouveauPrenom) && !validerNomPrenom($nouveauPrenom)) {
        $erreurs[] = "Le prénom contient des caractères invalides.";
    }

    if (!empty($nouvelleDateNaissance) && !validerDateNaissance($nouvelleDateNaissance)) {
        $erreurs[] = "Date invalide ou vous devez avoir au moins 18 ans.";
    }

    // sauvegarde
    if (empty($erreurs)) {
        $utilisateurs[$login]['nom'] = $nouveauNom;
        $utilisateurs[$login]['prenom'] = $nouveauPrenom;
        $utilisateurs[$login]['sexe'] = $nouveauSexe;
        $utilisateurs[$login]['date_naissance'] = $nouvelleDateNaissance;

        if (file_put_contents($fichierUtilisateurs, json_encode($utilisateurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
            $message = "Profil mis à jour avec succès !";
            $classMessage = "success";

            // Mise a jour des variables pour l'affichage
            $nom = $nouveauNom;
            $prenom = $nouveauPrenom;
            $sexe = $nouveauSexe;
            $date_naissance = $nouvelleDateNaissance;
        } else {
            $message = "Erreur technique lors de l'enregistrement.";
            $classMessage = "error";
        }
    } else {
        $message = implode("<br>", $erreurs);
        $classMessage = "error";
    }
}
?>

<section class="profil-page">
    <h2>Mon Profil</h2>

    <div class="profil-container">
        <?php if (!empty($message)): ?>
            <div class="alert <?= $classMessage ?>">
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="post" action="index.php?page=profil" class="form-profil">

            <div class="form-group">
                <label for="login">Login (non modifiable)&nbsp;:</label>
                <input type="text" id="login" value="<?= htmlspecialchars($login) ?>" disabled class="input-disabled">
            </div>

            <div class="form-group">
                <label for="nom">Nom&nbsp;:</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($nom) ?>">
            </div>

            <div class="form-group">
                <label for="prenom">Prénom&nbsp;:</label>
                <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($prenom) ?>">
            </div>

            <div class="form-group">
                <label for="sexe">Sexe&nbsp;:</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="sexe" value="homme" <?= $sexe === 'homme' ? 'checked' : '' ?>>
                        Homme
                    </label>
                    <label>
                        <input type="radio" name="sexe" value="femme" <?= $sexe === 'femme' ? 'checked' : '' ?>>
                        Femme
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label for="date_naissance">Date de naissance&nbsp;:</label>
                <input type="date" id="date_naissance" name="date_naissance" value="<?= htmlspecialchars($date_naissance) ?>">
                <small>Format JJ/MM/AAAA (18 ans minimum)</small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary">Enregistrer les modifications</button>
            </div>
        </form>
    </div>
</section>
