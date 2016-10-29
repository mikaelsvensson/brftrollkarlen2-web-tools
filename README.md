# Webbverktyg för brf. Trollkarlen 2

## ekonomirapporter

Under utveckling. Tanken är att visa grafer över utgifter och inkomster per bokföringskonto.

## forvaltningsrapporter

Används för att på ett enkelt sätt visa upp information om boende, kontrakt och annat.

Tanken med verktyget är följande:
* Enkelt se vilka boende som flyttat in resp. ut senaste tiden.
* Enkelt kopiera information om boende för att använda i personliga brevutskick.
* Enkelt se all denna information utan att behöva logga in på Entré. Istället behöver man bara logga in med vårat
gemensamma Google-konto.

I bakgrunden används en styrelsemedlems Entré-konto för att logga in i Entré och hämta rapporterna.

För att kunna se rapporterna måste man logga in med ett Google-konoto och detta fyller två syften:
1. Det säkerställer att bara de som kan lösenordet till föreningens e-postkonto kan se informationen.
1. Det gör det möjligt att komplettera informationen som hämtas från Entré med information från Google Contacts.

För att den här PHP-sidan ska kunna anropa Google så har sidan registrerats som ett projekt hos Google. Du hittar
projektet på https://console.developers.google.com/apis/dashboard?project=brftrollkarlen2-web-tools. Kom ihåg att du
måste logga in som brf.trollkarlen2@gmail.com.

Via console-sidan så hittar man det "OAuth 2.0 client ID" som PHP-sidan använder för att prata med Google. Namnet på
PHP-sidan, såsom det är uppsatt hos Google, är "Dokumentgeneratorn" (vilket numera är något missvisande). Kikar man
närmare på installningarna för "Dokumentgeneratorn" så ser man den "client secret" som också återfinns i
client_secret_...apps.googleusercontent.com.json på webbservern.

Kända problem:
* Efter ett tag blir man utloggad från Google men det visas inget felmeddelande. Det enda som händer är att rapporterna
inte visas. Testa att klicka på "Logga ut och logga in" och försök igen.

## lib

Används för externa PHP-bibliotek. I dagsläget det om följande:
* Google API Client för att komma åt föreningens dokument på Google Drive från de här verktygen.
* En PDF-läsare (som inte längre används eftersom den inte klarade av alla de Crystal Reports-PDF:er som vi kan få från Fastighetsägarna).

## tillsynsrapporter

Här laddar vi upp de Excel-filer som vi får från Rolf Samuelsson.

Filerna sparas på servern och utvalt information ur filerna presenteras i tabell och/eller diagramfram. Olika typer av
information presenteras på olika sätt.

Tanken med det här verktyget är att underlätta för föreningen att upptäckta trender i hur fastighetens tekniska komponenter mår.

Kända problem:
* Verktyget förutsätter att rapporterna från Rolf alltid ser ut på samma sätt (vilket de också gjort de senaste åren)
men framtida förändringar kommer kräva att verktyget anpassas efter förändringarna.
* Verktyget kan inte på egen hand upptäcka mätfel och slarvfel i de värden som Rolf eventuellt gör. Det kan därför
uppstå stora hack i graferna när något värde avviker kraftigt från normen.

## vendor

De PHP-bibliotek som verktygen importerar mha. Composer.

Den här mappen finns inte incheckad men finns uppladdad till webbservern. Som utvecklare som förväntas man installera Composer lokalt och köra "composer update" för att hämta de senaste filerna. Om de senaste filerna fungerar bra så laddar man upp de till webbservern.