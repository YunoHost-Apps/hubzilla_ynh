[h1][b]$Projectname-Features[/b][/h1]

[h1]$Projectname kurz zusammengefasst[/h1]

tl;dr

$Projectname stellt verteiltes Web-Publishing und soziale Kommunikation mit [b]dezentraler Rechteverwaltung[/b] zur Verfügung.

Aber was genau ist eine dezentrale Rechteverwaltung? Sie gibt mir die Möglichkeit, etwas auf meiner Website (Fotos, Medien, Dateien, Webseiten etc.) mit bestimmten Personen auf anderen Websites zu teilen – aber nicht unbedingt mit [i]allen[/i] auf diesen Websites. Und: Sie brauchen kein Konto auf meiner Website und müssen sich auf meiner Website nicht extra einloggen, um sich die Dinge anzusehen, die ich mit ihnen geteilt habe. Sie haben ein Konto auf ihrer Heimat-Website, und „Magic Authentication“ zwischen den Websites besorgt den Rest. Da das Netzwerk dezentral aufgebaut ist, gibt es auch keinen einzelnen Betreiber des Netzwerks, der an der Rechteverwaltung vorbei alles sehen kann.

$Projectname kombiniert viele Features von tradionellen Blogs, sozialen Netzwerken und Medien, Content-Management-Systemen und persönlichem Cloud-Speicher auf einer einfach zu nutzenden Plattform. Jeder Hub (Web-Server) im Grid kann isoliert operieren oder sich mit anderen Hubs zu einem Super-Netzwerk vereinen. Die Kontrolle über die Privatsphäre hat immer derjenige, der die Inhalte veröffentlicht.

$Projectname ist eine Open-Source Webserver-Applikation, geschrieben ursprünglich für PHP/MySQL. Mit minimaler Erfahrung als Admin ist sie leicht zu installieren. Sie kann auch durch Plugins und Themes und weitere Angebote von Drittanbietern erweitert werden.

[h1][b]$Projectname-Features[/b][/h1]

$Projectname ist ein Allzweck-Web-Publishing- und Kommunikationsnetzwerk mit einigen einzigartigen Features. Es wurde für eine große Bandbreite von Nutzern entwickelt, von Nutzern sozialer Netzwerke über technisch nicht interessierte Blogger bis hin zu PHP-Experten und erfahrenen Systemadministratoren.

Diese Seite listet einige der Kern-Features von $Projectname auf, die in der offiziellen Distribution enthalten sind. Wie immer bei freier Open-Source-Software sind den Möglichkeiten keine Grenzen gesetzt. Beliebige Erweiterungen, Addons, Themes und Konfigurationen sind möglich.

[h2]Entwickelt für Privatsphäre und Freiheit[/h2]

Eines der Design-Ziele von $Projectname ist einfache Kommunikations über das Web, ohne die Privatsphäre zu vernachlässigen, wenn die Nutzer das wünschen. Um dieses Ziel zu erreichen, verfügt $Projectname über einige Features, die beliebige Stufen des Privatsphäre-Schutzes ermöglichen:

[b]Beziehungs-Tool[/b]

Wenn Du in der $Projectname einen Kontakt hinzufügst (und das Beziehungs-Tool aktiviert hast), hast Du die Möglichkeit, einen „Grad der Freundschaft“ zu bestimmen. Bespiel: Wenn Du ein Blog eines Bekannten hinzufügst, könntest Du ihm den Freundschaftsgrad „Bekannte“ (Acquaintances) geben.

Wenn Du aber den privaten Kanal eines Freundes hinzufügst, wäre der Freundschaftsgrad „Freunde“ vermutlich passender.

Wenn Du allen Kontakten solche Freundschaftsgrade zugeordnet hast, kannst Du mit dem Beziehungs-Tool, das (sofern aktiviert) oben auf Deiner Matrix-Seite erscheint, bestimmen, welche Inhalte Du sehen willst. Indem Du die Schieberegler einstellst, legst Du fest, was angezeigt wird – nur Kanäle mit einem Freundschaftsgrad innerhalb des eingestellten Bereichs werden angezeigt

