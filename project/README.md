
# Projekt-Ordner

Integriere in diesen Bereich deine gesamte praktische Projektstruktur!
Jeglicher Fremdcode ist als solcher im Projekt zu kennzeichnen.
Ergänze sprechende Kommentare in deinem Code nur dort, wo sie wirklich helfen.

## Datenbank-Anbindung

### 1. Setup für Docker (docker-compose.yml)
✓ **Bereits vorkonfiguriert!**
- Datenbank: `carfixfast` 
- User: `root` mit Passwort `rootpassword` (aus docker-compose.yml)
- Service Name: `db_server` (im Docker-Netzwerk)
- Diese Werte sind in `.env` schon gespeichert

**Was du tun musst:**
1. Stelle sicher, dass du `database/carfix_schema.sql` in phpMyAdmin (http://localhost:8081) importiert hast
2. Mehr nicht nötig! Fahrzeugdaten werden automatisch in die DB gespeichert.

### 2. Debug: Sind Daten in der DB?
- Öffne phpMyAdmin: http://localhost:8081
- Login: Benutzer `root`, Passwort `rootpassword`
- Gehe zu: `carfixfast` → `vehicles` Tabelle
Dort sollten deine Fahrzeugdaten sichtbar sein, sobald du sie im Dashboard speicherst

### 3. Fehlersuche
1. Öffne im Browser: `http://localhost:8080/db_test.php` (zeigt ob Verbindung OK ist)
2. Öffne Browser DevTools (F12) → Tab "Netzwerk"
3. Gib im Dashboard Fahrzeugdaten ein → "Daten speichern"
4. Rechtsklick auf `save_profile.php` Request → "Antwort" anschauen
5. Falls Fehler: Hier steht konkret was schiefgeht
