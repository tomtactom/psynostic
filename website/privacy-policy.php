<?php
	$show_only_user = false;
	$title = 'Datenschutzerklärung';
	$description = 'Wir legen großen Wert auf den schutz deiner Daten. Dazu kannst du dir hier alles durchlesen.';
	$keywords = 'Datenschutzerklärung';
	include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
?>
<article>
    <section>
		<h1><strong>Datenschutzerklärung</strong></h1>
		<?php get('error'); ?>
		<br><br>
		<h1>Erfassung allgemeiner Informationen</h1>
		<p>
			Wenn du auf unsere Webseite zugreifst, werden automatisch Informationen allgemeiner Natur erfasst. Diese Informationen (Server-Logfiles) beinhalten etwa die Art des Webbrowsers, das verwendete Betriebssystem, den Domainnamen deines Internet Service Providers und Ähnliches. Dabei handelt es sich nur um Informationen, welche keine Rückschlüsse auf dich selbst zulassen. Diese Informationen sind technisch notwendig, um von dir angeforderte Inhalte von Webseiten korrekt auszuliefern und fallen bei Nutzung des Internets zwingend an. Anonyme Informationen dieser Art werden von uns statistisch ausgewertet, um unseren Internetauftritt und die dahinterstehende Technik zu optimieren.
		</p>
		<br><br>
		<h1>Cookies</h1>
		<p>
			Wie viele andere Webseiten verwenden wir auch sogenannte „Cookies“. Cookies sind kleine Textdateien, die von einem Webseitenserver auf deine Festplatte übertragen werden. Dadurch erhalten wir automatisch bestimmte Daten wie z. B. IP-Adresse, verwendeter Browser, Betriebssystem über deinen Computer und deine Verbindung zum Internet.<br>
			Cookies können nicht verwendet werden, um Programme zu starten oder Viren auf einen Computer zu übertragen. Anhand der in Cookies enthaltenen Informationen können wir dir die Navigation erleichtern und die korrekte Anzeige unserer Webseiten ermöglichen.<br>
			In keinem Fall werden die von uns erfassten Daten an Dritte weitergegeben oder ohne die Einwilligung eine Verknüpfung mit personenbezogenen Daten hergestellt.<br>
			Natürlich kannst du unsere Website grundsätzlich auch ohne Cookies betrachten. Internet-Browser sind regulär so eingestellt, dass sie Cookies akzeptieren. Du kannst die Verwendung von Cookies jederzeit über die Einstellungen deines Browsers deaktivieren. Bitte verwende die Hilfefunktionen deines Internetbrowsers, um zu erfahren, wie du diese Einstellungen ändern kannst. Bitte beachten, dass die meisten Funktionen unserer Website möglicherweise nicht funktionieren, wenn du die Verwendung von Cookies deaktiviert hast.<br>
			Wir haben verschiedene Cookie arten, welche im folgenden genauer erläutert werden.<br>
			Wie schon im Teil "Das Nutzer Analyse Tool" gibt es einen Cookie der uns erlaubt dich zu analysieren ("AllowCookies")<br>
			Ansonsten wird von unserem System aus ein sogenannter "PHP-Session-Cookie" gespeichert. Der muss gespeichert werden damit das System funktioniert. Darauf haben wir wenig Einfluss, weswegen, wenn du ihn nicht haben möchtest, ihn einfach löschen kannst.<br>
			Ansonsten gibt es noch den Cookie "indentifier" und "securetytoken". Diese werden gesetzt, wenn du beim Login, den Haken "Angemeldet Bleiben" Aktivierst.
		</p>
		<br><br>
		<h1>Registrierung auf unserer Webseite</h1>
		<p>
			<strong>Normalerweise kann sich ein normaler Benutzer nicht registrieren.</strong><br>
			Bist du bei uns registriert, kannst du auf Inhalte und Leistungen zugreifen, die wir nur registrierten Nutzern anbieten. Angemeldete Nutzer haben zudem die Möglichkeit, bei Bedarf die bei Registrierung angegebenen Daten jederzeit zu ändern oder zu löschen. Selbstverständlich erteilen wir dir darüber hinaus jederzeit Auskunft über die von uns über dich gespeicherten personenbezogenen Daten. Gerne berichtigen bzw. löschen wir diese auch auf deinen Wunsch, soweit keine gesetzlichen Aufbewahrungspflichten entgegenstehen. Du hast außerdem die Möglichkeit, deine Daten selber zu ändern und zu löschen.<br>
			<strong>Eine Gruppe zu erstellen, zählt nicht zu "Personenbezogenen Daten", da der Link frei zugänglich ist, deshalb die Gruppe nicht mehr Personenbezogend ist und außerdem keine Angaben zu einer Person gespeichert werden! Wenn du nicht mehr möchtest, dass deine Gruppe Veröffentlicht ist, benutze einfach die Funktion von WhatsApp "Gruppeneinladungslink zurückrufen". Dadurch wird automatisch der Link ungültig gemacht!</strong>
		</p>
		<br>
		<h2>Welche Daten wir von dir speichern uns auslesen können:</h2>
		<p>
			Dieser Bereich gilt, sobald du dich auf unserer Seite registrierst. Mit dem Klick, auf den registrieren Button bestätigst du außerdem die gesamte Datenschutzerklärung akzeptiert zu haben, auch wenn du momentan nicht angemeldet bist.
		</p>
		<h3>ID</h3>
		<p>
			Anhand deiner ID können wir auslesen der wievielte registrierte Benutzer du auf unserer Webseite bist.
		</p>
		<h3>E-Mail-Adresse</h3>
		<p>
			Wir speichern dich anhand deiner E-Mail-Adresse, um dich als Nutzer zu identifizieren, deine Identität nachzuweisen und Spam zu vermeiden.<br>
			Du musst deine eigene E-Mail-Adresse verwenden, auf die du dauerhaften Zugriff hast.<br>
			Wir speichern zwar deine E-Mail-Adresse offen, allerdings steht diese nur wenigen Mitarbeitern zur Verfügung.<br>
		</p>
		<h4>Das dürfen wir mit deiner E-Mail-Adresse machen:</h4>
		<ul>
			<li>Nach Spam durchsuchen</li>
			<li>Dir E-Mails zu wichtigen Änderungen deines Accounts, unserer Datenschutzerklärung, dem System oder anderweitig schicken</li>
			<li><strong>NICHT</strong> an dritte weitergeben</li>
			<li>Von unseren Systemen automatisch verarbeitet werden.</li>
		</ul>
		<h3>Passwort</h3>
		<p>
			Dein Passwort wird momentan mit der Passwort-Hash Methode verschlüsselt die momentan als Unentschlüsselbar gilt. Bei der Abfrage wird also das eingegebene Passwort mit einem Key verschlüsselt und kann so miteinander auf seine Richtigkeit überprüft werden.<br>
			Dein Passwort-Hash kann von ausgewählten Mitarbeitern ausgelesen werden. Damit dein Passwort sicher ist, haben wir ein Minimum von 8 Zeichen gesetzt. Damit dein Passwort aber wirklich sicher ist, empfehlen wir dir ein Passwort von einer Länge von mindestens 16 Zeichen sowie mindestens 3 Großbuchstaben, 3 Kleinbuchstaben, 3 Zahlen und 3 Sonderzeichen, die mit deinem Vornamen, Nachnamen, deiner Biografie, deinem Profilbild, deinem Geburtsdatum, deiner E-Mail und anderen Personenbezogenen Daten nichts zu tun haben, zu nehmen.
		</p>
		<h3>Vorname, Nachname, Biografie, Nutzername</h3>
		<p>
			Dein Vorname, Nachname, Nutzername und deine Biografie wird offen gespeichert und kann von allen anderen registrierten Leuten ausgelesen werden.
		</p>
		<h3>Geburtsdatum</h3>
		<p>
			Um diese Seite zu nutzen, musst du mindestens <?php echo $options['mindestalter']; ?> Jahre alt sein. Dein Geburtsdatum kann nur von ausgewählten Mitarbeitern ausgelesen werden.
		</p>
		<h3>Geschlecht</h3>
		<p>
			Wir speichern dein Geschlecht nach der Registrierung. Wenn du das nicht möchtest, kannst du einfach 'Keine Angabe' auswählen.
		</p>
		<h3>Änderungs-, Passwort vergessen-, &amp; Registrierdaten</h3>
		<p>
			Wir speichern das genaue Datum und deine Uhrzeit, wenn du dein Profil geändert hast, dein Passwort zurückgesetzt wurde und wann du dich registriert hast, damit wir dir bei der Fehlersuche besser helfen können und unsere Statistik genauer machen können.
		</p>
		<br>
		<h2>Sonstiges</h2>
		<p>
			Von allen Registrierten Nutzern ausgelesen werden können: 
		</p>
		<ul>
			<li>Benutzername</li>
			<li>Profilbild</li>
			<li>Vorname</li>
			<li>Nachname</li>
			<li>Biografie</li>
			<li>Geschlecht</li>
		</ul>
		<p>
			Dein Geschlecht kannst du wie oben beschrieben verstecken. Wenn du deine anderen Daten an unsere anderen Nutzer nicht weitergeben möchtest, kannst du dich leider nicht bei uns registrieren.<br>
			Alternativ können wir, wenn du uns eine E-Mail schreibst auch eine Individuelle Lösung finden.
		</p>
		<br><br>
		<h1>Dein Rechte auf Auskunft, Berichtigung, Sperre, Löschung und Widerspruch</h1>
		<p>
			Du hast das Recht, jederzeit Auskunft über die bei uns gespeicherten personenbezogenen Daten zu erhalten. Ebenso hast du das Recht auf Berichtigung, Sperrung oder, abgesehen von der vorgeschriebenen Datenspeicherung zur Geschäftsabwicklung, Löschung deiner personenbezogenen Daten.
		</p>
		<br><br>
		<h1>SSL-Verschlüsselung</h1>
		<p>
			Um die Sicherheit deiner Daten bei der Übertragung zu schützen, verwenden wir dem aktuellen Stand der Technik entsprechende Verschlüsselungsverfahren (z. B. SSL) über HTTPS.
		</p>
		<br><br>
		<h1>Kontaktformular</h1>
		<p>
			Trittst du per E-Mail oder Kontaktformular mit uns in Kontakt, werden die von dir gemachten Angaben zum Zwecke der Bearbeitung der Anfrage sowie für mögliche Anschlussfragen gespeichert. Dabei wird eine E-Mail an uns gesendet und eine E-Mail an die von dir angegebene E-Mail-Adresse, in der auch deine IP-Adresse genannt wird.<br>
			Damit eine Sperre von Daten jederzeit berücksichtigt werden kann, müssen diese Daten zu Kontrollzwecken in einer Sperrdatei vorgehalten werden. Du kannst auch die Löschung der Daten verlangen, soweit keine gesetzliche Archivierungsverpflichtung besteht. Soweit eine solche Verpflichtung besteht, sperren wir deine Daten auf Wunsch.<br>
			Du kannst Änderungen oder den Widerruf einer Einwilligung durch entsprechende Mitteilung an uns mit Wirkung für die Zukunft vornehmen.<br>
			Da nicht viele Leute deine Anliegen bearbeiten, kann es einige Zeit dauern bis wir uns bei dir melden.
		</p>
		<br><br>
		<h1>Änderung unserer Datenschutzbestimmung</h1>
		<p>
			Wir behalten uns vor, diese Datenschutzerklärung gelegentlich anzupassen, damit sie stets den aktuellen rechtlichen Anforderungen entspricht oder um Änderungen unserer Leistungen in der Datenschutzerklärung umzusetzen, z. B. bei der Einführung neuer Services. Für deinen erneuten Besuch gilt dann die neue Datenschutzerklärung. Darüber werden wir dich nicht informieren. Bitte schaue also regelmäßig nach, ob sich an unseren Bestimmungen etwas geändert hat.
		</p>
		<br><br>
		<h1>Änderung all deiner Daten: </h1>
		<p>
			Wir behalten es uns vor, deine Daten ohne nennenden Grund zu löschen. Das kann zum Beispiel aus fehlerhaften/unvollständigen Informationen hervorgerufen werden.<br>
			Im Grunde hast du über einige Daten die eigenständige Funktion deine Daten zu ändern, allerdings behalten wir es uns vor deine Daten zu ändern oder zu löschen, bei zum Beispiel pornografischen, beleidigenden/hetzend, mobbenden, Spam, sinnfreien Inhalten, diese zu löschen.
		</p>
		<br><br>
		<h1>Zuständiger der Datenschutzerklärung</h1>
		<p>
			Da wir laut der DSGVO unter 9 Mitarbeiter sind, müssen wir keinen Datenschutzbeauftragten zur Verfügung stellen.<br>
			Trotzdem stehen hier die Daten desjenigen, der sich um die Datenschutzerklärung kümmert. Für eine schnelle Rückantwort, benutze bitte das normale Kontaktformular.<br>
			Da nicht viele Leute deine Anliegen bearbeiten, kann es einige Zeit dauern bis wir uns bei dir melden
		</p>
		<p>
			<strong>Name: </strong><?php echo $options['author']; ?><br>
			<strong>E-Mail-Adresse: </strong>-<br>
			<strong>Telefonnummer: </strong>Nur auf Anfrage<br>
		</p>
		<h1>Server und Sicherheit</h1>
		<ul>
			<li>Wir haben eine Firewall, die vor Spam schützt. Dies schützt insbesondere die Leistung der Seite.</li>
			<li>Im falle eines Hackerangriffs, werden wir dich umgehend informieren und dich darüber informieren, welche Daten geklaut worden sind.</li>
		</ul>
		<br><br>
		<h1>Haftung für Inhalte</h1>
		<p>
			Als Diensteanbieter sind wir gemäß § 7 Abs.1 TMG für eigene Inhalte auf diesen Seiten nach den<br>
			allgemeinen Gesetzen verantwortlich. Nach §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch nicht<br>
			verpflichtet, übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen<br>
			zu forschen, die auf eine rechtswidrige Tätigkeit hinweisen.<br>
			Verpflichtungen zur Entfernung oder Sperrung der Nutzung von Informationen nach den allgemeinen<br>
			Gesetzen bleiben hiervon unberührt. Eine diesbezügliche Haftung ist jedoch erst ab dem Zeitpunkt der<br>
			Kenntnis einer konkreten Rechtsverletzung möglich. Bei Bekanntwerden von entsprechenden<br>
			Rechtsverletzungen werden wir diese Inhalte umgehend entfernen.<br>
		</p>
		<br><br>
		<h1>Das Nutzer Analyse Tool</h1>
		<p>
			Wir nutzen ein Analysetool zur Analyse grundlegender Nutzerdaten.<br>
			Diese Daten werden ohne Einsatz von Cookies gesammelt.
		</p>
		<ul>
			<li>Der wievielte Aufruf es war.</li>
			<li>Wann welche Seite besucht wurde.</li>
			<li>Der Link der Aufgerufen wurde.</li>
			<li>In welchem Land du gerade bist.</li>
			<li>Welchen Browser du verwendest.</li>
			<li>Welche Browser Version du installiert hast.</li>
			<li>Welches Betriebssystem du installiert hast.</li>
		</ul>
		<p>
			Damit man nicht ungefragt analysiert wird, wirst du, wenn du die Webseite besuchst, oben in einem Cookie-Hinweis gefragt, ob du getrackt werden möchtest. Das ganze kannst du auch ablehnen.<br>
			Dabei wird ein Cookie gesetzt, der einspeichert, dass du getrackt werden darfst. Wenn du dies nicht mehr möchtest, entferne diesen Cookie einfach in deinen Browsereinstellungen.<br>
			<strong>Wir geben keine Daten an Dritte weiter und du wirst anonym ausgewertet</strong><br>
		</p>
		<br><br>
		<h1>Google reCAPTCHA</h1>
		<p>
			In unserem Internetauftritt setzen wir Google reCAPTCHA zur Überprüfung und Vermeidung von Interaktionen auf unserer Internetseite durch automatisierte Zugriffe, bspw. durch sog. Bots, ein. Es handelt sich hierbei um einen Dienst der Google LLC, 1600 Amphitheatre Parkway, Mountain View, CA 94043 USA, nachfolgend nur „Google“ genannt.
			Durch die Zertifizierung nach dem EU-US-Datenschutzschild („EU-US Privacy Shield“)
			<a href="https://www.privacyshield.gov/participant?id=a2zt000000001L5AAI&status=Active" hreflang="en" rel="external" target="_blank">https://www.privacyshield.gov/participant<br>?id=a2zt000000001L5AAI&status=Active</a>
			garantiert Google, dass die Datenschutzvorgaben der EU auch bei der Verarbeitung von Daten in den USA eingehalten werden.
			Durch diesen Dienst kann Google ermitteln, von welcher Webseite eine Anfrage gesendet wird sowie von welcher IP-Adresse aus du die sog. reCAPTCHA-Eingabebox verwendest. Neben der IP-Adresse werden womöglich noch weitere Informationen durch Google erfasst, die für das Angebot und die Gewährleistung dieses Dienstes notwendig sind.   
			Rechtsgrundlage ist Art. 6 Abs. 1 lit. f) DSGVO. Unser berechtigtes Interesse liegt in der Sicherheit unseres Internetauftritts sowie in der Abwehr unerwünschter, automatisierter Zugriffe in Form von Spam o.ä..
			Google bietet unter
			<a href="https://policies.google.com/privacy" hreflang="de" rel="external" target="_blank">https://policies.google.com/privacy</a>
			weitergehende Informationen zu dem allgemeinen Umgang mit deinen Nutzerdaten an.
		</p>
		<br><br>
		<h1>Spamschutz</h1>
		<p>
			Bestimmte Wörter können von dir in den Formularen auf der Webseite nicht benutzt werden, da wir einen Spamwortfilter benutzen. Darunter zählen beleidigungen und Pornografische Wörter.<br>
			Fals du doch eine Gruppe oä. findest, die deiner Meinung nach unangemessene Inhalte bzw. Wörter beinhaltet, kannst du sie gerne über das Kontaktformular melden.
		</p>
		<br><br>
		<h1>Häufig gestellte Fragen - FAQ Funktion</h1>
		<p>
			Deine eingegebenen Daten werden auf unseren Server offen gespeichert. Deine E-Mail-Adresse behalten wir ausschließlich zu Informationszwecken.<br>
			Deine Fragestellung kann so wie du sie gestellt hast oder von uns verändert, der Öffentlichkeit gezeigt werden.<br>
			Du hast nachträglich kein Recht diese zu wiederrufen, vorrausgesetzt sie beinhaltet keine Personenbezogenen Daten.
		</p>
		<br><br>
		<h1>Unser Impressum</h1>
		<p>
			In unserem Impressum sind einige Daten durch Kryptische Zeichen versehen worden (Bspw.: "@" zu "[ät]"). Dies liegt daran, dass wir uns vor Spambots schützen wollen die Rekrusiv Webseiten nach E-Mail-Adressen duchsuchen.<br>
			Selbstverständlich kannst du uns durch ersetzung bzw. entfernung dieser Zeichen normal Kontaktieren, wir empfehlen dir allerdings unsere "Kontakt" Seite zu benutzen für eine schnellere Antwort.
		</p>
		<br>
		<hr>
		<br>
		<big><strong>Hast du noch eine Frage? - Stelle sie uns unter der FAQ Seite</strong></big>
	</section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT']."/include/website/footer.inc.php") ?>
