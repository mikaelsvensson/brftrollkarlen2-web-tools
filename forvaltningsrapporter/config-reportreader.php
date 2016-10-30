<?php
use Config\PositionRule;
use Config\Reader;
use Config\TextRule;
$configReportReader = [
    new Reader("tenants", "/doc/row/Tj[text() = 'HYRESGÄSTFÖRTECKNING']", ["Header", "Footer"], false, [

        new TextRule("Footer", "Copyright", true),
        new TextRule("Header", "Användare", true),
        new TextRule("Header", "18916 Trollkarlen 2", true),

        new TextRule("Objekt", "^18916-", true),
        new TextRule("FakturaIntervall", "^må$", false),
        new TextRule("FakturaIntervall", "^kv$", false),
        new TextRule("AdressGata", "vägen", false),
        new TextRule("AdressGata", "gata", false),
        new TextRule("AdressGata", "Karusellv", false),
        new TextRule("AdressGata", "Cirkusv", false),
        new TextRule("AdressGata", "Tombolav", false),
        new TextRule("AdressPostnr", '^\d{3}\s\d{2}$', false),
        new TextRule("AdressVaning", '^(NB|\d\s+tr)$', false),
        new TextRule("Typ", "Lager", false),
        new TextRule("Typ", "P-plats", false),
        new TextRule("Avisering", "giro", false),

        new PositionRule("InOutDatum", "0 720"),
        new TextRule("InflyttAvflytt", '\d{4}-\d{2}-\d{2}', false, ["FromTom", "ObjFromTom"]),

        new PositionRule("Namn1", "3720 720"),
        new PositionRule("Namn1", "3720 240"),
        new PositionRule("Namn2", "0 480"),
        new PositionRule("SSN", "2640 240"),
        new PositionRule("SSN", "2640 720"),
        new PositionRule("Typ", "1680 960"),
        new PositionRule("Typ", "1680 240"),
        new PositionRule("Typ", "1080 960"),
        new TextRule("KrKvm", '\d{3,},\d', false),
        new TextRule("LghArea", '\d{1,2},\d', false, ["ContractArea"]),
        new PositionRule("LghData", "0 -240"),
        new PositionRule("Belopp", "-802 -1166"),
        new PositionRule("Avisering", "-12854 -720"),
        new PositionRule("AdressGata", "0 -480"),
        new PositionRule("AdressPost", "0 240"),
        new PositionRule("AdressGata", "0 -720"),
        new PositionRule("AdressGata", "1440 480"),
    ]),
    new Reader("tenants2", "/doc/row/TJ[starts-with(text(), 'HYRESGÄSTFÖRTECKNING')]", ["Header", "Footer"], false, [

        new TextRule("Footer", 'Copyright', true),
        new TextRule("Footer", 'Trollkarlen 2', true),
        new TextRule("Header", 'Användare', true),
        new TextRule("Header", '^HYRESGÄSTFÖRTECKNING', true),

        new TextRule("Objekt", '^18916-', true, ["DatumInflyttAvflytt", "Hyresgäst"]),
        new TextRule("AdressPostnr", '^\d{3}\s\d{2}', false),
        new TextRule("AvgTyp", '^(101|301)\s.+', false, ["AvgTotal", "AvgKrPerM2"]),
        new TextRule("AvgTyp", '^\d{3}\s.+', false),
        new TextRule("AviIntervall", '^Månad', false),
        new TextRule("AviIntervall", '^Kvartal$', false),
        new TextRule("AdressGata", 'vägen', false),
        new TextRule("AdressGata", 'Karusellv', false),
        new TextRule("AdressGata", 'Cirkusv', false),
        new TextRule("AdressGata", 'Tombolav', false),
        new TextRule("Yta", '\d+,\d+', false),
        new TextRule("AdressPlan", '^(NB|\d\s+tr)$', false),
        new TextRule("Beskrivning", 'Lager', false),
        new TextRule("Beskrivning", 'P-plats', false),
        new TextRule("Avisering", 'giro', false),
        new TextRule("Datum", '^\d{6}', false),

        new PositionRule("Hyresgast", "1.0001 0 0 1 151.44 357.12"),
        new PositionRule("Hyresgast", "1.0001 0 0 1 151.44 449.52"),
        new PositionRule("Hyresgast", "1.0001 0 0 1 151.44 333.12"),
        new PositionRule("Hyresgast", "1.0001 0 0 1 151.44 240.72"),
        new PositionRule("Hyresgast", "1.0001 0 0 1 151.44 148.32"),
        new PositionRule("Hyresgast", "1.0001 0 0 1 151.44 264.72"),
        new PositionRule("Hyresgast", "1.0001 0 0 1 151.44 172.32"),
        new PositionRule("Hyresgast", "1.0001 0 0 1 151.44 425.52"),

        new PositionRule("Hyresgast", "1.0001 0 0 1 152.16 229.44"),
        new PositionRule("Hyresgast", "1.0001 0 0 1 152.16 438.24"),

        new PositionRule("Beskrivning", "1.0001 0 0 1 11.28 229.44"),
        new PositionRule("Beskrivning", "1.0001 0 0 1 11.28 137.04"),
        new PositionRule("Beskrivning", "1.0001 0 0 1 11.28 438.24"),
        new PositionRule("Beskrivning", "1.0001 0 0 1 11.28 345.84"),
        new PositionRule("Beskrivning", "1.0001 0 0 1 11.28 253.44"),
        new PositionRule("Beskrivning", "1.0001 0 0 1 11.28 161.04"),
        new PositionRule("Beskrivning", "1.0001 0 0 1 11.28 68.64"),
        new PositionRule("Beskrivning", "1.0001 0 0 1 11.28 321.84"),
        new PositionRule("Beskrivning", "1.0001 0 0 1 11.28 414.24"),

        new PositionRule("AviAdress", "1.0001 0 0 1 151.44 206.88", false, ["UppsFörl", "AviPostort"])
    ]),
/*        <reader xpathMatchPattern="/doc/row/TJ[starts-with(text(), 'MEDLEMSFÖRTECKNING')]"
                skipEntriesWithColumn="Header Footer">

            <textRule outputColumn="Footer" pattern="Copyright" isGroupStart="true"/>
            <textRule outputColumn="Footer" pattern="Trollkarlen 2" isGroupStart="true"/>
            <textRule outputColumn="Header" pattern="Användare" isGroupStart="true"/>
            <textRule outputColumn="Header" pattern="^MEDLEMSFÖRTECKNING" isGroupStart="true"/>

            <textRule outputColumn="Objekt" pattern="^18916-" isGroupStart="true"/>
            <textRule outputColumn="Personnr" pattern="^\d{6}" isGroupStart="false" followingOutputColumns="Inträde Förvärv AntalRum AdressGata AdressPostnr AdressPostort"/>
        </reader>
        <reader xpathMatchPattern="/doc/row/Tm[@cmd='1 0 0 -1 825 505']"
                skipEntriesWithColumn="Header Footer">

            <textRule outputColumn="Footer" pattern="Copyright" isGroupStart="true"/>
            <textRule outputColumn="Footer" pattern="Trollkarlen 2" isGroupStart="true"/>
            <textRule outputColumn="Header" pattern="Användare" isGroupStart="true"/>
            <textRule outputColumn="Header" pattern="^MEDLEMSFÖRTECKNING" isGroupStart="true"/>

            <positionRule outputColumn="Namn" exactMatch="-10200 -311" isGroupStart="true" followingOutputColumns="Inträde AntalRum AdressGata AdressPostort"/>
            <textRule outputColumn="Objekt" pattern="^18916-" isGroupStart="true" followingOutputColumns="Namn Inträde AntalRum AdressGata AdressPostort"/>
        </reader>
*/
    new Reader("members", "/doc/row/Tj[text() = 'Medlemsförteckning']", ["Header", "Footer"], false, [

        new TextRule("Footer", 'Copyright', true),
        new TextRule("Header", 'Användare', true),
        new TextRule("Header", '18916 Trollkarlen 2', true),

        new TextRule("Objekt", '^18916-', true),

        new PositionRule("Namn", "1605 0"),
        new PositionRule("Namn", "10200 -311"),
        new PositionRule("Namn", "-10200 -311"),
        new PositionRule("LghData", "1553 0"),
        new PositionRule("AdressGata", "1955 0"),
        new PositionRule("AdressPostort", "2160 0"),
        new PositionRule("Datum", "4532 0"),
    ]),
    new Reader("debts", "/doc/row/Tj[text() = 'HYRESFORDRAN']", ["Header", "Footer"], false, [

        new TextRule("Fakturanr", '48775\d+', true),
        new TextRule("Header", 'HYRESFORDRAN', false),
        new TextRule("Footer", '18916 Trollkarlen 2', true),


        new PositionRule("Fakturadatum", "5400 0"),
        new PositionRule("Forfallodatum", "1080 0"),
        new PositionRule("Restbelopp", "1482 0"),
        new PositionRule("Restbelopp", "1598 0"),
        new PositionRule("Krav", "5432 0"),
        new PositionRule("Krav", "5316 0"),
        new PositionRule("Fakturabelopp", "-872 0"),
        new PositionRule("Fakturabelopp", "-756 0"),
        new PositionRule("Objekt", "-11082 0"),
        new PositionRule("Objekt", "-11198 0"),
        new PositionRule("Hyresgast", "1200 0"),
        new PositionRule("Typ", "2160 0"),
    ]),
    new Reader("ending_contracts", "/doc/row/Tj[text() = 'KONTRAKT UPPHÖR']", ["Header", "Footer"], false, [

        new TextRule("Header", '48775 Trollkarlen 2 Brf', false),
        new TextRule("Footer", 'Copyright', true),
        new TextRule("Footer", '18916 Trollkarlen 2', true),

        new TextRule("Objekt", '^18916-', true),
        new PositionRule("Typ", "1320 0"),
        new PositionRule("Hyresgast", "1920 0"),
        new TextRule("Kontraktstid", '\d{4}-\d{2}-\d{2} - \d{4}-\d{2}-\d{2}', false),

        new PositionRule("Uppsägningsda", "1919 0"),
        new PositionRule("Uppsägning", "-3645 0"),
        new PositionRule("Uppsägning", "-3723 0"),
        new PositionRule("Uppsägning", "-4768 0"),
        new PositionRule("Förlängning", "1170 0"),
        new PositionRule("Förlängning", "1092 0"),
        new PositionRule("Kr/kvm", "5248 0"),
        new PositionRule("Kr/kvm", "5404 0"),
        new TextRule("Area", '\d+,\d+', false),

        new PositionRule("Årshyra", "11732 0"),
        new PositionRule("Årshyra", "11654 0"),

    ]),
    new Reader("apartments", "/doc/row/Tj[text() = 'LÄGENHETSFÖRTECKNING']", ["PageStart"], false, [

        new PositionRule("PageStart", "1454 -1422", true),
        new TextRule("PageStart", 'LÄGENHETSFÖRTECKNING', true),
        new TextRule("LghData", 'rum och', true, ["Area", "ProcentArsavgift", "UpplatelseAvgift", "Insats", "AdressPostnr"]),

        new TextRule("Objekt", '^18916-'),
        new PositionRule("AdressGata", "-5051 -2"),

        new TextRule("Vaning", 'tr$'),
        new TextRule("Vaning", 'NB'),
        new PositionRule("AdressGata", "-5051 -2")

    ]),
    new Reader("shares", "/doc/row/Tj[text() = 'Andelstal']", ["Header", "Footer"], false, [

        new TextRule("Header", '48775 Trollkarlen 2 Brf', false),
        new TextRule("Header", 'Användare', true),
        new TextRule("Footer", 'Copyright', true),
        new TextRule("Footer", '18916 Trollkarlen 2', true),

        new TextRule("Objekt", '^18916-', true, ["Namn", "Personnr"]),
        new PositionRule("Namn", '-13849 -303', true, ["Personnr"]),
        new PositionRule("Namn", '-4051 -303', true, ["Personnr"]),
        new PositionRule("Andel", "1531 0"),
        new PositionRule("AndelArsavgift", "3542 0"),
        new PositionRule("AndelFormogenhet", "-1726 0"),

        new PositionRule("Andel", "1453 0"),
        new PositionRule("AndelArsavgift", "3620 0"),

        new PositionRule("AndelArsavgift", "1894 0"),

        new PositionRule("Area", "7982 0")
    ]),
    new Reader("resultatrakning", "/doc/row/Tj[text() = 'Resultaträkning']", ["PageStart", "Query"], true, [

        new TextRule("PageStart", '^Resultat', false),
        new PositionRule("PageStart", "-40.35 -12"),
        new PositionRule("Rubrik", "0 -27.5"),
        new PositionRule("Rubrik", "-371.25 -13.75"),
        new TextRule("PageStart", '\d{4}-\d{2}-\d{2}', false),

        new TextRule("PageStart", '^Sida', false),
        new TextRule("PageStart", 'Incit Xpand', false),

        new TextRule("Konto", '^\d{4}$', true, ["Kontonamn", "AccUtfall", "BudgetHelar", "Procent", "PeriodUtfall", "AccUtfallForegAr"]),
        new TextRule("Rubrik", '^summa', false, ["AccUtfall", "BudgetHelar", "Procent", "PeriodUtfall", "AccUtfallForegAr"])
    ]),
    new Reader("huvudbok", "/doc/row/Tj[text() = 'Huvudbok']", ["PageStart", "Query", "ColumnHeader", "HideRow"], false, [

        new PositionRule("Rubrik", '0 -11.75', true),

        new TextRule("Rubrik", '^Summa:$', true, ["Debet", "Kredit"]),
        new TextRule("Rubrik", '^Summa', false, ["Debet", "Kredit"]),

        new TextRule("PageStart", '^Huvudbok', false),
        new TextRule("ColumnHeader", '^Verifikatid$', true),
        new TextRule("ColumnHeader", '^Konto$', true),
        new PositionRule("PageStart", "-36.5 -12"),

        new TextRule("Konto", '^\d{4}$', true, ["Kontonamn", "Rubrik", "BalansIn", "SaldoIn", "Rubrik"]),

        new TextRule("Volym", '0,0000', true, ["Verifikatid", "BokfDatum"]),
        new TextRule("Kredit", '^-\d[\s\d]*,\d{2}'),
        new TextRule("Debet", '^\d[\s\d]*,\d{2}'),
        new TextRule("HideRow", 'Förändring', true, ["Rubrik", "Debet", "Kredit", "Volym", "Rubrik", "Förändring", "SaldoUt"]),

        new TextRule("PageStart", 'Incit Xpand', false)
    ]),
    new Reader("huvudbok_with_summary", "/doc/row/Tj[text() = 'Huvudbok med radsaldo']", ["PageStart", "Query", "ColumnHeader", "HideRow"], false, [

        new PositionRule("Rubrik", '0 -11.75', true),


        new TextRule("PageStart", '^Huvudbok', false),
        new TextRule("PageStart", 'Incit Xpand', true),
        new TextRule("ColumnHeader", '^Verifikatid$', true),
        new TextRule("ColumnHeader", '^Konto$', true),
        new PositionRule("PageStart", "-36.5 -12"),

        new TextRule("Verifikatid", '^18916\w{2}\d{4}', true, ["Radtext", "BokfDatum"]),
        new TextRule("Kredit", '^-\d[\s\d]*,\d{2}', false, ["Radsumma"]),
        new TextRule("Debet", '^\d[\s\d]*,\d{2}', false, ["Radsumma"]),



        new TextRule("Konto", '^\d{4}$', true, ["Kontonamn", "BalansIn", "SaldoIn"]),

        new TextRule("HideRow", 'Förändring', true, ["Rubrik", "Debet", "Kredit", "Volym", "Rubrik", "Förändring", "SaldoUt"])
    ])
];