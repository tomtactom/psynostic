<?php
@session_start();
require('../include/database/database.php');
require('../include/functions.inc.php');
require('../include/settings.inc.php');
require($options['pluginpath'] . '/loadplugins.inc.php');

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
add($scheme . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
?>
<!doctype html>
<html lang="<?php echo htmlspecialchars($options['short_language'], ENT_QUOTES, 'UTF-8'); ?>">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<meta name="language" content="<?php echo htmlspecialchars($options['short_language'], ENT_QUOTES, 'UTF-8'); ?>">
	<meta name="date" content="<?php echo htmlspecialchars($options['created'], ENT_QUOTES, 'UTF-8'); ?>">
	<style>
		:root {
			--maincolor: <?php echo $options['maincolor']; ?>;
			--mainfontcolor: <?php echo $options['mainfontcolor']; ?>;
			--mainbackgroundcolor: <?php echo $options['mainbackgroundcolor']; ?>;
			--mainhovercolor: <?php echo $options['mainhovercolor']; ?>;
		}
		<?php if ($options['font'] !== 'none' && !empty($options['font'])) { ?>
		@font-face {
			font-family: '<?php echo addslashes($options['fontname']); ?>';
			src: url('<?php echo $options['siteurl']; ?>/include/database/fonts/<?php echo rawurlencode($options['font']); ?>');
		}
		html,
		body {
			font-family: <?php echo $options['fontname']; ?>, sans-serif;
		}
		<?php } ?>
	</style>
	<title><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?> | <?php echo htmlspecialchars($options['sitename'], ENT_QUOTES, 'UTF-8'); ?></title>
	<meta name="keywords" content="<?php echo htmlspecialchars($options['keywordsmain'], ENT_QUOTES, 'UTF-8'); ?>">
	<meta name="description" content="<?php echo htmlspecialchars($options['sitedescription'], ENT_QUOTES, 'UTF-8'); ?>">
	<meta name="robots" content="<?php echo htmlspecialchars($options['robots'], ENT_QUOTES, 'UTF-8'); ?>">
	<meta http-equiv="content-language" content="<?php echo htmlspecialchars($options['language'] . ', ' . $options['short_language'], ENT_QUOTES, 'UTF-8'); ?>">
	<meta name="author" content="<?php echo htmlspecialchars($options['author'], ENT_QUOTES, 'UTF-8'); ?>">
	<link rel="shortcut icon" type="image/x-icon" href="favicon.ico">
	<meta name="msapplication-TileColor" content="<?php echo htmlspecialchars($options['maincolor'], ENT_QUOTES, 'UTF-8'); ?>">
	<meta name="theme-color" content="<?php echo htmlspecialchars($options['maincolor'], ENT_QUOTES, 'UTF-8'); ?>">
	<link rel="stylesheet" href="<?php echo $options['siteurl']; ?>/include/style.css">
	<link rel="stylesheet" href="<?php echo $options['siteurl']; ?>/design/<?php echo $options['frontenddesign']; ?>/style.css">
	<?php include('design/' . $options['frontenddesign'] . '/head.php'); ?>
</head>
<body>
<?php include_once('design/' . $options['frontenddesign'] . '/header.php'); ?>
