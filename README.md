# AI Beitragseinreichung

| | |
|---|---|
| **Version** | 1.2.6 |
| **Autor** | Jan Erbert |
| **Letztes Update** | 2026-09-09 |
| **Lizenz** | GPL-2.0-or-later |

**AI Beitragseinreichung** ist ein WordPress-Plugin, mit dem berechtigte Nutzer im Backend Beiträge vorbereiten und zur Prüfung einreichen können. Beiträge werden mit dem Status **in Verarbeitung** gespeichert und können anschließend von Admins oder Redakteuren kontrolliert, bearbeitet und veröffentlicht werden.

Das Plugin unterstützt Titel, Inhalt, Kategorie, Schlagwörter, Beitragsbild, Zusatzbilder, Vorschau, Benachrichtigungen und optional eine KI-gestützte Überarbeitung über die OpenAI API.

---

## Funktionen

- Beiträge im WordPress-Backend einreichen
- Beiträge vor dem Speichern als Vorschau prüfen
- Titel, Inhalt, Kategorie, Schlagwörter, Beitragsbild und Zusatzbilder erfassen
- Schlagwörter als Kacheln eingeben
- doppelte Schlagwörter automatisch vermeiden
- aktuelles Jahr optional als Schlagwort vorschlagen
- Beiträge optional per KI stilistisch überarbeiten
- Textauszug manuell oder automatisch per KI erstellen
- KI-Schlagwörter optional automatisch vorschlagen lassen
- Stilgruppen für unterschiedliche Beitragsarten pflegen
- Zusatzbilder sauber als Einzelbild oder Galerie-Slider einfügen
- Benachrichtigungsmails an ausgewählte Nutzer senden
- KI-Optimierungen im Protokoll nachvollziehen
- eigene WordPress-Capabilities für Rollen und Rechte nutzen

---

## Typischer Ablauf

1. Nutzer öffnet im Backend **AI Beitragseinreichung**.
2. Titel, Inhalt, Kategorie und Schlagwörter werden eingetragen.
3. Optional werden Beitragsbild und Zusatzbilder ausgewählt.
4. Optional wird **Texte automatisch verbessern** aktiviert.
5. Der Beitrag wird über **Vorschau erstellen** geprüft.
6. Bei Bedarf wird ein Änderungswunsch an die KI gesendet.
7. Der Beitrag wird final eingereicht.
8. Admins oder Redakteure prüfen den Beitrag im Status **in Verarbeitung**.

Die Vorschau wird empfohlen, weil sie Titel, Text, Schlagwörter und Bilder vor dem Speichern sichtbar macht.

---

## KI-Funktionen

Die KI ist optional und wird nur genutzt, wenn sie in den Einstellungen aktiviert ist und der Nutzer sie im Formular auswählt.

Unterstützt werden:

- stilistische Überarbeitung von Titel und Beitragstext
- automatische Erstellung eines Textauszugs
- automatische Vorschläge für Schlagwörter
- erneute Überarbeitung über einen konkreten Änderungswunsch in der Vorschau
- zentrale Modellverwaltung über `includes/ai/ai-models.php`

Die OpenAI-Aufrufe nutzen strukturierte Antworten, damit Titel, Inhalt, Textauszug und Schlagwörter zuverlässig getrennt verarbeitet werden können.

Als Standard wird `gpt-5.6-terra` verwendet. Freigeschaltet sind derzeit:

- `gpt-5.6-luna`
- `gpt-5.6-terra`
- `gpt-5.6-sol`
- `gpt-5.4-nano`
- `gpt-5.4-mini`
- `gpt-5.4`
- `gpt-5.5`

Weitere Modelle können zentral vorbereitet werden, ohne sie sofort im Dropdown anzubieten. Ungültige oder nicht mehr freigeschaltete gespeicherte Modellwerte werden automatisch durch das Standardmodell ersetzt.

Wichtig: Die KI verbessert Stil und Struktur, ersetzt aber keine fachliche Prüfung.

