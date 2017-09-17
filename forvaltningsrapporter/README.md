# F�rvaltningsrapporter

Det h�r �r ett litet verktyg f�r att automatiskt ladda ner rapporter fr�n Fastighets�garnas webbplats Entr�.
I bakgrunden anv�nds en styrelsemedlems Entr�-konto f�r att logga in i Entr� och h�mta rapporterna.

Tanken med verktyget �r f�ljande:
* G�r det enkelt att hitta information om boende, kontrakt och annat.
* G�r det enkelt se vilka boende som flyttat in resp. ut senaste tiden.
* G�r det enkelt kopiera information om boende f�r att anv�nda i personliga brevutskick.
* G�r det enkelt se all denna information utan att beh�va logga in p� Entr�. Ist�llet beh�ver du bara logga in med 
  v�rat gemensamma Google-konto.

F�r att kunna se rapporterna m�ste man logga in med ett Google-konoto och detta fyller tv� syften:
1. Det s�kerst�ller att bara de som kan l�senordet till f�reningens e-postkonto kan se informationen.
1. Det g�r det m�jligt att komplettera informationen som h�mtas fr�n Entr� med information fr�n Google Contacts.

## K�nda problem

* Efter ett tag blir man utloggad fr�n Google men det visas inget felmeddelande. Det enda som h�nder �r att rapporterna
inte visas. Testa att klicka p� "Logga ut och logga in" och f�rs�k igen.

## Funktioner

Verktyget best�r av tre delar:

* Funktion f�r att ladda ner rapporter.
* Funktion f�r att visa och j�mf�ra de senaste rapporterna.
* Funktion f�r att generera dokument utifr�n information p� en rapportrad.

## Konfiguration

Verktyget anv�nder sig av tre konfigurationsfiler.

### Entr�-inloggning

Filen config-credentials.php inneh�ller anv�ndarnamn och l�senord till Entr�:
 
    <?php
    const STOFAST_USERNAME = "YOUR_STOFAST_USERNAME_HERE";
    const STOFAST_PASSWORD = "YOUR_STOFAST_PASSWORD_HERE";
    ?>
    
Filen finns inte incheckad i Git och m�ste d�rf�r skapas manuellt i samma mapp som config.php ligger i.

Anv�nd forvaltningsrapporter/config-credentials.sample.php som mall.

### Rapportbeskrivningar

Filen cfg.xml inneh�ller instruktioner f�r hur information ska extraheras ur de PDF-filer som kan laddas ner via Entr�.

Grundtanken �r att klassen PdfReader anv�nds f�r att konvertera PDF-filen till ett XML-dokument med de instruktioner
som PDF-dokumentet best�r av. Rapportbeskrivningen anv�nder sig sedan av tv� typer av regler f�r att extrahera en
tabell fr�n PDF-dokumentet.

M�jliga regler:

* positionRule anv�nds f�r att hitta tabellceller som alltid ritas ut med samma koordinater.
* textRule anv�nds f�r att hitta tabellceller vars inneh�ller matchar ett visst regulj�rt uttryck.

Specialfall:

* Det g�r att filtrera bort �terkommande sidhuvuden och sidf�tter mha. skipEntriesWithColumn.
* En rad kallas "group" i konfigurationsfilen, s� "isGroupStart" indikerar att n�r n�got matchar regeln s� ska en ny rad skapas.

### Google API-nyckel

F�r att den h�r PHP-sidan ska kunna anropa Google s� har sidan registrerats som ett projekt hos Google. Du hittar
projektet p� https://console.developers.google.com/apis/dashboard?project=brftrollkarlen2-web-tools. Kom ih�g att du
m�ste logga in som brf.trollkarlen2@gmail.com.

Via console-sidan s� hittar man det "OAuth 2.0 client ID" som PHP-sidan anv�nder f�r att prata med Google. Namnet p�
PHP-sidan, s�som det �r uppsatt hos Google, �r "Dokumentgeneratorn" (vilket numera �r n�got missvisande). Kikar man
n�rmare p� installningarna f�r "Dokumentgeneratorn" s� ser man den "client secret" som ocks� �terfinns i
client_secret_...apps.googleusercontent.com.json p� webbservern.

F�r att kunna anv�nda verktyget f�r att generera dokument utifr�n v�rden fr�n rapportrader s� beh�ver en JSON-fil med
Google-nyckel finnas tillg�nglig. JSON-filen kan laddas ner fr�n Google Developer Console p� 
https://console.developers.google.com/apis/credentials?project=brftrollkarlen2-web-tools
    
JSON-filen ska sparas p� den plats som anges i konstanten GOOGLE_CLIENT_SECRET_FILE (se google-util.php).

## Ladda ner rapporter

Genom att anropa download-reports.php s� h�nder f�ljande:

* Webbsidan loggar in p� Entr� mha. det i konfigurationsfilen angivna anv�ndarkontot.
* Ett antal rapporter laddas ner och sparas.

Konstanten REPORT_DAYS anger vilka dagar i m�naderna som rapporter laddas ner, om download-reports.php anropas n�gon
g�ng under respektive dag. I dagsl�get anv�nds http://uptimerobot.com/ f�r att anropa sidan en g�ng per timme.

## Visa och j�mf�ra de senaste rapporterna

Genom att surfa till index.php s� f�r man upp en vy �ver de senaste N rapporterna. Den senaste rapporten visas
komplett. F�r tidigare rapporter visas bara skillnaderna.

Vissa rapporter/tabeller har l�nkar (som l�ggs till mha. Javascript) f�r att generera dokument utifr�n tabellraderna.

## Generera dokument

F�r att underl�gga styrelsearbetet s� finns en mycket specifik finess f�r att generera dokument. Detta kan anv�ndas
f�r att, exempelvis, skapa semi-personliga v�lkomstbrev till nya boende. Dokument genereras s� h�r:

1. Anv�ndaren tittar p� en rapport och vill generera ett dokument utifr�n information p� en viss rad.
1. Anv�ndaren loggar in mha. Google.
1. Webbsidan visar en lista �ver anv�ndarens Google Docs-dokument. Anv�ndaren v�ljer en av dessa att anv�nda som mall.
1. Formul�ret visar vilken information som kommer att anv�ndas f�r att generera det nya dokumentet.
1. Webbsidan laddar ner en kopia av malldokumentet, ers�tter alla f�rekomster av "platsh�llarna" och laddar upp den
   modifierade kopian som ett nytt dokument.
1. Webbsidan visar det nya dokumentet som en PDF, redo f�r utskrift eller annan �tg�rd.