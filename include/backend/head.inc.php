<?php
    @session_start();
    require('../include/database/database.php');
    require_once('../include/functions.inc.php');
	require('../include/settings.inc.php');
	require($options['pluginpath']."/loadplugins.inc.php");
	add("http://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."");
?>
<html lang="<?php echo $options['short_language']; ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
	<meta name="language" content="<?php echo $options['short_language']; ?>">
	<meta name="date" content="<?php echo $options['created'] ?>">
	<style>
		:root {
			--maincolor: <?php echo $options['maincolor']; ?>;
			--mainfontcolor: <?php echo $options['mainfontcolor']; ?>;
			--mainbackgroundcolor: <?php echo $options['mainbackgroundcolor']; ?>;
			--mainhovercolor: <?php echo $options['mainhovercolor']; ?>
		}
		<?php if ($options['font'] != 'none' || empty($options['font'])) { ?>
		@font-face { 
			font-family: '<?php echo $options['fontname']; //wird bald was am System geändert ?>';
            src: url('<?php echo $options['siteurl']; ?>/include/database/fonts/<?php echo $options['font'];; ?>');
		}
		html,
		body { 
			font-family: <?php echo $options['fontname']; ?>, sans-serif; 
		}
		<?php } ?>
	</style>
	<title><?php echo $title ?> | <?php echo $options['sitename']; ?></title>
	<meta name="keywords" content="<?php echo $options['keywordsmain']; ?>">
	<meta name="description" content="<?php echo $options['sitedescription']; ?>">
	<meta http-equiv="language" content="<?php echo $options['language'].", ".$options['short_language']; ?>">
	<meta name="author" content="<?php echo $options['author']; ?>">
	<meta name="date" content="<?php echo $options['created']; ?>">
	<link rel="apple-touch-icon" sizes="57x57" href="../include/images/apple-icon-57x57.png">
	<link rel="apple-touch-icon" sizes="60x60" href="../include/images/apple-icon-60x60.png">
	<link rel="apple-touch-icon" sizes="72x72" href="../include/images/apple-icon-72x72.png">
	<link rel="apple-touch-icon" sizes="76x76" href="../include/images/apple-icon-76x76.png">
	<link rel="apple-touch-icon" sizes="114x114" href="../include/images/apple-icon-114x114.png">
	<link rel="apple-touch-icon" sizes="120x120" href="../include/images/apple-icon-120x120.png">
	<link rel="apple-touch-icon" sizes="144x144" href="../include/images/apple-icon-144x144.png">
	<link rel="apple-touch-icon" sizes="152x152" href="../include/images/apple-icon-152x152.png">
	<link rel="apple-touch-icon" sizes="180x180" href="../include/images/apple-icon-180x180.png">
	<link rel="icon" type="image/png" sizes="192x192"  href="../include/images/android-icon-192x192.png">
	<link rel="icon" type="image/png" sizes="32x32" href="../include/images/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="96x96" href="../include/images/favicon-96x96.png">
	<link rel="icon" type="image/png" sizes="16x16" href="../include/images/favicon-16x16.png">
	<link rel="manifest" href="../include/images/manifest.json">
	<meta name="msapplication-TileColor" content="<?php echo $options['maincolor']; ?>">
	<meta name="msapplication-TileImage" content="../include/images/ms-icon-144x144.png">
	<meta name="theme-color" content="<?php echo $options['maincolor']; ?>">
	<link rel="stylesheet" href="<?php echo $options['siteurl']; ?>/include/style.css">
	<link rel="stylesheet" href="<?php echo $options['backendstylesheeturl']; ?>">
	<script src="https://code.jquery.com/jquery-3.3.1.min.js" integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8=" crossorigin="anonymous"></script>
	<script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.2/Chart.bundle.js'></script>
	<script src='<?php echo $options['siteurl']; ?>/include/tablesorter.js'></script>
	<?php 
		include($_SERVER['DOCUMENT_ROOT'].'/backend/design/'.$options['backenddesign'].'/head.php');
	?>
</head>
	