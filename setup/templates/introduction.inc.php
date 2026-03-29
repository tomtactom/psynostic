	<article>
		<section>
			<h3>Einleitung</h3>
			<p>
				Du bist dabei <strong><?php  echo $product; ?></strong> (Version: <?php echo $productVersion; ?>), entwickelt von <strong><?php echo $company; ?></strong> zu installieren.
			</p>
			<form method="post">
				<input type="hidden" name="nextStep" value="eula">
				<button type="submit">Start</button>
			</form>
		</section>
	</article>
