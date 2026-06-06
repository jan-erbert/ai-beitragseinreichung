# Changelog – AI Beitragseinreichung

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
