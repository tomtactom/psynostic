	<article>
		<section>
			<h3>Dateiberechtigungen</h3>
			<?php if (!$goToNextStep) { ?>
				<p>
					Das Installationsprogramm verfügt nicht über ausreichende Dateiberechtigungen auf diesem Server! Überprüfe die chmod-Berechtigungen, um das Problem zu beheben.
				</p>
			<?php } ?>
			<table>
				<thead>
					<tr>
						<th>Name</th>
						<th>Echter Pfad</th>
						<th>Benötigt</th>
						<th>Status</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($showPermissions as $filename => $permissions): ?>
					<tr>
						<td><?php echo $filename; ?></td>
						<td><?php echo $permissions['realpath']; ?></td>
						<td><?php echo $permissions['showRequired']; ?></td>
						<td><?php if ($permissions['error'] == "") { ?><img src="img/icons/accept.png"> Ok <?php } else { ?><img src="img/icons/cancel.png"><?php echo $permissions['error']; ?> <?php } ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<hr>
			<a href="index.php">Abbrechen</a>
			<?php if ($goToNextStep) { ?>
				<form method="post">
					<input type="hidden" name="nextStep" value="database">
					<button type="submit">Weiter</button>
				</form>
			<?php } else { ?>
				<form method="post">
					<input type="hidden" name="nextStep" value="filePermissions">
					<button type="submit">Nochmal versuchen</button>
				</form>
			<?php } ?>
		</section>
	</article>
