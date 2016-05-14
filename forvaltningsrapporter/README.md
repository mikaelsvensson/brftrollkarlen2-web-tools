# Förvaltningsrapporter

Det här är ett litet verktyg för att automatiskt ladda ner rapporter från Fastighetsägarnas webbplats Entré.

## Funktioner

Verktyget består av tre delar:

* Funktion för att ladda ner rapporter.
* Funktion för att visa och jämföra de senaste rapporterna.
* Funktion för att generera dokument utifrån information på en rapportrad.

## Konfiguration

Verktyget använder sig av tre konfigurationsfiler.

### Entré-inloggning

Filen config-credentials.php innehåller användarnamn och lösenord till Entré:
 
    <?php
    const STOFAST_USERNAME = "YOUR_STOFAST_USERNAME_HERE";
    const STOFAST_PASSWORD = "YOUR_STOFAST_PASSWORD_HERE";
    ?>
    
Filen finns inte incheckad i Git och måste därför skapas manuellt i samma mapp som config.php ligger i.

### Rapportbeskrivningar

Filen cfg.xml innehåller instruktioner för hur information ska extraheras ur de PDF-filer som kan laddas ner via Entré.

Grundtanken är att klassen PdfReader används för att konvertera PDF-filen till ett XML-dokument med de instruktioner
som PDF-dokumentet består av. Rapportbeskrivningen använder sig sedan av två typer av regler för att extrahera en
tabell från PDF-dokumentet.

Möjliga regler:

* positionRule används för att hitta tabellceller som alltid ritas ut med samma koordinater.
* textRule används för att hitta tabellceller vars innehåller matchar ett visst reguljärt uttryck.

Specialfall:

* Det går att filtrera bort återkommande sidhuvuden och sidfötter mha. skipEntriesWithColumn.
* En rad kallas "group" i konfigurationsfilen, så "isGroupStart" indikerar att när något matchar regeln så ska en ny rad skapas.

### Google API-nyckel

För att kunna använda verktyget för att generera dokument utifrån värden från rapportrader så behöver en JSON-fil med
Google-nyckel finnas tillgänglig. JSON-filen kan laddas ner från Google Developer Console på 
https://console.developers.google.com/apis/credentials?project=brftrollkarlen2-web-tools
    
Verktyget finns registrerad som OAuth-klienten "Dokumentgeneratorn".

JSON-filen ska sparas på den plats som anges i konstanten GOOGLE_CLIENT_SECRET_FILE (se google-util.php).

## Ladda ner rapporter

Genom att anropa download-reports.php så händer följande:

* Webbsidan loggar in på Entré mha. det i konfigurationsfilen angivna användarkontot.
* Ett antal rapporter laddas ner och sparas.

Konstanten REPORT_DAYS anger vilka dagar i månaderna som rapporter laddas ner, om download-reports.php anropas någon
gång under respektive dag. I dagsläget används http://uptimerobot.com/ för att anropa sidan en gång per timme.

## Visa och jämföra de senaste rapporterna

Genom att surfa till index.php så får man upp en vy över de senaste N rapporterna. Den senaste rapporten visas
komplett. För tidigare rapporter visas bara skillnaderna.

Vissa rapporter/tabeller har länkar (som läggs till mha. Javascript) för att generera dokument utifrån tabellraderna.

## Generera dokument

För att underlägga styrelsearbetet så finns en mycket specifik finess för att generera dokument. Detta kan användas
för att, exempelvis, skapa semi-personliga välkomstbrev till nya boende. Dokument genereras så här:

1. Användaren tittar på en rapport och vill generera ett dokument utifrån information på en viss rad.
1. Användaren loggar in mha. Google.
1. Webbsidan visar en lista över användarens Google Docs-dokument. Användaren väljer en av dessa att använda som mall.
1. Formuläret visar vilken information som kommer att användas för att generera det nya dokumentet.
1. Webbsidan laddar ner en kopia av malldokumentet, ersätter alla förekomster av "platshållarna" och laddar upp den
   modifierade kopian som ett nytt dokument.
1. Webbsidan visar det nya dokumentet som en PDF, redo för utskrift eller annan åtgärd.