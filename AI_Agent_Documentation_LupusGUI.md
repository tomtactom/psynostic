# AI Agent Documentation für LupusGUI

> Diese Datei ist für KI-Agenten gedacht, die mit **LupusGUI als CMS-Grundlage** neue Webseiten, Seitenfunktionen oder Erweiterungen bauen.
> Ziel: **sicher erweitern, ohne das CMS zu beschädigen**.

## 1) Schnellüberblick: Was ist LupusGUI?

LupusGUI ist ein klassisches PHP-CMS mit klarer Trennung:

- `website/` = öffentliches Frontend (Startseite, Login, Register, Kontakt, FAQ, Profil etc.)
- `backend/` = interner Admin-/Support-Bereich
- `setup/` = Installations-Wizard
- `include/` = gemeinsamer CMS-Kern (DB, Settings, Core-Funktionen, Plugins, Templates)

Die eigentliche Geschäftslogik wird häufig in `include/plugins/*.inc.php` umgesetzt, während Seiten (`website/*.php`, `backend/*.php`) vor allem orchestrieren und HTML-Templates laden.

---

## 2) Architekturprinzipien (wichtig für KI-Agenten)

### 2.1 Entrypoint-Muster für Seiten

Neue Seiten sollen sich an bestehende Seiten halten:

1. Zugriffslevel setzen (`$show_only_user`)
2. Meta-Infos setzen (`$title`, optional `$description`, `$keywords`)
3. Passenden Head einbinden:
   - Frontend: `include/website/head.inc.php`
   - Backend: `include/backend/head.inc.php`
4. Optional Plugin-Logik laden
5. Optional Plugin-HTML-Template laden
6. Footer einbinden

**Beispiel-Referenzen:** `website/login.php`, `website/contact.php`, `backend/settings.php`, `backend/index.php`.

### 2.2 Initialisierung passiert in den `head.inc.php`-Dateien

Die Head-Includes machen u. a.:

- Session-Start
- Laden der DB-Verbindung (`include/database/database.php`)
- Laden von Core-Funktionen (`include/functions.inc.php`)
- Laden der CMS-Optionen/Berechtigungen (`include/settings.inc.php`)
- Laden globaler Plugins (`include/plugins/loadplugins.inc.php`)
- Statistik-Tracking via `add(...)`

**Regel:** Nicht doppelt initialisieren (kein zweites `session_start`, kein manuelles erneutes Laden derselben Basisdateien im Seitencode).

### 2.3 Optionssystem statt Hardcoding

LupusGUI liest zentrale Werte aus der DB-Tabelle `option` und baut daraus `$options` (z. B. `siteurl`, Design, Farben, Sprache, Pluginpfade). Nutze vorhandene `$options`-Werte statt festen URLs/Pfaden.

---

## 3) Rollen & Berechtigungen korrekt verwenden

Die Seitenzugriffe werden über `$show_only_user` gesteuert und in `include/settings.inc.php` geprüft.

Gängige Werte:

- `false` → öffentlich
- `min_member`
- `min_supporter`
- `min_manager`
- `adminstrator` (Schreibweise im Code ist absichtlich so)

**Sicherheitsregel:**
- Sensible Backend-Seiten immer mit mindestens `min_supporter` oder höher absichern.
- Reine Admin-Funktionen mit `adminstrator` absichern.

---

## 4) Plugin-System verstehen

## 4.1 Plugin-Ladewege

`include/settings.inc.php` setzt relevante Pfade (falls nicht vorhanden):

- `$options['pluginpath']` → `include/plugins/`
- `$options['pluginhtmlpath']` → `include/plugins/htmltemplates/`
- `$options['plugincmspath']`
- `$options['pluginstatisticpath']`

`include/plugins/loadplugins.inc.php` lädt zentrale Plugins (derzeit z. B. Statistik-Init).

## 4.2 Typisches Plugin-Muster