Das Beziehungs-Tool erlaubt blitzschnelles Filtern von großen Mengen Inhalt, gruppiert nach Freundschaftsgrad.

[b]Filter für Verbindungen[/b]

Du kannst ganz genau kontrollieren, was in Deinem Stream erscheint, wenn Du den optionalen „Filter für Verbindungen“ aktivierst. Dann kannst Du beim Bearbeiten einer Verbindung Kriterien festlegen, nach denen entschieden wird, ob einzelne Beiträge dieser Verbindung importiert werden sollen oder nicht (Einschluss oder Ausschluss möglich). Wurde ein Beitrag einmal importiert, wirst Du auch alle Kommentare dazu sehen, egal ob eines der Kriterien auf sie zutrifft oder nicht. Du könntest einzelne Wörter festlegen, die, wenn sie in einem Beitrag vorkommen, dafür sorgen, dass er geblockt oder eben nicht geblockt wird. Auch reguläre Ausdrüce können benutzt werden, genauso wie Hashtags oder sogar die Sprache, in der der Beitrag verfasst wurde.

[b]Zugriffsrechte[/b]

Wenn Du Inhalte mit anderen teilst, hast Du die Option, den Zugriff darauf einzuschränken. Wenn Du auf das Schloss unterhalb des Beitrags-Editors klickst, kannst Du auswählen, wer diesen Beitrag sehen darf, indem Du einfach auf die Namen klickst.

Diese Nachricht kann dann nur vom Absender und den eingestellten Empfängern betrachtet werden. Mit anderen Worten, sie erscheint nicht öffentlich auf einer Pinnwand.

Solche Zugriffsrechte gibt es bei Beiträgen, Fotos, Terminen, Webseiten, Chat-Räumen und Dateien.

[b]Ein Passwort für alle $Projectname-Server (Single Sign-on)[/b]

Zugriffsrechte funktionieren im gesamten Grid mit allen Kanälen. Die meisten Links, die innerhalb von $Projectname verlinken, enthalten Deine Identität (zid), so dass der Zielserver Dich direkt anmelden kann. Du kannst Dich aber auch so auf jedem $Projectname-Server mit Deiner $Projectname-Identität anmelden und erhältst dann Zugriff auf die Inhalte, die für Dich freigegeben sind.

Du loggst Dich nur einmal auf Deinem Heimat-Hub ein. Ab dann funktioniert die Authentifizierung gegenüber anderen $Projectname-Hubs „magisch“ von selbst.

[b]Dateiablage (Cloud) mit WebDAV-Zugriff[/b]

Du kannst in Deinem persönlichen Speicherbereich Dateien hochladen und ihn sogar als Verzeichnis von Deinem lokalen Betriebssystem anzeigen lassen (via WebDAV). Die Dateien können über Zugriffsrechte bestimmten $Projectname-Mitgliedern (und den Mitgliedern mancher anderer Netze) zugänglich gemacht oder auch komplett öffentlich zur Verfügung gestellt werden.

[b]Fotoalben[/b]

Stelle Deine Fotos online in Alben zur Verfügung. Auch hier kann der Zugriff über die Zugriffsrechte eingeschränkt werden.

[b]Terminkalender[/b]

Im eingebauten Terminkalender kannst Du Termine erstellen und verwalten. Auch hier greifen die Zugriffsrechte für andere. Termine können im vcalendar/iCal-Format importiert/exportiert und in Beiträgen mit anderen geteilt werden. Wenn Deine Kontakte ihren Geburtstag in ihr Profil eingetragen haben, werden diese Geburtstage automatisch zu Deinem Kalender hinzugefügt – mit entsprechender Anpassung der Zeitzone, so dass Du nie zu früh oder zu spät gratulierst. Termine werden normalerweise mit Teilnehmerzählern erstellt, so dass Deine Freunde und Verbindungen sofort zu- oder absagen können.

