<br>
<div align="center">
<blockquote>
    <form action="" method="post">
        <label for="inputBiography"><p>Biografie</p>
            <textarea id="inputBiography" cols="30" rows="15" placeholder="Deine Biografie" name="biography" maxlength="255"><?php echo $user['biography']; ?></textarea>
        </label>
        <button type="submit" name="save_biography">Speichern</button>
    </form>
</blockquote>
<br>
<blockquote>
    <form action="" method="post">
        <label for="inputUsername"><p>Benutzername</p>
            <input type="text" id="inputUsername" placeholder="Dein Benutzername" name="username" value="<?php echo $user['username']; ?>" minlength="3" maxlength="32" pattern="[A-Za-z0-9/_-]+" required>
        </label>
        <button type="submit" name="save_username">Speichern</button>
    </form>
</blockquote>
<br>
<blockquote>
    <h3>Zum Änderen deiner E-Mail-Adresse gib bitte dein aktuelles Passwort sowie die neue E-Mail-Adresse ein</h3>
    <form action="" method="post">
        <label for="inputPasswort"><p>Passwort</p>
            <input placeholder="Passwort" id="inputPasswort" name="passwort" type="password" minlength="8" maxlength="255" required>
        </label>
        <label for="inputEmail"><p>E-Mail</p>
            <input placeholder="E-Mail" id="inputEmail" name="email" type="email" value="<?php echo htmlentities($user['email']); ?>" minlength="6" maxlength="255" required>
        </label>
        <label for="inputEmail2"><p>E-Mail (Neu)</p>
            <input placeholder="E-Mail wiederholen" id="inputEmail2" name="email2" type="email" minlength="6" maxlength="255" required>
        </label>
        <button type="submit" name="save_email">Speichern</button>
    </form>
</blockquote>
<br>
<blockquote>
    <h3>Zum Änderen deines Passworts gib bitte dein aktuelles Passwort sowie das neue Passwort ein</h3>
    <form action="" method="post">
        <label for="inputPasswort"><p>Altes Passwort</p>
            <input placeholder="Altes Passwort" id="inputPasswort" name="passwortAlt" type="password" minlength="8" maxlength="255" required>
        </label>
        <label for="inputPasswortNeu"><p>Neues Passwort</p>
            <input placeholder="Neues Passwort" id="inputPasswortNeu" name="passwortNeu" type="password" minlength="8" maxlength="255" required>
        </label>
        <label for="inputPasswortNeu2"><p>Neues Passwort (wiederholen)</p>
            <input placeholder="Neues Passwort wiederholen" id="inputPasswortNeu2" name="passwortNeu2" type="password" minlength="8" maxlength="255" required>
        </label>
        <button type="submit" name="save_passwort">Speichern</button>
    </form>
</blockquote>
<br>
<blockquote>
    <form action="" method="post" enctype="multipart/form-data">
        <label for="inputProfilpicture"><p>Profilbild</p>
            <input type="file" id="inputProfilpicture" name="profilpicture" accept=".jpg" required>
        </label>
        <button type="submit" name="save_profilpicture">Speichern</button>
    </form>
    <br><br>
    <form action="" method="post">
        <button type="submit" name="save_profilpicturedelete">Profilbild löschen</button>
    </form>
</blockquote>
<br>
<blockquote>
    <form action="" method="post">
        <label for="inputDeleteok">Ich habe verstanden dass ich damit meinen Account unwiederruflich lösche:
            <input type="checkbox" name="deleteok" id="inputDeleteok" required>
        </label>
        <br>
        <label for="inputPassword"><p>Passwort</p>
            <input placeholder="Passwort zur überprüfung" id="inputPassword" name="password" type="password" minlength="8" maxlength="255" required>
        </label>
        <button type="submit" name="save_accountdelete">Account löschen</button>
    </form>
</blockquote>
</div>
<br>