- **Logik-Datei**: `include/plugins/<name>.inc.php`
- **Template-Datei**: `include/plugins/htmltemplates/<name>.html.inc.php`
- Seite lädt zuerst Logik, dann Template.

Beispiel:
- `website/login.php` lädt `login.inc.php` + `login.html.inc.php`.
- `website/contact.php` lädt `contact.inc.php` + `contact.html.inc.php`.

## 4.3 Wie KI-Agenten neue Plugins sicher hinzufügen

1. Neue Logik in `include/plugins/<feature>.inc.php` anlegen.
2. Neues Template in `include/plugins/htmltemplates/<feature>.html.inc.php` anlegen.
3. Seite in `website/` oder `backend/` erstellen und beide Teile laden.
4. Eingaben serverseitig validieren (Länge, Typ, erlaubte Werte).
5. DB-Zugriffe mit Prepared Statements ausführen.
6. Erfolg/Fehler über die vorhandenen Muster (`$success_msg`, `$error_msg`, `get('error')`) kompatibel halten.

---

## 5) Seiten erstellen: Frontend, Backend, Setup

## 5.1 Frontend-Seite erstellen

Empfohlenes Vorgehen:

1. Neue Datei in `website/<seite>.php` erstellen.
2. `$show_only_user` passend setzen.
3. `include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');`
4. Bei Bedarf Plugin + HTML-Template laden.
5. `include($_SERVER['DOCUMENT_ROOT'].'/include/website/footer.inc.php');`

URL-Verhalten:
- Durch `.htaccess` sind Routen ohne `.php` möglich (Rewrite auf `<route>.php`).

## 5.2 Backend-Seite erstellen

Empfohlenes Vorgehen:

1. Neue Datei in `backend/<seite>.php` erstellen.
2. Zugriffslevel mit `$show_only_user` restriktiv setzen.
3. `include('/include/backend/head.inc.php')`
4. `include('/include/backend/header.inc.php')`
5. Inhalt/Plugin rendern
6. `include('/include/backend/footer.inc.php')`

## 5.3 Setup-Seiten erweitern

Setup basiert auf `setup/index.php` + Step-Dateien `setup/<step>.inc.php` und Templates unter `setup/templates/`.

Wenn du Setup erweiterst:

- Schrittname konsistent über `nextStep` steuern.
- Keine produktiven CMS-Funktionen voraussetzen, bevor DB korrekt eingerichtet ist.
- Dateirechte/DB-Schreibvorgänge vorsichtig und idempotent behandeln.

---

## 6) Was ein KI-Agent mit LupusGUI typischerweise machen kann

- Neue öffentliche Seiten erstellen (Landing, FAQ-Varianten, Content-Seiten).
- Interne Backend-Tools erweitern (z. B. Verwaltungsseiten mit Rollenprüfung).
- Plugin-basierte Features ergänzen (Logik + Template trennen).
- Designs/Themes ergänzen (Frontend/Backend-Designverzeichnisse).
- Website-Einstellungen über bestehende Option-Mechanik nutzen/erweitern.
- Formulare mit Validierung, Mailversand und DB-Speicherung integrieren.

---

## 7) Do/Don’t-Liste, damit das CMS stabil bleibt

## Do

- Bestehende Seitenstruktur kopieren statt komplett neues Muster erfinden.
- `$options` für URLs, Pfade, Meta-Daten und Designs verwenden.
- Rollenprüfung über `$show_only_user` nutzen.
- `include/*.inc.php` nur serverseitig einbinden, nicht direkt verlinken.
- Bei neuen Formularen konsequent validieren und Prepared Statements verwenden.
- Bestehende Namenskonventionen beibehalten (`*.inc.php`, `*.html.inc.php`).

## Don’t

