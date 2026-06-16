# Changelog – AI Beitragseinreichung

## Version 1.2.5 – 2026-06-16

**Fixed:**

- Versionshinweis-Popup auf Smartphones scrollbar gemacht, damit der komplette Inhalt erreichbar bleibt und nicht abgeschnitten wird.
- Mobile Darstellung des Versionshinweises kompakter gestaltet.
- Ladehinweis bei der Vorschau-Erstellung korrigiert, damit dort nicht mehr von einer Beitragseinreichung gesprochen wird.

## Version 1.2.4 – 2026-06-07

**Fixed:**

- Benachrichtigungs-Empfänger werden beim Laden der Einstellungen typstabil gelesen, damit gespeicherte Checkbox-Auswahlen sichtbar erhalten bleiben.

## Version 1.2.3 – 2026-06-07

**Added:**

- Schlagwort-Assistent für das Einreichungsformular:
  - Schlagwörter werden als Kacheln erfasst.
  - Doppelte Schlagwörter werden vermieden.
  - Das aktuelle Jahr kann automatisch vorgeschlagen werden.
- KI-Schlagwörter ergänzt:
  - automatische Schlagwortvorschläge pro Beitrag
  - optional abschaltbar zugunsten manueller Eingabe
  - KI-Schlagwort-Pool mit häufig verwendeten WordPress-Schlagwörtern
  - häufige Schlagwörter mit Nutzungszahl und Übernahme per Popup
- Versionshinweis ergänzt, der pro Nutzer angezeigt und in den Einstellungen erneut geöffnet werden kann.
- Kleine Lottie-Animation ergänzt, die bei aktivierter KI-Unterstützung im Einreichungsformular erscheint.
- Link zum GitHub-Issue-Tracker im Einreichungsformular ergänzt.
- Kleine Versionsanzeige im Formular und in den Einstellungen ergänzt.
- Eigenes Hinweis-Modal für Validierung, Warnungen und Bestätigungen im Einreichungsformular, in den Einstellungen und im KI-Protokoll ergänzt.

**Changed:**

- Einstellungen für KI, Schlagwörter und Stilgruppen neu organisiert.
- Stilgruppen werden nun in einem Popup bearbeitet; Speichern im Popup speichert direkt die kompletten Einstellungen.
- Vorschau zeigt Schlagwörter als eigene Kacheln an.
- Warnungen zu fehlendem Beitragsbild oder deaktivierter KI erscheinen bereits vor der Vorschau-Erstellung.
- Versionshinweis-Popup nutzerfreundlicher formuliert und deutlich mit dem Wiki verlinkt.
- Versionshinweis-Popup optisch überarbeitet und lokale Release-Lottie klein eingebunden.
- KI-Prompts und sichtbare Modellhinweise verwenden echte deutsche Umlaute.
- Direkte Einreichung ohne Vorschau weist deutlicher auf fehlende Prüfung und mögliche KI-Fehler hin.
- Warnhinweise in Dialogen werden optisch stärker voneinander abgegrenzt.
- KI-Fehlerhinweis erscheint beim finalen Speichern nur noch, wenn ohne vorherige Vorschau direkt eingereicht wird.
- Vorschauen werden zusätzlich serverseitig kurzzeitig gespeichert und beim finalen Einreichen per Token übernommen.
- OpenAI-Netzwerkfehler werden nicht mehr zusätzlich per `error_log()` protokolliert, sondern über die bestehende Admin-Benachrichtigung behandelt.

**Notes:**

- Grundlage für einen deutlich saubereren Einreichungsprozess geschaffen.
- Die Änderungen betreffen vor allem Bedienbarkeit, Vorschau, KI-Schlagwörter und Pflege der Einstellungen.
- Das Formular-JavaScript sollte als nächster Wartungsschritt aus dem Inline-Block in eine eigene Admin-Asset-Datei ausgelagert werden.

## Version 1.2.2 – 2026-06-07

**Added:**

- Vorschau-Schritt fuer Beitragseinreichungen begonnen: Beitraege koennen vor dem finalen Speichern per AJAX vorbereitet und geprueft werden.
- Eingabefeld fuer konkrete Aenderungswuensche in der Vorschau ergaenzt, z. B. „Text kuerzer schreiben“ oder „freundlicher formulieren“.
- Finales Speichern uebernimmt vorbereitete Vorschauwerte, damit die KI nicht ungewollt doppelt laeuft.
- Benachrichtigungsmail fuer neue Beitraege als strukturierte Vorschau mit Metadaten, Beitragsbild, Textauszug, Inhalt und Zusatzbildern ueberarbeitet.
- KI-Protokoll als kompaktere Liste mit Detail-Popup und formatierter optimierter Vorschau ueberarbeitet.

**Fixed:**

