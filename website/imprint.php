<?php
	$show_only_user = false;
	$title = 'Impressum';
	$description = 'Im Impressum findest du unsere Daten nach § 5 TMG';
	$keywords = 'impressum';
	include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
?>
<article>
    <section>
		<h1>Impressum</h1>
		<?php get('error'); ?>
		<section>
			<h3>Angaben gemäß § 5 TMG: </h3>
			<blockquote>
				<?php echo $options['author']; ?><br>
				<address>
					<br>
					<br>
					<br>
				</address>
				<hr>
			</blockquote>
				<a href="./contact" hreflang="<?php echo $options['short_language']; ?>" title="Kontaktiere uns" rel="help"><h1>Kontaktiere uns hier</h1></a>
			<blockquote>
			<hr>
				📧: info[ät]<br>
				<?php /*☎: <small><abbr title="Für Supportanfragen bitte das Kontaktformular verwenden!"><i>+ 49 · 15 · 00 · 00 · 00 · 000</i></abbr></small><br>*/ ?>
				Telefonnummer und Adresse gibt es auf Anfrage
			</blockquote>
			<br>
			<br>
			<strong>Fals es rechtliche Probleme gibt, würden wir uns über eine persönliche Lösung freuen.</strong>
		</section>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT']."/include/website/footer.inc.php") ?>