---

## Stilgruppen

Stilgruppen geben der KI eine klare sprachliche Richtung. Beispiele:

- Einladung
- Rückblick
- Vereinsmeldung
- Ergebnisbericht
- kurzer Hinweis

Eine gute Stilgruppe beschreibt nicht nur „mach schöner“, sondern gibt konkret vor, wie der Beitrag klingen soll.

Beispiel:

```text
Freundlich und einladend schreiben. Der Beitrag soll Lust auf Teilnahme machen. Datum, Uhrzeit, Ort und Anmeldung deutlich nennen. Titel kurz halten. Keine Inhalte erfinden.
```

Stilgruppen können in den Einstellungen als versionierte JSON-Datei exportiert und wieder importiert werden. Einzelne Importe werden zuerst im Bearbeiten-Popup geöffnet, damit sie geprüft und erst danach gespeichert werden. Mehrere Stilgruppen können über eine gemeinsame JSON-Datei importiert werden.

---

## Schlagwörter

Schlagwörter werden im Formular als Kacheln gepflegt. Sie helfen dabei, Beiträge später besser zu verbinden und auffindbar zu machen.

Das Plugin unterstützt:

- manuelle Schlagwort-Kacheln
- automatische Duplikatvermeidung
- optionales Jahres-Schlagwort
- KI-generierte Schlagwortvorschläge
- häufig verwendete WordPress-Schlagwörter als Orientierung für die KI
- eine bearbeitbare Liste bevorzugter Schreibweisen

---

## Bilder

Das Plugin unterstützt:

- ein Beitragsbild
- zusätzliche Bilder
- Galerie- bzw. Slider-Ausgabe bei mehreren Zusatzbildern

Ein Beitragsbild wird empfohlen, weil es je nach Theme in Übersichten, Teasern oder Social-Vorschauen verwendet werden kann.

---

## Formatierung

Der Beitragstext kann mit einer bewusst begrenzten Markdown-Auswahl strukturiert werden. Beim Speichern wandelt das Plugin diese Formatierungen in passende Gutenberg-Blöcke oder sicheres HTML um:

- Überschriften von `#` bis `######`
- Aufzählungen und nummerierte Listen
- Zitate und horizontale Trennlinien
- einfache Tabellen
- Links und Inline-Code
- Fett- und Kursivschrift

Markdown-Bilder, Codeblöcke und freie HTML-Ausgabe sind nicht vorgesehen. Titel werden grundsätzlich von Markdown und HTML bereinigt.

---

## Benachrichtigungen

Bei neuen Einreichungen können ausgewählte Nutzer per E-Mail informiert werden. Optional kann auch der einreichende Autor eine Benachrichtigung erhalten.

Die Mail enthält eine strukturierte Vorschau mit:

- Titel
- Autor
- Kategorie
- Schlagwörtern
- Textauszug
- Inhalt
- Bildinformationen

---

## KI-Protokoll

Das KI-Protokoll zeigt durchgeführte KI-Überarbeitungen mit:

- Zeitpunkt
- Nutzer
- Modell
- Stilgruppe
- Originaltitel
- optimiertem Titel
- Originalinhalt
- optimiertem Inhalt
- Textauszug

Details werden als Popup angezeigt. Auf kleinen Bildschirmen ist die Ansicht mobil nutzbar.

In den Einstellungen kann festgelegt werden, wie viele Einträge aufbewahrt werden. Standard sind 100, maximal sind 500 möglich. Beim Erreichen der Grenze werden die ältesten Einträge automatisch entfernt.

---

## Rechte und Rollen

Das Plugin nutzt eigene Capabilities:

| Capability | Bedeutung |
|------------|-----------|
| `beitragseinreichung_submit` | Formular öffnen und Beiträge einreichen |
| `beitragseinreichung_settings` | Plugin-Einstellungen öffnen |
| `beitragseinreichung_admin` | Erweiterte Adminfunktionen, z. B. Protokolle löschen |

