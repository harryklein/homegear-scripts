# homegear-scripts
Für den Zugriff auf einiger Homematic-Gerätschaften wurden ein paar Skripte erstellt.

Getestet wurde mit den folgenden Homematic-Geräten:

- HM-ES-PMSw1-Pl
- HM-WDS10-TH-O
- HM-CC-TC
- HM-Sec-SC

# Installation
Die Installation erfolgt zur Zeit manuell.



# Beschreibung der Skripte

## getAllInfo.php
Gibt allgemeine Informationen (Id, Name, Type und Firmware) zu allen bekannten Geräten aus bzw. alle aktuellen Werte.    
```
Aufruf [--id ID]|[--address ADDRESS]|[--all]|[--list]|[--help]
	--list           : Listet alle bekannten Geräte mit Id, Name, Type, Firmware und Name auf
	--id ID          : Ausgabe aller Details eines Gerätes mit der Id ID
	--address ADDRESS: Ausgabe aller Details eines Gerätes mit der Adresse ADDRESS
	--all            : Ausgabe Details aller bekannten Geräte
	--help           : Diese Hilfe
```



## getData.php  

## homematic-discovery.php