- Vorschau-AJAX wird nicht mehr versehentlich vom finalen Speichern verarbeitet; dadurch werden Mail, KI-Protokoll und Beitragserstellung erst beim finalen Einreichen ausgefuehrt.
- Zusatzbilder koennen im Formular wieder vollstaendig entfernt werden.
- Vorschau nutzt bei aktiver KI das KI-Lottie, blendet den Vorschau-erstellen-Button nach erfolgreicher Vorschau aus und rendert einfache Markdown-Formatierungen wie Fetttext.
- Direkte Beitragseinreichung ohne vorherige Vorschau wieder erlaubt; Vorschau bleibt optional.
- Vorschau-AJAX sendet keine alten Preview-Hidden-Felder mehr mit und gibt KI-Fehler direkt im Browser zurueck, statt Admin-Mails fuer Preview-Fehler auszulösen.
- KI-Protokoll auf Smartphone-Ansichten als Kartenlayout und mit mobil nutzbarem Detail-Popup verbessert.

## Version 1.2.1 – 2026-06-06

**Improved:**

- KI-Modellverwaltung weiter zentralisiert: freigeschaltete Modelle, Standardmodell und Modellbeschreibungen werden jetzt gemeinsam in `includes/ai/ai-models.php` gepflegt.
- Die Einstellungsseite zeigt künftig direkt freigeschaltete OpenAI-Modelle statt abstrakter Profile; ungültige oder veraltete gespeicherte Werte fallen automatisch auf das Standardmodell zurück.
- KI-Protokoll und Benachrichtigungsmail zeigen verwendete Modelle verständlicher mit Anzeigename und technischem Modellnamen an.
- OpenAI-Aufrufe nutzen jetzt die empfohlene Responses API statt Chat Completions.
- Strukturierte KI-Antworten fuer Titel, Inhalt und Textauszug werden jetzt per JSON Schema angefordert.
- Modellbezogene Request-Optionen wie `reasoning.effort` werden zentral pro Modell konfiguriert.
- Admin-Menü, OpenAI-AJAX-Test, KI-Fehlerbenachrichtigung und Custom Post Status sind aus `wp-form.php` in passende Module ausgelagert.
- Formular-JavaScript fuer Media Picker, Validierung und Erfolgs-/Fehler-Overlays ist aus `wp-form.php` in ein Admin-Modul ausgelagert.
- Projektlokales PHP-Tooling mit Composer, PHPCS, WordPress-Sicherheitsregeln und PHPCompatibilityWP eingerichtet.
- Lokale `AGENTS.md` um Composer-/PHPCS-/PHPCBF-Arbeitsregeln ergaenzt.
- Lokale `AGENTS.md` um FTP-Sync-Regeln ergaenzt: ausschliesslich lokal nach remote und mit festen Ausschluessen.
- WordPress-Stubs und VS-Code-Einstellungen fuer lokale PHP-/PHPCS-Diagnosen ergaenzt.

**Fixed:**

- Mehrere PHPCS-Sicherheitsbefunde bereinigt, u. a. Nonce-Unslash, fehlendes Escaping und unpräfixierte globale Hilfsfunktionen.
- OpenAI-Verbindungstest nutzt jetzt das Mindestlimit der Responses API fuer `max_output_tokens`.
- Statischen OpenAI-Konto-Limit-Hinweis aus den Einstellungen entfernt.
- `gpt-5.5` in der zentralen Modellkonfiguration fuer die Auswahl freigeschaltet.
- Weitere Reserve-Modelle deaktiviert in der zentralen Modellkonfiguration hinterlegt.
- Benachrichtigungs-Empfaenger in den Einstellungen kompakter gemacht: Suche, Sichtbar-Auswahl, Auswahl leeren und Zaehler ergaenzt.
- VS-Code-Diagnosen weiter bereinigt: WordPress-Stubs absolut eingebunden, doppelte PHP-Undefined-Function-Pruefung deaktiviert und Modellhelfer per PHPDoc typisiert.
- Versionshinweis fuer Version 1.2.1 ergaenzt, der pro Nutzer einmal im Plugin-Adminbereich angezeigt wird und die Verbesserungen nutzerfreundlich zusammenfasst.
- Zusatzbilder werden im Formular mit Vorschau angezeigt und im Beitrag sauber als Einzelbild oder Galerie-Slider eingefuegt.
- Link zu den Plugin-Einstellungen im Versionshinweis wird nur noch Nutzern mit Einstellungsrecht angezeigt.

**Notes:**

- Naechster geplanter Entwicklungsschritt fuer Version 1.2.2: Vorschau-Schritt vor dem finalen Speichern, inklusive Eingabefeld fuer konkrete Aenderungswuensche wie „Text kuerzer schreiben“ oder „freundlicher formulieren“.

