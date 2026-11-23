# Versteckt "mailto"-Links hinter einer Weiterleitung

Versteckt "mailto"-Links hinter einer Weiterleitung. Sollte der Linktext eine E-Mail-Adresse sein, kann dieser durch einen in den Einstellungen zu konfigurierenden Text ersetzt werden. Um zu erreichen das die E-Mail-Adresse im Link nicht ersetzt wird muss diese mit zwei @ Zeichen geschrieben werden.

Im Ersatztext können Insert-Tags verwendet werden, z. B. für mehrsprachige Seiten:

```
{{iflng::de}}E-Mail schreiben{{iflng::en}}Send e-mail{{iflng}}
```

Beispiel für die `@@`-Schreibweise: Linktext `info@@example.com` mit Ziel `mailto:info@example.com` – der Link wird verschlüsselt, als Text erscheint weiterhin `info@example.com` (verschleiert).

## Voraussetzungen

- PHP 8.1 oder höher (mit der Erweiterung `sodium`)
- Contao 5.3 bis 6.x

Funktioniert sowohl mit klassischen als auch mit modernen (Twig-)Seitenlayouts.
