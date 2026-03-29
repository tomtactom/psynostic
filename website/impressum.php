<?php
	$show_only_user = false;
	$title = 'Impressum';
	$description = 'Unsere Daten';
	$keywords = 'impressum';
	include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
?>
<article>
    <section>
		<h1>Impressum</h1>
		
		<?php get('error'); ?>
		
		<h3>Angaben gemäß § 5 TMG: </h3>
		<blockquote>
			<?php echo $options['author']; ?><br>
			<i>Webentwickler</i><br>
			<!-- hier Adresse einfügen -->
		</blockquote>
			<h3>Kontakt:</h3>
		<blockquote>
			<!--Telefon: <br> Hier Telefonnummer einfügen -->
			E-Mail: <?php echo $options['adminemail']; ?><br>
		</blockquote>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT']."/include/website/footer.inc.php") ?>
