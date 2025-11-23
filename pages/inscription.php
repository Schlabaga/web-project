<?php
require_once "Donnees.inc.php";

// Fichier où sont stockés les utilisateurs
$fichierUtilisateurs = "data/utilisateurs.json";

// Initialiser les variables pour conserver les valeurs en cas d'erreur
$login = isset($_POST['login']) ? trim($_POST['login']) : '';
$nom = isset($_POST['nom']) ? trim($_POST['nom']) : '';
$prenom = isset($_POST['prenom']) ? trim($_POST['prenom']) : '';
$sexe = isset($_POST['sexe']) ? $_POST['sexe'] : '';
$date_naissance = isset($_POST['date_naissance']) ? $_POST['date_naissance'] : '';

$erreurs = array();

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

// si formulaire fourni
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password_confirm']) ? $_POST['password_confirm'] : '';

    if (empty($login)) {
        $erreurs[] = "Le login est obligatoire.";
    } elseif (!validerLogin($login)) {
        $erreurs[] = "Le login ne peut contenir que des lettres non accentuées et des chiffres.";
    }

    if (empty($password)) {
        $erreurs[] = "Le mot de passe est obligatoire.";
    }

    if (empty($password_confirm)) {
        $erreurs[] = "La confirmation du mot de passe est obligatoire.";
    }

    if (!empty($password) && !empty($password_confirm) && $password !== $password_confirm) {
        $erreurs[] = "Les mots de passe ne correspondent pas.";
    }

    if (!empty($nom) && !validerNomPrenom($nom)) {
        $erreurs[] = "Le nom contient des caractères non autorisés.";
    }

    if (!empty($prenom) && !validerNomPrenom($prenom)) {
        $erreurs[] = "Le prénom contient des caractères non autorisés.";
    }

    if (!empty($date_naissance) && !validerDateNaissance($date_naissance)) {
        $erreurs[] = "Vous devez avoir au moins 18 ans pour vous inscrire.";
    }

    if (empty($erreurs)) {
        $utilisateurs = array();

        if (file_exists($fichierUtilisateurs)) {
            $contenu = file_get_contents($fichierUtilisateurs);
            $utilisateurs = json_decode($contenu, true);

            if ($utilisateurs === null) {
                $utilisateurs = array();
            }
        }

        if (isset($utilisateurs[$login])) {
            $erreurs[] = "Ce login est déjà utilisé. Veuillez en choisir un autre.";
        }
    }

    // Si pas d'erreurs, créer l'utilisateur
    if (empty($erreurs)) {

        // créer le dossier data s'il n'existe pas
        if (!is_dir('data')) {
            mkdir('data', 0755, true);
            // ca c'est les permissions
        }

        // charger les utilisateurs existants
        $utilisateurs = array();
        if (file_exists($fichierUtilisateurs)) {
            $contenu = file_get_contents($fichierUtilisateurs);
            $utilisateurs = json_decode($contenu, true);
            if ($utilisateurs === null) {
                $utilisateurs = array();
            }
        }

        // créer le nouvel utilisateur avec les infos en json
        $utilisateurs[$login] = array(
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'nom' => $nom,
            'prenom' => $prenom,
            'sexe' => $sexe,
            'date_naissance' => $date_naissance,
            'favoris' => array()
        );


        $json = json_encode($utilisateurs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        file_put_contents($fichierUtilisateurs, $json);

        // Connecter automatiquement l'utilisateur
        $_SESSION['login'] = $login;
        $_SESSION['favoris'] = array();


        // TODO: faut changer la façon de sauvegarder les favoris en fonction de connecté ou aps

        // Rediriger vers l'accueil
        header("Location: index.php?page=navigation");
        exit();
    }
}
?>

<section class="inscription-page">
    <h2>Inscription</h2>

    <?php if (!empty($erreurs)) { ?>
        <div class="erreurs">
            <h3>Erreurs détectées :</h3>
            <ul>
                <?php foreach ($erreurs as $erreur) { ?>
                    <li><?= htmlspecialchars($erreur) ?></li>
                <?php } ?>
            </ul>
        </div>
    <?php } ?>

    <form method="POST" action="index.php?page=inscription" class="form-inscription">

        <div class="form-group">
            <label for="login">Login <span class="obligatoire">*</span></label>
            <input type="text"
                   id="login"
                   name="login"
                   value="<?= htmlspecialchars($login) ?>"
                   required>
            <small>Lettres non accentuées et chiffres uniquement</small>
        </div>

        <div class="form-group">
            <label for="password">Mot de passe <span class="obligatoire">*</span></label>
            <input type="password"
                   id="password"
                   name="password"
                   required>
        </div>

        <div class="form-group">
            <label for="password_confirm">Confirmer le mot de passe <span class="obligatoire">*</span></label>
            <input type="password"
                   id="password_confirm"
                   name="password_confirm"
                   required>
        </div>

        <div class="form-group">
            <label for="nom">Nom</label>
            <input type="text"
                   id="nom"
                   name="nom"
                   value="<?= htmlspecialchars($nom) ?>">
            <small>Lettres, tirets, apostrophes et espaces uniquement</small>
        </div>

        <div class="form-group">
            <label for="prenom">Prénom</label>
            <input type="text"
                   id="prenom"
                   name="prenom"
                   value="<?= htmlspecialchars($prenom) ?>">
            <small>Lettres, tirets, apostrophes et espaces uniquement</small>
        </div>

        <div class="form-group">
            <label>Sexe</label>
            <div class="radio-group">
                <label>
                    <input type="radio"
                           name="sexe"
                           value="homme"
                           <?= $sexe === 'homme' ? 'checked' : '' ?>>
                    Homme
                </label>
                <label>
                    <input type="radio"
                           name="sexe"
                           value="femme"
                           <?= $sexe === 'femme' ? 'checked' : '' ?>>
                    Femme
                </label>
            </div>
        </div>

        <div class="form-group">
            <label for="date_naissance">Date de naissance</label>
            <input type="date"
                   id="date_naissance"
                   name="date_naissance"
                   value="<?= htmlspecialchars($date_naissance) ?>">
            <small>Vous devez avoir au moins 18 ans</small>
        </div>

        <p class="note-obligatoire">
            <span class="obligatoire">*</span> Champs obligatoires
        </p>

        <div class="form-actions">
            <button type="submit" class="btn-primary">S'inscrire</button>
            <a href="index.php?page=navigation" class="btn-secondary">Annuler</a>
        </div>

    </form>

    <p class="lien-connexion">
        Vous avez déjà un compte ?
        <a href="index.php?page=navigation">Se connecter depuis le menu</a>
    </p>

</section>