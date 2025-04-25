# Changelog – AI Beitragseinreichung

## Version 1.1.0 – 2025-04-25
**Added:**
-Unterstützung für Textauszug (Vorschautext):
-Manuell eingeben oder automatisch durch KI generieren lassen
-Neue Einstellung in den Optionen zum Aktivieren/Deaktivieren der Textauszugsfunktion
-Textauszug wird nun im KI-Protokoll gespeichert und angezeigt
-Neue Option „Textauszug automatisch generieren“ im Formular (visuell integriert in den KI-Bereich)
-Visuell überarbeiteter graublauer KI-Bereich im Einreichungsformular
-Dropdown in den Einstellungen zur Steuerung der Textauszugs-Funktion
-Automatische Ausblendung des Textauszug-Feldes bei aktiver KI und Checkbox
-Neue Styles und JavaScript-Logik zur dynamischen Steuerung von Textauszug und KI-Feldern

**Improved:**
-Konsistente visuelle Darstellung der KI-Einstellungen
-KI-Optimierungslogik überarbeitet, um auch den Textauszug zu erzeugen
-Einstellungen klarer gruppiert: Kategorie, Empfänger, Textauszug, KI, Stilgruppen
-JS-Validierung bei Formular-Absenden verbessert

**Fixed:**
-Kleinere UI-Inkonsistenzen im Formular bei aktivierter KI
-Doppelte Anzeige der Checkbox für automatischen Textauszug entfernt
-excerpt-Feld wird nun korrekt im Beitrag gespeichert und verarbeitet

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