[b]Chat-Räume[/b]

Du kannst Chaträume erstellen und über die Zugriffsrechte nur bestimmten Nutzern öffnen. Die Nachrichten sind sicherer verschlüsselt als es normalerweise bei Jabber/XMPP, IRC und anderen Instant Messengern üblich ist. Über Plugins ist es aber auch möglich, andere IM-Dienste aus der $Projectname heraus zu nutzen.

[b]Erstellen von Webseiten[/b]

In $Projectname gibt es Werkzeuge für „Content Management“, mit denen Du einfache Webseiten erstellen kannst, aber auch komplexe Layouts, Menüs, Blöcke und Widgets. Auch hier greifen die Zugriffsrechte, so dass die entstandenen Seiten nur von denen betrachtet werden können, denen Du das Recht dazu eingeräumt hast.

[b]Apps[/b]

$Projectname-Mitglieder könnnen Apps erstellen und verteilen. Anders als bei anderen Systemen, bei denen man an den System-Anbieter gebunden ist, werden diese Apps komplett vom App-Anbieter kontrolliert, der auf Wunsch seine eigene Zugriffskontrolle und ein Bezahlsystem einbauen kann. Die meisten Apps in der $Projectname sind kostenlos. Sie sind sehr einfach und ohne große Programmierkenntnisse zu erstellen.

[b]Layout[/b]

Das Seiten-Layout basiert auf eine Beschreibungssprache namens Comanche. $Projectname ist selbst in Comanche-Layouts verfasst, die man verändern kann. Dadurch ist eine sehr starke Anpassung an die eigenen Bedürfnisse möglich, wie man sie so in Multi-User-Umgebungen normalerweise nicht findet.

[b]Lesezeichen[/b]

Du kannst Lesezeichen teilen, speichern und verwalten, direkt aus den Unterhaltungen mit anderen heraus.

[b]Verschlüsselung privater Nachrichten[/b]

Private Nachrichten werden verschlüsselt gespeichert. Das bietet keine absolute Sicherheit, erschwert aber einfaches Herumschnüffeln durch den Administrator oder Internet Provider.

Jeder $Projectname-Kanal hat seinen eigenes 4096-bit-RSA-Schlüsselpaar, das erzeugt wird, wenn der Kanal erstellt wird. Damit werden private Nachrichten und Beiträge mit eingeschränktem Empfängerkreis während der Übermittlung zu anderen Hubs geschützt.

Zusätzlich können Nachrichten mit Ende-zu-Ende-Verschlüsselung versehen werden, so dass weder $Projectname-Hub-Administratoren noch ISPs irgendetwas mitlesen können, solange sie nicht über das Passwort verfügen.

Komplett öffentliche Nachrichten werden weder in der Datenbank noch bei der Übertragung verschlüsselt (abgesehen ggfs. von SSL).

Private Nachrichten und Beiträge können gelöscht (zurückgezogen) werden, aber es kann natürlich nicht garantiert werden, dass der Empfänger sie nicht schon gelesen hat.

Alle Beiträge können mit einem „Verfallsdatum“ versehen werden. Zu diesem Zeitpunkt werden sie dann von den Servern der Empfänger gelöscht.

[b]Verbindung zu anderen Diensten[/b]

Neben Plugins, die das „crossposten“ zu diversen anderen Netzwerk erlauben, wird der Import von RSS/Atom-Feeds nativ unterstützt, auch, um mit diesen Inhalten spezielle Kanäle zu erstellen. Außerdem kann über das Diaspora-Protokoll mit Kontakten in den Netzwerken Friendica und Diaspora kommuniziert werden. Diese Unterstützung ist als experimentell eingestuft, da diese Netzwerke nicht die gleichen Möglichkeiten wie $Projectname in Sachen Privatsphäre und Verschlüsselung bieten, so dass Kommunikation mit ihnen zu Privatsphäreproblemen führen könnte.