Die Rechte können z. B. mit dem WordPress-Plugin **Members** verwaltet werden.

---

## Voraussetzungen

- WordPress 5.8 oder neuer
- PHP 7.4 oder neuer
- Gutenberg-kompatible Beitragsausgabe
- Optional: OpenAI API-Key für KI-Funktionen

---

## Installation

1. Plugin-Ordner nach `/wp-content/plugins/` kopieren oder als ZIP installieren.
2. Plugin im WordPress-Backend aktivieren.
3. Benutzerrechte vergeben.
4. Standard-Kategorie und Benachrichtigungen einstellen.
5. Optional OpenAI API-Key hinterlegen.
6. Optional Stilgruppen und Schlagwortoptionen pflegen.

---

## OpenAI API-Key

Der API-Key kann in den Plugin-Einstellungen gespeichert oder bevorzugt in der `wp-config.php` definiert werden:

```php
define('OPENAI_API_KEY', 'dein-api-key-hier');
```

Wenn `OPENAI_API_KEY` gesetzt ist, verwendet das Plugin diesen Wert bevorzugt. Der Key wird dann nicht in den Einstellungen überschrieben.

Nach dem Speichern der Einstellungen wird die Verbindung automatisch getestet. Schlägt der Test fehl, bleibt die bisherige KI-Einstellung zunächst erhalten; ein Administrator kann anschließend bewusst entscheiden, ob die KI deaktiviert werden soll.

---

## Qualität und Prüfungen

Für die lokale Entwicklung werden Composer-Tools genutzt:

```bash
composer install
composer run check
```

`composer run check` prüft Composer-Metadaten, bekannte Sicherheitsprobleme in Abhängigkeiten, PHP-Syntax, WordPress-Coding-Standards und die lokalen Entwicklungstests. Derselbe Ablauf wird bei Pushes und Pull Requests über GitHub Actions mit PHP 7.4 und PHP 8.3 ausgeführt.

Ein bereinigtes Release-ZIP ohne lokale Konfiguration und Entwicklungswerkzeuge wird erstellt mit:

```bash
composer run build:release
```

---

## Dokumentation

Weitere Informationen stehen im GitHub-Wiki:

- [Installation und Einrichtung](https://github.com/jan-erbert/ai-beitragseinreichung/wiki/Installation)
- [Formular-Übersicht](https://github.com/jan-erbert/ai-beitragseinreichung/wiki/Formular-%C3%9Cbersicht)
- [Beitrag einreichen](https://github.com/jan-erbert/ai-beitragseinreichung/wiki/Beitrag-einreichen)
- [Gute Beiträge schreiben](https://github.com/jan-erbert/ai-beitragseinreichung/wiki/Gute-Beitr%C3%A4ge)
- [KI-Funktionen](https://github.com/jan-erbert/ai-beitragseinreichung/wiki/KI-Funktionen)
- [Einstellungen](https://github.com/jan-erbert/ai-beitragseinreichung/wiki/Einstellungen)
- [Stilgruppen](https://github.com/jan-erbert/ai-beitragseinreichung/wiki/Stilgruppen)
- [Benutzerrechte und Rollen](https://github.com/jan-erbert/ai-beitragseinreichung/wiki/Benutzerrechte)
- [Protokoll und Fehlersuche](https://github.com/jan-erbert/ai-beitragseinreichung/wiki/Protokoll)
- [Entwicklung und Roadmap](https://github.com/jan-erbert/ai-beitragseinreichung/wiki/Entwicklung)

Repository: [jan-erbert/ai-beitragseinreichung](https://github.com/jan-erbert/ai-beitragseinreichung)

---

## Hinweise

- Inhalte werden nur an die OpenAI API gesendet, wenn die KI-Funktion aktiv genutzt wird.
- KI-Ausgaben sollten vor der Veröffentlichung geprüft werden.
- API-Nutzung kann Kosten verursachen.
- Zugangsdaten und API-Keys gehören nicht ins Repository.