## Version 1.2.0 – 2026-06-06

**Fixed:**

- API-Key wird beim Speichern der Einstellungen nicht mehr durch maskierte Sternchen ueberschrieben.
- Admin-only Einstellungen werden nicht mehr durch Benutzer ohne Admin-Recht geleert oder deaktiviert.
- Beitragseinreichungen pruefen jetzt die Plugin-Capability `beitragseinreichung_submit`.
- Fehler bei `wp_insert_post()` werden vor weiteren Metadaten-Aenderungen abgefangen.
- KI-generierte Titel werden vor dem Speichern von Markdown und HTML bereinigt und auf eine sinnvolle Laenge begrenzt.
- Emojis bleiben in Titeln erlaubt, damit Stilgruppen weiterhin expressive Titel erzeugen koennen.
- Zieltext der Stilgruppe wird im Formular wieder anhand der ausgewaehlten Stilgruppen-Bezeichnung gefunden.
- JavaScript-Altlasten auf der Einstellungsseite entfernt, die auf nicht mehr vorhandene Elemente verwiesen.

**Improved:**

- KI-Optimierung fuer Titel, Inhalt und optionalen Textauszug nutzt jetzt eine strukturierte JSON-Antwort in einem gemeinsamen KI-Aufruf.
- Prompt-Aufbau und Parsing strukturierter KI-Antworten sind in eigene Module ausgelagert.
- Galerie-Anhang und Benachrichtigungsmail sind aus dem Post-Handler in ein Core-Hilfsmodul ausgelagert.
- Titel werden mit Kontext des Beitragstextes verbessert, bleiben aber nah an der Nutzereingabe.
- Titel-Prompt fuer die KI enger gefasst: kurze einzeilige Titel, keine Markdown-Formatierung, keine erfundenen Fakten.
- Textauszug-Prompt nutzt eigene Temperatur und Systemrolle.
- Markdown-Links werden bei der Gutenberg-Konvertierung sicherer verarbeitet.
- OpenAI-Verbindungstest per AJAX prueft jetzt die passende Plugin-Capability.
- Direkte Plugin-Dateiaufrufe werden frueher blockiert.

**Notes:**

- Naechster geplanter Umbau: Post-Verarbeitung weiter in kleinere Erstellungs- und Validierungsfunktionen trennen.
- Spaetere Phase: zentrale, leicht pflegbare Modellkonfiguration mit UI-Profilen statt verstreuten technischen Modellnamen.

## Version 1.1.4 – 2025-06-16

**Added:**

- Visueller Hinweis bei fehlendem Komma in den Schlagwörtern (z. B. bei Eingabe „2025 Berlin Lauf“).
- Tooltip-Erweiterung und Inline-Anzeige des Stilgruppen-Ziels bei der Beitragseinreichung.

**Improved:**

- Bessere Nutzerführung beim Bearbeiten von Stilgruppen – inkl. Hinweis zum finalen Speichern der Einstellungen.

**Fixed:**

- Kritischer Bug behoben: Stilgruppenbeschreibung wurde bei der KI-Optimierung nicht korrekt an die OpenAI-API übergeben.

## Version 1.1.3 – 2025-05-25

**Added:**

- Feld „Ziel“ für Stilgruppen zur Beschreibung des Einsatzzwecks.
- Tooltip mit Zieltext bei Hover über Stilgruppen-Auswahl im Formular.
- Zieltext wird zusätzlich unterhalb der Stilgruppen-Auswahl angezeigt.
- Hinweis unter Stilgruppen-Editor, dass unten auf „Einstellungen speichern“ geklickt werden muss.

**Improved:**

- Stilgruppen-Editor visuell und funktional überarbeitet.
- Formularvalidierung bei Stilgruppenwahl (wenn KI aktiv) verbessert.

**Fixed:**

- Stilgruppen-Zieltext wurde beim Bearbeiten nicht korrekt angezeigt.
- Hinweis auf „Einstellungen speichern“ erschien mehrfach.

## Version 1.1.2 – 2025-05-22

**Added:**

- Wiki-Verlinkung am Ende der Seiten „Einstellungen“ und „Beitrag einreichen“
- Sicherheitshinweis zur vertraulichen Behandlung des OpenAI API-Keys
- Neuer Hilfe-Abschnitt zur API-Integration und OpenAI-Registrierung

**Improved:**

- Ausführlichere Dokumentation im GitHub-Wiki (Installationshilfe, Beispiele, Debug)
- Stilgruppenbeschreibung überarbeitet mit formatierbarem Beispiel
- Übersichtliche Weiterleitungen innerhalb der Wiki-Seiten eingebaut

**Fixed:**