Weiterhin wird OpenID auf experimenteller Ebene unterstützt und kann bei den Zugriffsrechten genutzt werden, um Inhalte für per OpenID authentifizierte Nutzer freizugeben. An dieser Funktion wird noch gearbeitet. Jeder $Projectname-Hub kann außerdem als OpenID-Provider dienen.

Die Inhalte von Kanälen können als Quellen für andere Kanäle dienen (wenn der Kanalinhaber das erlaubt), so dass Themen-Kanäle mit den Inhalten von zwei oder mehr Kanälen erstellt werden können.

[b]Sammlungen[/b]

„Sammlungen“ sind unsere Implementierung von Privatsphäregruppen, ähnlich den „Kreisen“ bei Google+ und den „Aspekten“ bei Diaspora. Sammlungen können zur Filterung der angezeigten Nachrichten genutzt werden (nur Threads anzeigen, die von einem Mitglied dieser Sammlung gestartet wurden), aber auch zum Setzen von Zugriffsrechten (bevor der Beitrag abgeschickt wird).

[b]Verzeichnisdienste[/b]

Wir stellen einfachen Zugriff auf ein Mitgliederverzeichnis zur Verfügung, samt einer dezentralen Möglichkeit, sich neue Kontakte basierend auf den eigenen vorschlagen zu lassen. Die Verzeichnis-Server sind normale $Projectname-Server, bei denen der Administrator sich entschieden hat, sie auch als Verzeichnis agieren zu lassen. Das benötigt mehr Ressourcen als eine normale $Projectname-Installation, deshalb ist das nicht voreingestellt. Die Verzeichnis-Server synchronisieren sich miteinander, so dass (abgesehen von einer gewissen Verzögerung bis zur nächsten Synchronisation) alle Verzeichnis-Server aktuelle Informationen über das gesamte Netzwerk bereitstellen können.

[b]TLS/SSL[/b]

$Projectname-Server, die TLS/SSL benutzen, verschlüsseln ihre Kommunikation vom Server zum Nutzer mit SSL. Nach den aktuellen Enthüllungen über das Umgehen von Verschlüsselung durch NSA, GHCQ und andere Dienste, sollte man jedoch nicht mehr davon ausgehen, dass diese Verbindungen nicht mitgelesen werden können. Private Kommunikation (nicht komplett öffentliche Beiträge) wird darüberhinaus zusätzlich verschlüsselt, bevor sie von einem Server zum anderen geschickt wird.

[b]Kanal-Einstellungen[/b]

Wenn ein Kanal erstellt wird, muss eine bestimmte Zugriffsrechte-Kategorie (z.B. öffentliches Forum oder privater Kanal für soziales Netzwerken) ausgewählt werden, die dafür sorgt, dass sinnvolle Privatsphäre-Einstellungen für diese Art von Kanal ausgewählt werden.

Wenn Du die Experten-Kategorie wählst, kannst Du detaillierte Zugriffseinstellungen für verschiedenste Aspekte der Kommunikation festlegen. Unter den „Sicherheits- und Privatsphäre-Einstellungen“ kann für jeden Punkt auf der linken Seite eine von 7-8 möglichen Optionen aus dem Menü gewählt werden. Daneben gibt es diverse weitere Einstellmöglichkeiten zum Thema Privatsphäre.

Die Optionen für die einzelnen Punkte (z.B., wer Deine normalen Beiträge sehen kann) sind:
[ul][*]Niemand außer Du selbst
[*]Nur die, denen Du es explizit erlaubst
[*]Angenommene Verbindungen
[*]Beliebige Verbindungen
[*]Jeder auf diesem Website
[*]Alle $Projectname-Nutzer
[*]Jeder authentifizierte
[*]Jeder im Internet[/ul]

