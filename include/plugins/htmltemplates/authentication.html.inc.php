<?php
	if ($showForm) {
?>
<h3>Jetzt den Account von @<?php echo $user['username']; ?> aktivieren</h3>
<form method="post">
	<button name="send">Account bestätigen</button>
</form>
<?php } ?>
