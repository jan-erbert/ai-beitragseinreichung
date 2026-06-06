# AGENTS.md

Projektspezifische Arbeitsregeln fuer das WordPress-Plugin **AI Beitragseinreichung**.

Diese Datei ergaenzt die globale `AGENTS.md`. Allgemeine Regeln zu kleinen Schritten, Git, Sicherheit, Tests, Secrets und sauberem Arbeiten gelten weiterhin und werden hier nicht wiederholt.

---

## 1. Projektkontext

- Das Projekt ist ein WordPress-Plugin fuer Beitragseinreichungen im Adminbereich.
- Einstiegspunkt ist `wp-form.php`.
- Fachmodule liegen unter `includes/`.
- Frontend-/Admin-Darstellung, Post-Verarbeitung, KI-Anbindung, Logging und Formatting sollen getrennt bleiben.
- Bestehende WordPress-Konventionen haben Vorrang vor eigenen Framework-Strukturen.

Wichtige Bereiche:

```text
includes/
+-- admin/       Admin-Assets und Einstellungsseite
+-- ai/          Modellprofile, OpenAI-Client, KI-Protokoll
+-- core/        Capabilities und Beitragsverarbeitung
+-- formatting/  Gutenberg-/Markdown-Formatierung
+-- frontend/    Formularausgabe im Adminbereich
```

---

## 2. Architektur und Dateigrenzen

- `wp-form.php` soll langfristig moeglichst schlank bleiben.
- Neue Logik bevorzugt in ein passendes Modul unter `includes/` legen.
- Keine neue Unterstruktur einfuehren, wenn ein vorhandener Ordner passt.
- Admin-Menues, Hooks und Registrierungen nur dort verschieben, wo die Abhaengigkeiten klar bleiben.
- Formularanzeige und Formularverarbeitung nicht unnoetig vermischen.
- KI-Client, Modellprofile und KI-Logging klar getrennt halten.
- Gemeinsame Hilfsfunktionen nur dann auslagern, wenn sie wirklich mehrfach genutzt werden.

---

## 3. WordPress-Sicherheit

Bei jeder Aenderung an Formularen, AJAX-Endpunkten oder gespeicherten Optionen pruefen:

- Nonces mit `wp_nonce_field()`, `wp_verify_nonce()` oder `check_ajax_referer()`.
- Capabilities mit den plugin-eigenen Rechten pruefen:
  - `beitragseinreichung_submit`
  - `beitragseinreichung_settings`
  - `beitragseinreichung_admin`
- Eingaben passend sanitizen, z. B. `sanitize_text_field()`, `sanitize_textarea_field()`, `intval()`, `array_map()`.
- Ausgaben passend escapen, z. B. `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`.
- Keine API-Keys, Tokens oder vollstaendige Secret-Werte ausgeben.
- Admin-AJAX-Endpunkte immer mit Capability-Check und Nonce absichern.

---

## 4. OpenAI- und KI-Logik

- API-Key-Logik nicht unnoetig veraendern.
- `OPENAI_API_KEY` aus `wp-config.php` hat Vorrang vor gespeicherten Optionen.
- Modellwerte ueber `beitrag_normalize_ai_model()` normalisieren.
- Neue Modelle nur ueber `includes/ai/ai-models.php` ergaenzen.
- KI-Prompts muessen klar sagen, dass keine Inhalte erfunden werden duerfen.
- Fehler bei API-Aufrufen sollen nachvollziehbar behandelt und nicht still verschluckt werden.
- KI-Protokolle duerfen keine Secrets enthalten.

---

## 5. PHP-Stil

- PHP-Funktionen bekommen kurze deutsche Docblocks, wenn sie neu angelegt oder wesentlich geaendert werden.
- WordPress-Funktionen und Hooks im bestehenden Stil halten.
- Fruehe Rueckgaben sind bevorzugt, wenn sie Verschachtelung reduzieren.
- Keine produktiven `var_dump()`, `print_r()` oder Debug-Ausgaben im finalen Code.
- Inline-Kommentare nur fuer echte Fachlogik oder nicht offensichtliche Randfaelle.
- PHP-Dateien mit `defined('ABSPATH') || exit;` schuetzen, sofern sie direkt ladbare Pluginmodule sind.
- Projektlokale PHP-Tools werden ueber Composer verwaltet.
- `composer run lint` prueft PHP-Syntax.
- `composer run phpcs` prueft projektlokal definierte PHPCS-Regeln.
- `composer run phpcbf` darf fuer automatisch reparierbare PHPCS-Befunde genutzt werden, aber nicht als blinde Gesamtformatierung fuer das ganze historische Projekt.
- Das aktuelle `phpcs.xml.dist` ist bewusst auf Sicherheit, WordPress-relevante Checks und Kompatibilitaet fokussiert; reine Stilregeln werden nur schrittweise verschaerft.

