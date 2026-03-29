<?php 
	$sql = "SELECT * FROM users";
	$result = $db->query($sql);
	if ($result->num_rows > 0) {
		echo '<table>';
		while($row = $result->fetch_assoc()) {
			$username = $row["username"];
			echo '
						<tr>
							<td><a href="profile@'.$username.'" title="Profil von '.$row["vorname"].'">'.$username.'</a><br></td>
						</tr>';
		}
		echo '</table>';
	}
	else {
		$msg = "Keine Benutzer gefunden";
	}
?>
