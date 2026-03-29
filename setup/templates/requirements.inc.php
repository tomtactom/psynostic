	<article>
		<section>
			<h3>Server Vorraussetzungen</h3>
			<?php if (!$goToNextStep) { ?>
				<p>
					Kontaktiere deinen Webserveradminstrator (Hosting-Service) um die nötige PHP Version zu installieren.
				</p>
			<?php } ?>
			<h4>PHP Version</h4>
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Version</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td>Benötigt</td>
						<td><?php echo $phpVersion; ?></td>
						<td></td>
					</tr>
					<tr>
						<td>Du hast</td>
						<td><?php echo $currentPhpVersion; ?></td>
						<td><?php if ($phpVersionOk) { ?> <img src="img/icons/accept.png"> OK <?php } else { ?> <img src="img/icons/cancel.png"> Du hast eine zu alte PHP Version.<?php } ?></td>
					</tr>
				</tbody>
			</table>
			<hr>
			<h4>PHP-Erweiterungen</h4>
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($showExtensions as $extension => $status): ?>
					<tr>
						<td><?php echo $extension; ?></td>
						<td><?php if ($status) { ?> <img src="img/icons/accept.png"> Ok <?php } else { ?> <img src="img/icons/cancel.png"> Nicht installiert!<?php } ?> </td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<hr>
			<a href="index.php">Abbrechen</a>
			<?php if ($goToNextStep) { ?>
				<form method="post">
					<input type="hidden" name="nextStep" value="filePermissions">
					<button type="submit">Weiter</button>
				</form>
			<?php } else { ?>
				<form method="post">
					<input type="hidden" name="nextStep" value="requirements">
					<button type="submit">Nochmal versuchen</button>
				</form>
			<?php } ?>
		</section>
	</article>