---

## 6. JavaScript und CSS

- Bestehende Admin-UI nicht ohne Auftrag grundlegend umgestalten.
- JavaScript fuer Formular- oder Einstellungslogik moeglichst schrittweise aus Inline-Bloecken herausloesen, wenn daran gearbeitet wird.
- Bestehende IDs und Namen von Formularfeldern nicht ohne Migrationsgrund aendern.
- CSS nur gezielt erweitern; keine globalen WordPress-Admin-Styles unnoetig ueberschreiben.
- Externe CDN-Abhaengigkeiten nicht erweitern, ohne kurz zu begruenden, warum sie noetig sind.

---

## 7. Plugin-Daten und Optionen

- Bestehende Optionsnamen beibehalten, solange keine Migration vorgesehen ist.
- Neue Optionen klar mit Prefix `beitragseinreichung_` benennen.
- Gespeicherte Arrays strukturiert halten und beim Lesen defensiv pruefen.
- Aenderungen an Capabilities, Optionen oder Post-Status muessen rueckwaertskompatibel bleiben, sofern nichts anderes vereinbart ist.

---

## 8. Dokumentation und Versionierung

- Bei relevanten Feature- oder Bugfix-Aenderungen `changelog.md` aktualisieren.
- Plugin-Version in `wp-form.php` und Dokumentation konsistent halten, wenn eine Versionsaenderung Teil der Aufgabe ist.
- `readme.md` nur aktualisieren, wenn sich Nutzerverhalten, Installation, Konfiguration oder Featureumfang aendern.
- Keine rechtlichen, Datenschutz- oder Compliance-Aussagen ergaenzen, ohne dass sie fachlich belegt oder ausdruecklich gewuenscht sind.

---

## 9. SFTP und lokale Entwicklungsumgebung

- SFTP-Zugangsdaten gehoeren nicht ins Repository.
- `.vscode/sftp.json` nur anlegen, wenn klar ist, dass sie lokal ignoriert bleibt oder keine Secrets enthaelt.
- Fuer teilbare VS-Code-Konfigurationen nur unkritische Einstellungen versionieren.
- Deploy-/Sync-Einstellungen duerfen keine produktiven Daten loeschen oder ueberschreiben, ohne dass dies ausdruecklich bestaetigt wurde.
- Nach relevanten Codeaenderungen den Serverstand mitdenken und bei Bedarf synchronisieren.
- Sync-Richtung ist ausschliesslich lokal nach remote. Keine Remote-Dateien als Quelle fuer lokalen Code verwenden, ausser der Nutzer fordert dies ausdruecklich.
- Beim FTP-Sync immer lokale Ausschluesse beachten: `.git`, `.vscode`, `.agents`, `.codex`, `vendor`, `node_modules`, `wiki`, Logs, ZIPs und lokale Cache-Dateien.
- Secrets aus `.vscode/sftp.json` niemals ausgeben oder committen.
- Nach einem manuellen FTP-Sync eine kurze Kontrolle melden: Anzahl Uploads, Loeschungen, fehlende Dateien und extra Remote-Dateien.

---

## 10. Validierung

Nach Aenderungen passend pruefen:

```bash
git status
git diff
composer run lint
composer run phpcs
```

Wenn Composer-Abhaengigkeiten fehlen, zuerst ausfuehren:

```bash
composer install
```

Wenn `php` oder Composer lokal nicht verfuegbar ist, dies offen nennen und mindestens Diff sowie betroffene Kontrollfluesse manuell pruefen.

Bei WordPress-spezifischen Aenderungen zusaetzlich im Adminbereich testen:

- Plugin aktivieren.
- Einstellungsseite oeffnen.
- Beitragseinreichungsformular oeffnen.
- Formular mit und ohne KI testen, falls betroffen.
- AJAX-Funktionen testen, falls betroffen.