[b]Private und öffentliche Foren[/b]

Foren sind Kanäle, in denen mehrere Nutzer als Autoren fungieren können; eine Nachricht eines entsprechend berechtigten Nutzers an das Forum wird an alle Foren-Mitglieder verteilt. Es gibt momentan zwei Arten, um auf diese Weise an ein Forum zu posten: 1) Direktes Posten auf der Kanal-Seite des Forums („wall-to-wall post“) oder 2) über @mention-Tags. Jeder kann Foren erstellen, und sie können für beliebige Zwecke genutzt werden. Das Kanal-Verzeichnis ermöglicht es, direkt nach öffentlichen Foren zu suchen. Private Foren können meist nur von den Mitgliedern beschickt und gelesen werden.

[b]Klone[/b]

Konten in der $Projectname werden auch als [i]nomadische Identitäten[/i] bezeichnet. Nomadisch, weil bei anderen Diensten die Identität eines Nutzers an den Server oder die Plattform gebunden ist, auf der er ursprünglich erstellt wurde. Ein Facebook- oder Gmail-Konto ist and diese Dienste gekettet. Er funktioniert nicht ohne Facebook.com bzw. Gmail.com.

Bei $Projectname ist das anders. Sagen wir, Du hast eine $Projectname-Indentität namens tina@$Projectnamehub.com. Die kannst Du auf einen anderen Server klonen, mit dem gleichen oder einem anderen Namen, zum Beispiel lebtEwig@Anderer$ProjectnameHub.info.

Beide Kanäle sind jetzt miteinander synchronisiert, das heißt, dass alle Kontakte und Einstellungen auf dem Klon immer die gleichen sind wie auf dem ursprünglichen Kanal. Es ist egal, ob Du eine Nachricht von dort aus oder vom Klon aus schickst. Alle Nachrichten sind in beiden Klonen vorhanden.

Das ist ein ziemlich revolutionäres Feature, wenn man sich einige Szenarien dazu ansieht:

[ul][*]Was passiert, wenn ein Server, auf dem sich Deine Identität befindet, plötzlich offline ist (sicher haben viele von Euch den Twitter-„Fail Whale“ gesehen und verflucht)? Ohne Klone ist der Nutzer nicht in der Lage zu kommunizieren, bis der Server wieder online ist. Mit Klonen loggst Du Dich einfach bei Deinem geklonten Kanal ein und lebst glücklich bis an Dein Ende.
[*]Der Administrator Deines $Projectname-Hubs kann es sich nicht länger leisten, seinen für alle kostenlosen Server zu bezahlen. Er gibt bekannt, dass der Server in zwei Wochen vom Netz gehen wird. Zeit genug, um Deine $Projectname-Kanäle auf andere Server zu klonen und somit Verbindungen und Freunde zu behalten.
[*]Was, wenn Dein Kanal staatlicher Zensur unterliegt? Dein Server-Admin könnte gezwungen werden, Dein Konto und alle damit verbundenen Kanäle und Daten zu löschen. Durch Klone bietet $Projectname Zensur-Resistenz. Wenn Du willst, kannst Du hunderte von Klonen haben, alle mit unterschiedlichen Namen und auf unterschiedlichen Hubs überall im Internet.[/ul]

$Projectname bietet interessante, neue Möglichkeiten in Bezug auf die Privatsphäre. Mehr dazu unter „Tipps und Tricks zur privaten Kommunikation“.

Klone unterliegen einigen Restriktionen. Eine vollständige Erklärung zum Klonen von Identitäten gibt es unter „Klone“.

[b]Mehrere Profile[/b]

Jeder Kanal kann beliebig viele Profile mit unterschiedlichen Informationen definieren. Dann kannst Du einstellen, wer von Deinen Kontakten welches Profil zu sehen bekommt. Das Default-Profil ist für alle anderen zu sehen und kann so auf nur wenige Informationen beschränkt werden, während Freunde und Bekannte mehr zu sehen bekommen.

