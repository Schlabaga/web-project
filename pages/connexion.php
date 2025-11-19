<html>

    <form>
        <!-- Zone de connexion pour utilisateur connecté -->
        <div class="connected">
            <span class="username">Mamamia</span>
            <a href="profil.php" class="profile-btn">Profil</a>
            <button type="button" class="logout-btn">Se déconnecter</button>
        </div>

        <div class="not-connected">
            <label>Login</label>
            <input type="text" name="login" />

            <label>Mot de passe</label>
            <input type="password" name="password" />

            <button type="submit" class="login-btn">Connexion</button>
            <a href="inscription.php" class="register-btn">S’inscrire</a>
        </div>
    </form>



</html>