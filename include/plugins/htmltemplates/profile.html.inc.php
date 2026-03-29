<div align="center">
    <div class="profile_background">
        <h1 style="font-weight:600; font-size: 3.5vw;"><?php echo strtoupper($row["username"]); ?></h1>
        <img src="<?php echo $avatar_all; ?>" class="avatar_single" alt="<?php echo $row["username"] . 's Profilbild' ?>"
        style="margin: 0;width: 200px;height: 200px">
        <br><br>
        <p>Benutzername</p><?php echo $row["username"]; ?>
        <br><br>
        <p>Vor-/Nachname</p> <?php echo $row["vorname"] . "&nbsp" . $row["nachname"]; ?>
        <?php echo(!empty($row["biography"]) ? "<br><br><p>Biografie</p>" . $row["biography"] : ""); ?>
        <br><br>
        <p>Geschlecht</p> <?php echo $gender_all; ?>
    </div>
</div>
<div class="profile_spacebox">
    <div class="profile_spacebox_properties"></div>
</div>
