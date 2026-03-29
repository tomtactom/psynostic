    <header>
		<img href="<?php echo $options['siteurl']; ?>" src="<?php echo $options['frontenddesignurl']; ?>/logo.png" alt="Logo">
        <nav>
            <a></a>
			<?php
				include('../website/menu.inc.php');
			?>
        </nav>
    </header>
<?php 
	//gebe Cookie Hinweis aus
	askQuestion(); 
?>
