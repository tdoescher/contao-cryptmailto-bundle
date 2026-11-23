# Versteckt "mailto"-Links hinter einer Weiterleitung

Versteckt "mailto"-Links hinter einer Weiterleitung. Sollte der Linktext eine E-Mail-Adresse sein, kann dieser durch einen in den Einstellungen zu konfigurierenden Text ersetzt werden. Um zu erreichen das die E-Mail-Adresse im Link nicht ersetzt wird muss diese mit zwei @ Zeichen geschrieben werden.

Im Ersatztext können Insert-Tags verwendet werden, z. B. für mehrsprachige Seiten:

```
{{iflng::de}}E-Mail schreiben{{iflng::en}}Send e-mail{{iflng}}
```

### `@@`-Schreibweise

- **`@@` im Linktext** (z. B. `info@@example.com`): Die Adresse bleibt sichtbar (von Contao verschleiert) und wird nicht durch den Ersatztext ersetzt.
- **`@@` im Linkziel** (z. B. `mailto:info@@example.com`): Der Link wird nicht über die Weiterleitung geführt, sondern bleibt ein normaler, von Contao verschleierter `mailto:`-Link.

Markup innerhalb des Links (z. B. Icons per `{{svgicon::…}}` oder `<span>`-Elemente) bleibt erhalten; ersetzt werden nur die Textstellen mit der E-Mail-Adresse.

## Voraussetzungen

- PHP 8.1 oder höher (mit der Erweiterung `sodium`)
- Contao 5.3 bis 6.x

Funktioniert sowohl mit klassischen als auch mit modernen (Twig-)Seitenlayouts.
