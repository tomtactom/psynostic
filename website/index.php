<?php
	$show_only_user = false;
	$title = 'Startseite';
	$description = 'Startseite';
	$keywords = 'startseite';
	include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
?>

    <article>
		<div id="header">
			<h1>Lorem ipsum dolor sit amet</h1>
			<p>
				Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy
			</p>
		</div>
		<?php get('error'); ?>
		<section>
			<h1 style="font-size: 2rem; font-weigth: bold; text-align: center;" class="animated-text">At vero eos et accusam et justo.</h1>
			<p>
				Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor invidunt ut labore et dolore magna aliquyam erat, sed diam voluptua. <br>
				At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.
			</p>
			<p>
				Wir hoffen LupusGUI gefällt dir
			</p>
		</section>
        <main>
            <section>
                <h1>Beta-Version</h1>
                <ul>
                    <li>CMS-System Testen</li>
                    <li>Kostenlose Updates</li>
                    <li>Front- &amp; Backend einzeln Nutzbar</li>
                    <li>Viele kostenlose Plugins inbegriffen</li>
					<li>Usersystem mit Profilen und Rangstufen</li>
					<li><i>Danke fürs testen :)</i></li>
                </ul>
                <h1>Kostenlos</h1>
            </section>
        </main>
        <section>
			<h1><!--Starte hier--></h1>
			<p>
				Deine Seite wartet...<br>
				Starte jetzt mit HTML
			</p>
        </section>
    </article>
<?php include($_SERVER['DOCUMENT_ROOT']."/include/website/footer.inc.php") ?>
