	<?php if($user['role'] === 'adminstrator' || $user['role'] === 'manager') { ?>
	Willkommen im internen Bereich!<br><br>
	<table id="sort">
		<thead>
			<tr>
				<th>ID</th>
				<th>Vorname</th>
				<th>Nachname</th>
				<th>E-Mail</th>
				<th>Biografie</th>
				<th>Geburtsdatum</th>
				<th>Geschlecht</th>
				<th>Benutzername</th>
				<th>Rolle</th>
				<th>Registriert am</th>
				<th>Profil bearbeitet am</th>
			</tr>
		</thead>
		<tbody>
			<?php 
				$statement = $pdo->prepare("SELECT * FROM users ORDER BY id");
				$result = $statement->execute();
				$count = 1;
				while($row = $statement->fetch()) {
			?>
			<tr>
				<td scope="row"><?php echo $count++ ?></td>
				<td><?php echo $row['vorname'] ?></td>
				<td><?php echo $row['nachname'] ?></td>
				<td><a href="mailto:'.<?php echo $row['email'] ?>"><?php echo $row['email'] ?></a></td>
				<td><?php echo $row['biography'] ?></td>
				<td><?php echo $row['birthday'] ?></td>
				<td><?php echo $row['gender'] ?></td>
				<td><a href="profile@<?php echo $row['username'] ?>"><?php echo $row['username'] ?></a></td>
				<td><?php echo $row['role'] ?></td>
				<td><?php echo $row['created_at'] ?></td>
				<td><?php echo $row['updated_at'] ?></td>
			</tr>
		<?php } ?>
		</tbody>
	</table>
	<?php } ?>
