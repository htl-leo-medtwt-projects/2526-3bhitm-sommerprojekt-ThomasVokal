# Projektname: CarFixFast

## USP
**Digitaler Werkstattpass:** Kunden erhalten nach jeder Reparatur einen digitalen Eintrag in ihrer Historie. Das dient als lückenloses, fälschungssicheres Serviceheft, das den Wiederverkaufswert des Autos steigert und dem Kunden zeigt, wann der nächste Ölwechsel ansteht.

## UI & UX | Projekt aus Sicht des Users
* **Landingpage:** Begrüßung mit Fokus auf Vertrauen. Übersicht der Werkstatt-Öffnungszeiten und direkte Verlinkung zu den Top-Leistungen (z.B. Pickerl, Reifenwechsel).
* **Leistungs-Katalog:** Eine strukturierte Liste aller Services. Jeder Service hat eine eigene Detailseite mit Beschreibung, geschätzter Dauer und einem "Jetzt Termin anfragen"-Button.
* **Ersatzteil-Suche:** Ein einfacher Shop-Bereich, in dem Kunden nach Bauteilen (Auspuff, Bremsen, Filter) filtern können, um diese vorab zu reservieren oder die Preise zu prüfen.
* **Kunden-Dashboard (Login erforderlich):**
    * **Meine Garage:** Hinterlegte Infos zum eigenen Fahrzeug (Marke, Modell, Baujahr).
    * **Termin-Status:** Anzeige, ob der Mechaniker gerade am Auto arbeitet oder ob es fertig ist.
    * **Historie:** Liste aller vergangenen Reparaturen und gekauften Teile.

## Coder Plan | Projekt aus Sicht des Entwicklers
### Technologien & technische Umsetzung
* **Frontend:** HTML für die Struktur, CSS für das Design (Responsiv für Handy/Desktop) und JavaScript für interaktive Elemente wie Formular-Validierung oder Filter im Shop.
* **Backend:** PHP für die Logik (Verarbeitung von Formularen, Datenbank-Abfragen).
* **Account-System:** Login-System mit PHP-Sessions. Registrierung speichert User-Daten in der Datenbank; Login prüft Email und Passwort.
* **Datenübertragung:** Formulare werden via POST an PHP-Skripte gesendet, um Daten sicher in die Datenbank zu schreiben.

### Grobe Datenbankstruktur
1.  **Dienstleistungen:** ID, Name, Beschreibung, Preis, Kategorie
2.  **Ersatzteile:** ID, Teil_Name, Marke, Modell_Kompatibilität, Preis, Lagerbestand
3.  **Termine:** ID, User_ID, Service_ID, Datum, Uhrzeit, Status (Offen/In Arbeit/Fertig)
4.  **User:** ID, Vorname, Nachname, Email, Passwort, Telefon
5.  **Fahrzeuge:** ID, User_ID, Marke, Modell, Kennzeichen, Kilometerstand
6.  **Bestellungen:** ID, User_ID, Teil_ID, Menge, Gesamtpreis, Datum