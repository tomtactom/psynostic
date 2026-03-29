# psynostic
Dieses Tool ist ein Grundgerüst für eine Webseite die Fragebögen mit Normwerttabellen auswerten kann. Links können einfach an Menschen geschickt werden, diese können den Fragebogen ausfüllen und diese werden vollautomatisch ausgewertet. Grundlage dafür ist das LupusGUI-System.

## Neues Designpaket: `lupusnova`
Es wurde ein modernes Frontend- und Backend-Design unter `website/design/lupusnova` und `backend/design/lupusnova` hinzugefügt.

Aktivierung im CMS:
1. Im Backend anmelden.
2. `Einstellungen` öffnen.
3. Bei **Backend Design** und **Frontend Design** jeweils `lupusnova` auswählen.
4. Speichern.

## Normgruppen-Zuordnung für abgeschlossene Sessions
Eine referenzhafte Implementierung der Zuordnungslogik liegt in `include/plugins/norm_assignment.inc.php`.
Das zugehörige Regel-JSON-Schema ist in `docs/norm-rule-schema.md` dokumentiert.
