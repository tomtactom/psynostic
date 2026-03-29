<body>
	<header>
		<img src="../include/images/ico.ico" height="70px">
		<nav>
			<a></a>
			<ul>
				<li <?php if($step == "introduction") echo "style=\"font-weight:bold\""; ?>>Einleitung</li>
				<li <?php if($step == "eula") echo "style=\"font-weight:bold\""; ?>>EULA</li>
				<li <?php if($step == "requirements") echo "style=\"font-weight:bold\""; ?>>Vorraussetzungen</li>
				<li <?php if($step == "filePermissions") echo "style=\"font-weight:bold\""; ?>>Rechte</li>
				<li <?php if($step == "database") echo "style=\"font-weight:bold\""; ?>>Datenbank</li>
				<li <?php if($step == "importSQL") echo "style=\"font-weight:bold\""; ?>>SQL</li>
				<li <?php if($step == "settings") echo "style=\"font-weight:bold\""; ?>>Account</li>
				<li <?php if($step == "websitesettings") echo "style=\"font-weight:bold\""; ?>>Einstellungen</li>
				<li <?php if($step == "done") echo "style=\"font-weight:bold\""; ?>>Fertig</li>
			</ul>
		</nav>
	</header>