- Problem behoben: KI-generierte Textauszüge enthielten unerlaubte Markdown-Formatierung (`**fett**`) → wird nun automatisch entfernt
- Fehler-Overlay korrekt beendet bei KI-Abbruch (Endlosschleife nach KI-Abbruch behoben.)

## Version 1.1.1 – 2025-04-26

**Added:**

- Erfolgs-Overlay nach erfolgreicher Einreichung mit Lottie-Animation
- Button „📝 Beitrag jetzt prüfen“ im Erfolgs-Overlay (öffnet eingereichten Beitrag direkt im Editor)
- Responsives Logo auf der Einreichungsseite (automatischer Wechsel zwischen banner-big.png und banner-small.png je nach Bildschirmgröße)

**Improved:**

- Erfolgsmeldungen, Buttons und Animationen visuell vereinheitlicht (Button-Design angepasst)
- Fehler-Overlay verbessert (schnelleres Anzeigen, Hover-Effekte für Buttons)
- Kleinere JavaScript-Optimierungen für die Erfolgs- und Fehleranzeige

**Fixed:**

- Fehler behoben: „beitragID is not defined“ beim Klick auf „Beitrag jetzt prüfen“
- Fehler behoben: „Textauszug automatisch generieren“ erzeugt nur den optimierten Text

## Version 1.1.0 – 2025-04-25

**Added:**

- Unterstützung für Textauszug (Vorschautext):
- Manuell eingeben oder automatisch durch KI generieren lassen
- Neue Einstellung in den Optionen zum Aktivieren/Deaktivieren der Textauszugsfunktion
- Textauszug wird nun im KI-Protokoll gespeichert und angezeigt
- Neue Option „Textauszug automatisch generieren“ im Formular (visuell integriert in den KI-Bereich)
- Visuell überarbeiteter graublauer KI-Bereich im Einreichungsformular
- Dropdown in den Einstellungen zur Steuerung der Textauszugs-Funktion
- Automatische Ausblendung des Textauszug-Feldes bei aktiver KI und Checkbox
- Neue Styles und JavaScript-Logik zur dynamischen Steuerung von Textauszug und KI-Feldern

**Improved:**

- Konsistente visuelle Darstellung der KI-Einstellungen
- KI-Optimierungslogik überarbeitet, um auch den Textauszug zu erzeugen
- Einstellungen klarer gruppiert: Kategorie, Empfänger, Textauszug, KI, Stilgruppen
- JS-Validierung bei Formular-Absenden verbessert

**Fixed:**

- Kleinere UI-Inkonsistenzen im Formular bei aktivierter KI
- Doppelte Anzeige der Checkbox für automatischen Textauszug entfernt
- excerpt-Feld wird nun korrekt im Beitrag gespeichert und verarbeitet

## Version 1.0.0 – 2025-04-14

**Added:**

- Stabile Version mit allen Kernfunktionen
- Professionelles Design mit responsivem Header
- Kompatibel mit Gutenberg-Editor und OpenAI GPT-API
- Plugin-ready für WordPress.org Pluginverzeichnis

## Version 0.7.0 – 2024-04-13

**Added:**

- Design-Optimierungen für Formularseite
- Responsives Logo für Plugin-Seite

**Fixed:**

- Logo-Skalierung war unsauber, Anzeige wurde korrigiert

## Version 0.6.0 – 2024-04-12

**Added:**

- Benutzerrechte über Capabilities: submit, settings, admin
- Integration mit Plugin 'Members' inkl. Gruppierung der Rechte

## Version 0.5.0 – 2024-04-10

**Added:**

- Einstellungsseite mit Optionen für Kategorie, Empfänger, OpenAI-Modell, Stilgruppen
- Verbindungstest für OpenAI-API beim Speichern der Einstellungen

## Version 0.4.0 – 2024-04-06

**Added:**

- KI-Optimierung speichert Protokoll der Änderungen
- Adminseite mit Protokollanzeige für KI-Einsätze

**Fixed:**

- Fehlende Pflichtfelder verhinderten Einreichung bei deaktivierter KI-Option

## Version 0.3.0 – 2024-03-29

**Added:**

- Einbindung von OpenAI GPT-4 zur Textoptimierung (optional pro Beitrag)
- Stilgruppen und Zusatzhinweise für KI auswählbar

**Fixed:**

- Markdown-Formatierung wurde nicht korrekt in Gutenberg-Blöcke überführt

## Version 0.2.0 – 2024-03-22

**Added:**

- Integration von Beitragsbild-Upload und Galerie mit Media Picker
- E-Mail-Benachrichtigung an definierte Nutzer bei neuer Einreichung

## Version 0.1.0 – 2024-03-15

**Added:**

- Grundgerüst für Beitragsformular im Adminbereich
- Funktion zum Absenden und Speichern von Beiträgen mit benutzerdefiniertem Status „In Verarbeitung“
