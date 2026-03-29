	<article>
		<section>
			<h3>EULA</h3>
			<p>
				Du musst die EULA akzeptieren um fortzufahren.
			</p>
			<textarea style="height: 300px; width: 98%;" readonly><?php echo $eula; ?></textarea>
			<hr>
			<a href="index.php">Abbrechen</a>
			<form method="post">
				<input type="hidden" name="nextStep" value="requirements">
				<button type="submit">Ich habe die EULA gelesen und akzeptiert</button>
			</form>
		</section>
	</article>
