	<strong><?php echo $your_version; ?></strong><br>
	<?php echo $search_update; ?><br>
	<?php echo $new_update; ?><br>
	<?php echo $download_update; ?><br>
	<?php if ($new_update_found) { ?>
	Neues Update verfügbar.
	<form action="" method="post">
		<button name="doUpdate">Jetzt installieren</button>
	</form><br>
	<?php
		} else { 
			echo $no_update; 
		}
	?>
