<?php
    @session_start();
    require('../include/database/database.php');
    require('../include/functions.inc.php');
	require('../include/settings.inc.php');
	require($options['pluginpath']."/loadplugins.inc.php");
	add("http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."");
?>
<html lang="<?php echo $options['short_language']; ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale: 1.0, user-scalable=0">
	<meta name="language" content="<?php echo $options['short_language']; ?>">
	<meta name="date" content="<?php echo $options['created']; ?>">
	<style>
		:root {
			--maincolor: <?php echo $options['maincolor']; ?>;
			--mainfontcolor: <?php echo $options['mainfontcolor']; ?>;
			--mainbackgroundcolor: <?php echo $options['mainbackgroundcolor']; ?>;
			--mainhovercolor: <?php echo $options['mainhovercolor']; ?>
		}
		<?php if ($options['font'] != 'none' || empty($options['font'])) { ?>
		@font-face { 
			font-family: '<?php echo $options['fontname']; ?>';
            src: url('<?php echo $options['siteurl']; ?>/include/database/fonts/<?php echo $options['font'];; ?>');
		}
		html,
		body { 
			font-family: <?php echo $options['fontname']; ?>, sans-serif; 
		}
		<?php } ?>
	</style>
	<title><?php echo $title; ?> | <?php echo $options['sitename']; ?></title>
	<meta name="keywords" content="<?php echo $options['keywordsmain']; ?>">
	<meta name="description" content="<?php echo $options['sitedescription']; ?>">
	<meta name="robots" content="<?php echo $options['robots']; ?>">
	<meta http-equiv="language" content="<?php echo $options['language'].", ".$options['short_language']; ?>">
	<meta name="author" content="<?php echo $options['author']; ?>">
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
	<meta name="msapplication-TileColor" content="<?php echo $options['maincolor']; ?>">
	<meta name="theme-color" content="<?php echo $options['maincolor']; ?>">
	<link rel="stylesheet" href="<?php echo $options['siteurl']; ?>/design/<?php echo $options['frontenddesign']; ?>/style.css">
	<?php 
		include('design/'.$options['frontenddesign'].'/head.php');
	?>
</head>
	<?php 
		include('../include/website/header.inc.php');
	?>
	