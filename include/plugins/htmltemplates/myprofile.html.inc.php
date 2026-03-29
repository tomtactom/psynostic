<div align="center">
    <h1 style="font-weight:600; font-size: 2.3em;"><?php echo $user["username"]; ?></h1>
	<div class="myprofile_box">
		<div class="myprofile_background">
		<div class="myprofile_content">
			<form action="" method="post" enctype="multipart/form-data">
				<label for="inputProfilpicture">
					<img src="<?php echo $avatar; ?>" class="avatar_single" alt="<?php echo $user["username"].'s Profilbild' ?>" style="border-radius: 150px;margin: auto;height: 150px;width: 150px;display: block;">
				</label>
				<input type="file" id="inputProfilpicture" style="display: none;" name="profilpicture" accept=".jpg" required>
				<button type="submit" name="save_profilpicture" style="display:block;">Profilbild hochladen</button>
			</form>
			<p style='float:left;margin-left:20px;'>@<?php echo $user["username"]; ?></p>
			<p class="myprofile_name"><?php echo $user["vorname"] . " " . $user["nachname"]; ?></p><br><br>
			<div class="myprofile_biography" >
				<p><?php echo $user["biography"]; ?></p>
			</div>
			<p style='float:left;margin-left:20px;'><?php echo $gender; ?></p>
			<p style='float:right;margin-right:20px;'>Geburtsdatum: <?php echo $user['birthday']; ?></p>
		</div>
		</div>
	</div>
</div>