[b]Kanal-Backups[/b]

In $Projectname gibt es ein einfaches Ein-Klick-Backup, mit dem Du ein komplettes Backup Deiner Kanal-Einstellungen und Verbindungen herunterladen kannst.

Solche Backups sind ein Weg, um Klone zu erstellen, und können genutzt werden, um einen Kanal wiederherzustellen.

[b]Löschen von Konten[/b]

Konten und Kanäle können sofort gelöscht werden, indem Du einfach auf einen Link klickst. Das wars. Alle damit verbundenen Inhalte werden aus dem Grid gelöscht (inklusiver aller Beiträge und sonstiger Inhalte, die von dem gelöschten Konto/Kanal erzeugt wurden). Je nach Anzahl Deiner Verbindungen kann es etwas dauern, bis die Inhalte auch von allen Servern Deiner Kontakte gelöscht werden, aber die Löschung wird so schnell wie sinnvoll möglich durchgeführt.

[h2]Erstellen von Inhalten[/h2]

[b]Beiträge schreiben[/b]

$Projectname unterstützt diverse verschiedene Wege, um Inhalte mit Auszeichnung (z.B. fett, kursiv, farbig etc.) zu erstellen. Voreinstellung ist die $Projectname-Variante von BBCode (wie in vielen Web-Foren) mit einigen Ergänzungen, die nur hier funktionieren. Du kannst auch Markdown benutzen, wenn Dir das leichter fällt. Bis vor kurzem konnte auch ein grafischer Editor eingesetzt werden, der jedoch große Probleme aufwies und deshalb entfernt wurde. Wir suchen gerade nach einer Alternative.

Webseiten können neben BBCode und Markdown auch in HTML und Plain Text erstellt werden.

[b]Inhalte löschen[/b]

Alle Inhalte in $Projectname bleiben unter der Kontrolle des Mitglieds (bzw. Kanals), der sie ursprünglich erstellt hat. Alle Beiträge können jederzeit gelöscht werden, egal, ob sie auf dem Heimat-Server des Nutzers oder auf einem anderen Server erstellt wurden, an dem der Nutzer via Zot (Kommunikations- und Authentifizierungsprotokoll von $Projectname) angemeldet war.

[b]Medien[/b]

Genau wie jedes andere Blog-System, soziale Netzwerk oder Mikro-Blogging-Dienst unterstützt $Projectname das Hochladen von Dateien, das Einbetten von Bildern und Videos und das Verlinken von Seiten.

[b]Vorschau/Editieren[/b] 

Vor dem Absenden kann eine Vorschau von Beiträgen betrachtet werden. Außerdem können Beiträge auch nach dem Absenden noch verändert werden.

[b]Umfragen[/b]

Beiträge können als Umfragen gestaltet werden – die Leser können dann mittels entsprechender Buttons zustimmen, ablehnen oder sich enthalten, was ähnlich wie „Likes“ am Beitrag sichtbar wird. Dadurch kannst Du abschätzen, wie gut neue Ideen ankommen, oder informelle Umfragen starten.

[b]$Projectname erweitern[/b]

Die $Projectname kann auf vielerlei Art erweitert werden: Durch Server-Anpassung, persönliche Anpassung, setzen von Optionen, Themes und Addons/Plugins.

[b]API[/b]

Es existiert eine API, die von beliebigen Programmen/Apps und Diensten genutzt werden kann. Sie basiert auf der ursprünglichen Twitter-API (für die es hunderte von Tools und Apps gibt). Sie wird aktuell erweitert, um Zugriff auf Möglichkeiten zu gewähren, die es nur in $Projectname gibt. Authentifikation erfolgt über Login/Passwort oder OAuth. Eine Client-Registrierung für OAuth-Applikationen ist möglich.

#include doc/macros/main_footer.bb;
