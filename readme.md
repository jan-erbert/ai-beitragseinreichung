# 🧠 AI Beitragseinreichung

> Version: 1.2.0
> Autor: Jan Erbert  
> Letztes Update: 2026-06-06
> Lizenz: GPL2+

Ein leistungsstarkes WordPress-Plugin, das es berechtigten Nutzern ermöglicht, im Backend Beiträge mit Schlagwörtern, Kategorien, Bildern und optionaler KI-Unterstützung einzureichen. Die Inhalte werden mit einem benutzerdefinierten Status gespeichert und können anschließend durch Admins oder Redakteure geprüft werden.

---

## ✨ Übersicht Funktionen

- 📝 Beiträge einreichen im Adminbereich (inkl. Beitragsbild, Galerie, Tags & Kategorie)
- 🧠 Optional: Automatische Textverbesserung mit GPT-4 (via OpenAI API)
- 🔧 Stilvorgaben und Hinweise für die KI definierbar
- 💬 Eigene Protokollierung aller KI-Optimierungen
- 📬 E-Mail-Benachrichtigung an definierbare Admins + optional an den Autor
- 🔐 Custom Post Status: „in Verarbeitung“ bis zur Freigabe
- 📚 Gutenberg-kompatible Blockausgabe mit Markdown-zu-HTML-Konvertierung
- 🧾 Optionaler Textauszug (manuell oder per KI generierbar)

---

## 🔧 Features

- 📝 **Backend-Formular zur Beitragseinreichung**  
  Nutzer mit Berechtigung können neue Beiträge inklusive:

  - Titel & Inhalt
  - Kategorie & Schlagwörter
  - Beitragsbild & Galerie
  - KI-Optimierung (optional)
  - Textauszug: manuell oder automatisch durch KI generiert
    einreichen.

- 📦 **Custom Post Status: in_verarbeitung**
  Alle eingereichten Beiträge erhalten zunächst den Status in_verarbeitung. So können Admins sie prüfen, bevor sie veröffentlicht werden.

- 🤖 **OpenAI GPT-4 Integration (optional)**  
  Inhalte können automatisch stilistisch optimiert werden:
  - Auswahl von Stilgruppen
  - Freier Hinweistext für die KI
  - Unterstützung für **Markdown**, Absätze, Listen, Hervorhebungen etc.
  - Speicherung als saubere Gutenberg-Blöcke
  - Klassischer WP Editor (folgt!)

🛠️ **Eigene Rollen & Berechtigungen**  
Es lassen sich folgende Rechte vergeben:

- `beitragseinreichung_submit` – Beiträge einreichen, KI-Protokoll ansehen
- `beitragseinreichung_settings` – Zugriff auf die Plugin-Einstellungen
- `beitragseinreichung_admin` – Erweiterte Adminfunktionen (API-Key verwalten, Modellwahl, KI-Protokoll löschen etc.)

  🔐 **Integration mit dem Plugin „Members“**  
   Die Rechte des Plugins sind vollständig kompatibel mit dem beliebten WordPress-Plugin [Members](https://de.wordpress.org/plugins/members/). Dort erscheinen sie unter der separaten Gruppe **„AI Beitragseinreichung“**.

  👉 **So funktioniert’s:**

  1. Installiere und aktiviere das Plugin „Members“.
  2. Gehe zu **Benutzer → Rollen** und bearbeite eine bestehende Rolle (z. B. „Autor“) oder erstelle eine neue.
  3. Aktiviere gezielt die gewünschten Rechte wie `beitragseinreichung_submit`.
  4. Weise die Rolle den entsprechenden Benutzern zu.

  Damit kannst du exakt steuern, wer was im Einreichungsprozess darf – z. B. Autoren, die Beiträge nur einreichen können, oder Admins mit Zugriff auf alle Einstellungen und Logs.

- 📬 **Benachrichtigungen per E-Mail**

  - E-Mail an ausgewählte Admins bei neuen Beiträgen
  - Optional auch Benachrichtigung an den Autor selbst

- 📑 **KI-Protokoll**  
  Übersicht aller durchgeführten Optimierungen inklusive:

  - Vorher/Nachher-Vergleich von Titel & Inhalt
  - Zeitstempel, Autor & Modell
  - Genutzte Stilgruppe
  - Admins können Einträge löschen

- 🧾 **Textauszug (optional)**

  - Der Beitrag kann einen Kurztext (Excerpt) enthalten
  - Wahlweise manuell oder automatisch durch die KI generiert
  - Ein-/Ausblendbar über die Plugin-Einstellungen
  - Wird im Beitrag gespeichert und im KI-Protokoll dokumentiert

- 📶 **OpenAI API-Statusanzeige**
  - Verbindungstest beim Speichern der Einstellungen
  - Manuell auslösbarer Test
  - Statusanzeige mit Zeitstempel

---

## ✅ Voraussetzungen

- WordPress 5.8 oder höher
- PHP 7.4 oder höher
- Nutzung von Gutenberg Editor für Beiträge (Standard WP Editor folgt)
- Optional: OpenAI API-Key für GPT-Integration

---

## 🚀 Installation

1. Plugin-Ordner `ai-beitragseinreichung` in `/wp-content/plugins/` kopieren.
2. Plugin im Backend aktivieren.
3. Optional: OpenAI API-Key in den Einstellungen hinterlegen.
4. Benutzerrechte zuweisen.
5. Stilgruppen & Standardkategorie definieren.

---

## 🔐 Hinweis zum API-Key

Der OpenAI API-Key kann entweder direkt im Plugin-Backend hinterlegt oder alternativ sicher in der wp-config.php definiert werden.
Dazu füge folgende Zeile hinzu:

```php
define('OPENAI_API_KEY', 'dein-api-key-hier');
```

👉 Wenn diese Konstante gesetzt ist, wird der API-Key im Backend nicht angezeigt oder verändert und das Plugin verwendet ausschließlich den hinterlegten Wert aus der Konfigurationsdatei.

---

## 📌 Hinweise

Die KI-Ausgabe ist auf stilistische Korrektur optimiert, keine Faktenprüfung!

Der Gutenberg-kompatible HTML-Output unterstützt Überschriften, Absätze, Listen, Fett/Kursiv und Links.

Die Nutzung der OpenAI API kann kostenpflichtig sein. Ein Soft-/Hardlimit kann im OpenAI-Dashboard definiert werden!

---

ℹ️ Datenschutz-Hinweis
Dieses Plugin bietet die Option, Inhalte automatisch durch künstliche Intelligenz (OpenAI, z. B. GPT-4) stilistisch verbessern zu lassen. Dabei werden vom Nutzer eingegebene Texte (Titel und Inhalt) an die OpenAI API (USA) übermittelt.

Ob und wie diese Funktion genutzt wird, entscheidet der Beitragseinreicher individuell. Es erfolgt keine automatische Übertragung ohne Zustimmung.

Bitte beachte:

Die Nutzung der KI-Funktion kann unter Umständen datenschutzrechtliche Relevanz haben.

Eine entsprechende Ergänzung in der Datenschutzerklärung deiner Webseite kann notwendig sein.

Weitere Informationen zur Datenverarbeitung durch OpenAI: [OpenAI Privacy](https://openai.com/privacy)

Hinweis: Für die rechtliche Bewertung und Gestaltung deiner Datenschutzerklärung bist du als Webseitenbetreiber selbst verantwortlich.

---

### 🎨 Anpassbarer Stil

Die KI-Optimierung erfolgt **nicht automatisch**, sondern orientiert sich an deinen Stilvorgaben:

- ✍️ **Grundstil**: Eine globale Vorgabe wie z. B. _„freundlich, sachlich, sportlich“_ – wird bei jeder Optimierung berücksichtigt.
- 🗂️ **Stilgruppen**: Für verschiedene Beitragstypen lassen sich eigene Stile definieren, z. B. für Berichte, Einladungen oder Artikel.

🔧 Diese Vorgaben steuerst du direkt in den Plugin-Einstellungen und kannst sie jederzeit bearbeiten.

💡 So lassen sich auch unterschiedliche Kommunikationsstile für verschiedene Zielgruppen einfach umsetzen.

---

## 📘 Beispiele

### 🖋️ Eingabe im Formular (ohne KI-Optimierung)

```text
Ergebnisse vom Lauf in Mainz

Max Mustermann lief 12:00.
Maximilia Musterfrau 14:30.
```

### 🧱 Ausgabe im Beitrag (automatisch umgewandelt zu Gutenberg-kompatiblen Blöcken)

```text
<!-- wp:paragraph -->
<p>🏃‍♂️ Erfolgreicher Stadtlauf in Mainz!</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p><strong>Max Mustermann</strong> – 12:00 min<br>
<strong>Maximilia Musterfrau</strong> – 14:30 min</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph -->
<p>Herzlichen Glückwunsch an alle Teilnehmenden für ihre großartigen Leistungen! 🎉</p>
<!-- /wp:paragraph -->
```