- Keine direkten Breaking-Changes an `include/settings.inc.php`, `include/functions.inc.php` ohne Not.
- Keine globale Initialisierung doppeln (Session/DB/Settings mehrfach laden).
- Keine unvalidierten `$_POST`/`$_GET` direkt in SQL oder Dateipfade einsetzen.
- Keine fest verdrahteten absoluten URLs statt `$options['siteurl']`.
- Keine direkte Auslieferung von `.inc.php` (durch `.htaccess` ohnehin blockiert).

---

## 8) Sichere Entwicklungs-Checkliste für KI-Agenten

Vor jeder Änderung:

1. Scope klären: Frontend, Backend oder Setup?
2. Rechtebedarf klären: öffentlich oder Rollenpflicht?
3. Bestehende ähnliche Seite als Vorlage auswählen.
4. Plugin nötig? Dann Logik/Template sauber trennen.

Während der Umsetzung:

1. Eingaben validieren (Pflichtfelder, Länge, erlaubte Werte).
2. DB nur mit Prepared Statements.
3. Ausgabe escapen (z. B. `htmlentities`) für nutzerbezogene Inhalte.
4. CMS-Pfade und `$options` nutzen.

Nach der Umsetzung:

1. Seite ohne Login prüfen (bei öffentlichen Seiten).
2. Seite mit verschiedenen Rollen prüfen (bei geschützten Seiten).
3. Fehler-/Erfolgsfälle von Formularen testen.
4. URL-Rewrite-Verhalten testen (mit/ohne `.php`).

---

## 9) Wichtige Referenzdateien für KI-Agenten

- `include/settings.inc.php` → Optionen, Rollenprüfung, Pluginpfade
- `include/functions.inc.php` → Login-Check, Utility-Funktionen
- `include/plugins/loadplugins.inc.php` → global geladene Plugins
- `include/plugins/statistic/*` → Beispiel für modulare Plugin-Funktionen
- `website/*.php` → Frontend-Entrypoints
- `backend/*.php` → Backend-Entrypoints
- `setup/index.php` + `setup/templates/*` → Setup-Flow
- `.htaccess` → Rewrite- und Schutzregeln

---

## 10) Minimal-Blueprints (Copy/Paste für Agenten)

### 10.1 Neue Frontend-Seite (Blueprint)

```php
<?php
$show_only_user = false; // oder z. B. 'min_member'
$title = 'Neue Seite';
$description = 'Beschreibung';
$keywords = 'keywords';
include($_SERVER['DOCUMENT_ROOT'].'/include/website/head.inc.php');
?>
<article>
  <section>
    <?php require($options['pluginpath'].'myfeature.inc.php'); ?>
    <?php get('error'); ?>
    <?php require($options['pluginhtmlpath'].'myfeature.html.inc.php'); ?>
  </section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/website/footer.inc.php'); ?>
```

### 10.2 Neue Backend-Seite (Blueprint)

```php
<?php
$show_only_user = 'min_supporter'; // je nach Bedarf restriktiver
$title = 'Neue Backend-Seite';
include($_SERVER['DOCUMENT_ROOT'].'/include/backend/head.inc.php');
include($_SERVER['DOCUMENT_ROOT'].'/include/backend/header.inc.php');
?>
<article>
  <section>
    <?php require($options['pluginpath'].'adminfeature.inc.php'); ?>
    <?php get('error'); ?>
    <?php require($options['pluginhtmlpath'].'adminfeature.html.inc.php'); ?>
  </section>
</article>
<?php include($_SERVER['DOCUMENT_ROOT'].'/include/backend/footer.inc.php'); ?>
```

---

## 11) Fazit

Wenn ein KI-Agent sich an die bestehenden LupusGUI-Muster hält (Entrypoints, Rollenprüfung, Plugin-Trennung, `$options`-Konfiguration), kann er das System zuverlässig erweitern, ohne Kernfunktionen zu destabilisieren.

**Leitprinzip:** _Bestehende Struktur respektieren, nur inkrementell erweitern, Sicherheit/Validierung nie auslassen._
