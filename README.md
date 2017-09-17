# Webbverktyg för brf. Trollkarlen 2

## ekonomirapporter

Under utveckling. Tanken är att visa grafer över utgifter och inkomster per bokföringskonto.

## forvaltningsrapporter

Se forvaltningsrapporter/README.md

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