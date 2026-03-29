<?php askQuestion(); ?>
<footer>
	<section>
		<strong>Powered by LupusGUI</strong>
		<p>Das LupusNova Theme verbindet moderne Ästhetik mit klarer Usability.</p>
	</section>
	<nav>
		<ul>
			<?php
			$menuRightFile = dirname(__DIR__, 2) . '/menu_right.inc.php';
			if (is_file($menuRightFile)) {
				include $menuRightFile;
			}
			?>
		</ul>
	</nav>
	<section>
		<p>&copy;<?php echo date('Y').' '.$options['sitename']; ?></p>
	</section>
</footer>
