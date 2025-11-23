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

                    // Connexion réussie
                    $_SESSION['login'] = $login;

                    // Charger les favoris de l'utilisateur
                    if (isset($utilisateur['favoris']) && is_array($utilisateur['favoris'])) {
                        $_SESSION['favoris'] = $utilisateur['favoris'];
                    } else {
                        $_SESSION['favoris'] = array();
                    }

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