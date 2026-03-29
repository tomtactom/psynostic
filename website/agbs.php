<?php
	$show_only_user = false;
	$title = 'Allgemeine Geschäftsbedingungen - AGBs';
	$keywords = 'AGBs, Allgemeine Geschäftsbedingungen';
	$description = "Mit dem Nutzen dieser Webseite erklärst du dich einverstanden mit unseren Allgemeinen Geschäftsbedingungen!";
	include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php'); 
  ?><article>
		<section>
			<?php get('error'); ?>
			<!-- Trage hier deine Individuelle AGBs ein -->
			<br>
			<hr>
			<br>
			<strong>Sollte ein genannter Punkt vom Gesetzgeber als ungültig erklärt werden, gelten immer noch alle anderen Punkte</strong>
		</section>
    </article>
<?php include($_SERVER['DOCUMENT_ROOT']."/include/website/footer.inc.php"); ?>
