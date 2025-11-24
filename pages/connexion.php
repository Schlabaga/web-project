<?php
require_once "Donnees.inc.php";

// Fichier où sont stockés les utilisateurs
$fichierUtilisateurs = "data/utilisateurs.json";

$erreur = "";

// Si le formulaire est soumis
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = isset($_POST['login']) ? trim($_POST['login']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Vérifier que les champs sont remplis
    if (empty($login) || empty($password)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {

        // Charger les utilisateurs
        if (file_exists($fichierUtilisateurs)) {
            $contenu = file_get_contents($fichierUtilisateurs);
            $utilisateurs = json_decode($contenu, true);

            // Vérifier si l'utilisateur existe
            if (isset($utilisateurs[$login])) {
                $utilisateur = $utilisateurs[$login];

                // Vérifier le mot de passe
                if (password_verify($password, $utilisateur['password'])) {

                    // on fusionne les favoris
                    $favorisSession = isset($_SESSION['favoris']) ? $_SESSION['favoris'] : array();
                    $favorisUtilisateur = isset($utilisateur['favoris']) && is_array($utilisateur['favoris'])
                                          ? $utilisateur['favoris']
                                          : array();

                    // fusionner et éliminer les doublons
                    $favorisFusionnes = array_unique(array_merge($favorisSession, $favorisUtilisateur));
                    $_SESSION['favoris'] = array_values($favorisFusionnes);

                    // connexion réussie
                    $_SESSION['login'] = $login;

                    // SAUVEGARDER les favoris fusionnés dans le JSON
                    $utilisateurs[$login]['favoris'] = $_SESSION['favoris'];
                    $json = json_encode($utilisateurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                    file_put_contents($fichierUtilisateurs, $json);

                    // Rediriger vers l'accueil
                    header("Location: index.php?page=navigation");
                    exit();

                } else {
                    $erreur = "Login ou mot de passe incorrect.";
                }

            } else {
                $erreur = "Login ou mot de passe incorrect.";
            }

        } else {
            $erreur = "Aucun utilisateur enregistré. Veuillez vous inscrire.";
        }
    }
}

// Si erreur, afficher un message et rediriger
if (!empty($erreur)) {
    ?>
    <section class="connexion-erreur">
        <h2>Erreur de connexion</h2>
        <div class="message-erreur">
            <?= htmlspecialchars($erreur) ?>
        </div>
        <p><a href="index.php?page=navigation">Retour à l'accueil</a></p>
    </section>
    <?php
} else {
    // Si on arrive ici sans POST, rediriger vers l'accueil
    header("Location: index.php?page=navigation");
    exit();
}
?>