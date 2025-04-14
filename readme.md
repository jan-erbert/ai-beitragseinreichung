# 🧠 AI Beitragseinreichung

> Version: 1.0
> Autor: Jan Erbert  
> Letztes Update: 2025-04-14  
> Lizenz: GPL2+

Ein leistungsstarkes WordPress-Plugin, das es berechtigten Nutzern ermöglicht, im Backend Beiträge mit Schlagwörtern, Kategorien, Bildern und optionaler KI-Unterstützung einzureichen. Die Inhalte werden mit einem benutzerdefinierten Status gespeichert und können anschließend durch Admins oder Redakteure geprüft werden.

---

## 🔧 Features

- 📝 **Backend-Formular zur Beitragseinreichung**  
  Nutzer mit Berechtigung können neue Beiträge inklusive:
  - Titel & Inhalt
  - Kategorie & Schlagwörter
  - Beitragsbild & Galerie
  - KI-Optimierung (optional) einreichen.

- 🤖 **OpenAI GPT-4 Integration (optional)**  
  Inhalte können automatisch stilistisch optimiert werden:
  - Auswahl von Stilgruppen
  - Freier Hinweistext für die KI
  - Unterstützung für **Markdown**, Absätze, Listen, Hervorhebungen etc.
  - Speicherung als saubere Gutenberg-Blöcke
  - Klassischer Editor (folgt!)

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
  - Admins können Einträge löschen

- 📶 **OpenAI API-Statusanzeige**  
  - Verbindungstest beim Speichern der Einstellungen
  - Manuell auslösbarer Test
  - Statusanzeige mit Zeitstempel

---

## 🚀 Installation

1. Plugin-Ordner `ai-beitragseinreichung` in `/wp-content/plugins/` kopieren.
2. Plugin im Backend aktivieren.
3. Optional: OpenAI API-Key in den Einstellungen hinterlegen.
4. Benutzerrechte zuweisen.
5. Stilgruppen & Standardkategorie definieren.

---

## ✅ Voraussetzungen

- WordPress 5.8 oder höher
- PHP 7.4 oder höher
- Optional: OpenAI API-Key für GPT-Integration

---

## 📘 Beispiele

**Eingabe im Formular:**

```text
🏃‍♂️ Erfolgreich beim Lauf!

**Name 1** – 12:00 min  
**Name 2** – 14:30 min
