<?php
const PROP_REPORTREADER = 'reportreader';
const PROP_ROWPROCESSOR = 'rowprocessor';
const PROP_SUMMARY_GENERATOR = 'summarygenerator';
const PROP_URL = 'url';
const PROP_COLUMNS = 'columns';
const PROP_TITLE = 'title';

require_once 'config/BaseRule.php';
require_once 'config/PositionRule.php';
require_once 'config/Reader.php';
require_once 'config/TextRule.php';

use Config\PositionRule;
use Config\Reader;
use Config\TextRule;

const FILES_FOLDER = './reports-archive/';

const REPORT_DAYS = ["01", "03", "05", "15", "26", "28", "30"];

$defaultParams = ['DATUM' => date('Ymd-His')];

function getGoogleContacts($client)
{
    $feedURL = "https://www.google.com/m8/feeds/contacts/default/thin?max-results=1000&alt=json";
    $feedURL = "https://www.google.com/m8/feeds/contacts/default/full?max-results=1000&alt=json";
    //        $feedURL = "https://www.google.com/m8/feeds/contacts/default/full";
    $req = new Google_Http_Request($feedURL, 'GET', array("GData-Version" => "3.0"));
    $val = $client->getAuth()->authenticatedRequest($req);

    // The contacts api only returns XML responses.
    $responseRaw = $val->getResponseBody();

    $response = json_decode($responseRaw, true)['feed']['entry'];

    $contacts = array_map(function ($contact) {
        $aptAccessFromDateProps = is_array($contact['gContact$userDefinedField']) ? array_filter($contact['gContact$userDefinedField'],
            function ($item) {
                return strpos($item['key'], 'Tilltr') != -1;
            }) : [];
        return [
            'name' => isset($contact[PROP_TITLE]) ? $contact[PROP_TITLE]['$t'] : null,
            'updated' => $contact['updated']['$t'],
            'note' => isset($contact['content']) ? $contact['content']['$t'] : null,
            'email' => isset($contact['gd$email']) ? $contact['gd$email'][0]['address'] : null,
            'phone' => isset($contact['gd$phoneNumber']) ? $contact['gd$phoneNumber'][0]['$t'] : null,
            'orgName' => isset($contact['gd$organization']) ? $contact['gd$organization'][0]['gd$orgName']['$t'] : null,
            'orgTitle' => isset($contact['gd$organization']) ? $contact['gd$organization'][0]['gd$orgTitle']['$t'] : null,
            'aptAccessFrom' => count($aptAccessFromDateProps) > 0 ? $aptAccessFromDateProps[0]['value'] : null,
            'address' => isset($contact['gd$postalAddress']) ? explode("\n", $contact['gd$postalAddress'][0]['$t']) : null
        ];
    }, $response);
    return $contacts;
}

function findContacts($row, $contacts, $column)
{
    $fn = function ($value) {
        $nameParts = explode(' ', $value);
        sort($nameParts);
        return implode(' ', array_map("soundex", $nameParts));
    };
    $str = $fn($row[$column][0]);
    return array_filter($contacts, function ($contact) use ($str, $fn) {
        return $str == $fn($contact['name']);
    });
}

function addContactColumn(&$row, $contacts, $column)
{
    $matchingContacts = findContacts($row, $contacts, $column);
//                print_r($matchingContacts);
    if (count($matchingContacts) > 0) {
        $contact = array_pop($matchingContacts);
        if (preg_match('/\d{1,3}%/', $contact["orgName"], $ownershipPercent)) {
            $row['KontaktAgarandel'] = [$ownershipPercent[0]];
        }
        if (preg_match('/\d{4}/', $contact["orgName"], $addrNumber)) {
            $row['KontaktAddrLgnNr'] = [$addrNumber[0]];
        }
//        if (preg_match('/\d{3}\D/', $contact["orgName"], $aptNo)) {
//            $row['KontaktLghNr'] = [$aptNo[0]];
//        }
        if (preg_match('/[CKT][a-z]*\s+\d{1,2}/', $contact["orgName"], $addr)) {
            $row['KontaktGata'] = [$addr[0]];
        }
        $row['KontaktTilltrade'] = [$contact["aptAccessFrom"]];
//        $row['KontaktForetag'] = [$contact["orgName"]];
//        $row['KontaktTitel'] = [$contact["orgTitle"]];
        $row['KontaktNamn'] = [$contact["name"]];
        $row['KontaktEpost'] = [sprintf('<a href="mailto:%s">%s</a>', $contact["email"], $contact["email"])];
        $row['KontaktTelefon'] = [$contact["phone"]];
        return $row;
    }
    return $row;
}

$REPORTS = [
    'hyresfordran' =>
        [
            PROP_TITLE => 'Hyresfordran',
            PROP_COLUMNS => ['LghNr', 'Fakturanr', 'Restbelopp', 'Hyresgast', 'Forfallodatum', 'DagarForsening', 'KontaktNamn', 'KontaktEpost', 'KontaktTelefon'],
            PROP_URL => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/5CA3A55A0BECD55DC12579C50042A060/$File/48775_1001.pdf?openElement',
            PROP_ROWPROCESSOR => function ($row, $contacts) {
                $row['LghNr'] = [intval(substr($row['Objekt'][0], 6), 10)];

                $date = DateTime::createFromFormat('Y-m-d', $row['Forfallodatum'][0]);
                $interval = date_diff(new DateTime(), $date);
                $row['DagarForsening'] = [$interval->days];

                addContactColumn($row, $contacts, 'Hyresgast');

                return $row;
            },
            PROP_SUMMARY_GENERATOR => function ($data) {
                $res = [];
                $sum = 0.0;
                foreach ($data as $row) {
                    $debtee = $row['Hyresgast'][0];
                    $amount = intval(preg_replace('/\D/', '', $row['Restbelopp'][0]));

                    $res[$debtee]['Hyresgast'][0] = $debtee;
                    $res[$debtee]['TotalRestbelopp'][0] += $amount;
                    $res[$debtee]['AntalRestbelopp'][0]++;
                    $sum += $amount;
                }
                $res["sum"]['Hyresgast'][0] = "SUMMA";
                $res["sum"]['TotalRestbelopp'][0] = $sum;
                $res["sum"]['AntalRestbelopp'][0] = count($data);
                return array_values($res);
            },
            PROP_REPORTREADER => new Reader("debts", "/doc/row/Tj[text() = 'HYRESFORDRAN']", ["Header", "Footer"], false, [

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
            ])
        ],
    'medlemsforteckning' =>
        [
            PROP_TITLE => 'Medlemmar',
            PROP_COLUMNS => null,
            PROP_URL => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/563B9D7BB796E5D7C12579B70048844B/$File/48775_1005.pdf?openElement',
            PROP_ROWPROCESSOR => function ($row, $contacts) {
                static $last = null;
                if (empty($row['LghNr'])) {
                    $row['LghNr'] = $last;
                }
                $row['LghNr'] = [intval(substr($row['Objekt'][0], 6), 10)];
                $last = $row['LghNr'];

                $names = implode(" och ", array_map(function ($s) {
                    return array_reverse(explode(' ', $s))[0];
                }, $row['Namn']));
                $row['Fornamn'] = [$names];

                $row['AdressGata'] = array_unique($row['AdressGata']);
                $row['AdressPostort'] = array_unique($row['AdressPostort']);
                $row['Datum'] = array_unique($row['Datum']);
                $row['LghData'] = array_unique($row['LghData']);

                $date = DateTime::createFromFormat('Y-m-d', $row['Datum'][0]);
                $interval = date_diff(new DateTime(), $date);
                $memberYears = round(1.0 * $interval->days / 365.25, 1);
                $row['Medlemsar'] = [$memberYears];
                if ($memberYears < 0.3) {
                    $row['Status'] = ['NY'];
                } elseif ($memberYears > 0.9 && $memberYears < 1.1) {
                    $row['Status'] = ['ETT&nbsp;&Aring;R'];
                } else {
                    $row['Status'] = [''];
                }

                $row['Antal_Ytterligare'] = [count($row['Namn']) - 1];

                addContactColumn($row, $contacts, 'Namn');

                return $row;
            },
            PROP_REPORTREADER => new Reader("members", "/doc/row/Tj[text() = 'Medlemsförteckning']", ["Header", "Footer"], false, [

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
            ])
        ],
    'kontrakt-upphor' =>
        [
            PROP_TITLE => 'Kontrakt',
            PROP_COLUMNS => ['Objekt', 'Typ', 'Hyresgast', 'Area', 'Fran', 'Till', 'DagarKvar', 'KontaktNamn', 'KontaktEpost', 'KontaktTelefon'],
            PROP_URL => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/F0DAE4CB20A599E8C12579C50042A74F/$File/48775_1002.pdf?openElement',
            PROP_ROWPROCESSOR => function ($row, $contacts) {
                $period = $row['Kontraktstid'][0];
                list($from, $to) = explode(' - ', $period);
                $row['Fran'] = [$from];
                $row['Till'] = [$to];
                $row['Objekt'] = [substr($row['Objekt'][0], 6)];

                $toDate = DateTime::createFromFormat('Y-m-d', $to);
                $interval = date_diff(new DateTime(), $toDate);
                $row['DagarKvar'] = [$interval->days];

                addContactColumn($row, $contacts, 'Hyresgast');

                return $row;
            },
            PROP_REPORTREADER => new Reader("ending_contracts", "/doc/row/Tj[text() = 'KONTRAKT UPPHÖR']", ["Header", "Footer"], false, [

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

            ])
        ],
    'andelstal' =>
        [
            PROP_TITLE => 'Andelstal',
            PROP_COLUMNS => null,
            PROP_URL => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/C99AFBE828B40038C12579F900117247/$File/48775_1004.pdf?openElement',
            PROP_ROWPROCESSOR => function ($row) {
                static $last = null;
                if (empty($row['Objekt'])) {
                    $row['Objekt'] = $last;
                } else {
                    $row['Objekt'] = [substr($row['Objekt'][0], 7)];
                }
                $last = $row['Objekt'];

                static $lastAndelArsavgiftLgh = null;
                if (empty($row['AndelArsavgiftLgh'])) {
                    $row['AndelArsavgiftLgh'] = $lastAndelArsavgiftLgh;
                }
                $lastAndelArsavgiftLgh = $row['AndelArsavgiftLgh'];

                $row['Personnr'] = [substr($row['Personnr'][0], 0, 6) . "-****"];

                $aptYearlyFeeShare = doubleval(str_replace(',', '.', $row['AndelArsavgiftLgh'][0]));
                $tenantShare = doubleval(str_replace(',', '.', $row['AndelAvLgh'][0]));
                $row['AndelArsavgiftPerson'] = [number_format($aptYearlyFeeShare * ($tenantShare / 100), 5, ',', '')];

                return $row;
            },
            PROP_REPORTREADER => new Reader("shares", "/doc/row/Tj[text() = 'Andelstal']", ["Header", "Footer"], false, [

                new TextRule("Header", '48775 Trollkarlen 2 Brf', false),
                new TextRule("Header", 'Användare', true),
                new TextRule("Footer", 'Copyright', true),
                new TextRule("Footer", '18916 Trollkarlen 2', true),

                new TextRule("Objekt", '^18916-', true, ["Namn", "Personnr"]),
                new PositionRule("Namn", '-13849 -303', true, ["Personnr"]),
                new PositionRule("Namn", '-4051 -303', true, ["Personnr"]),
                new PositionRule("AndelAvLgh", "1531 0"),
                new PositionRule("AndelArsavgiftLgh", "1816 0"),
                new PositionRule("AndelArsavgiftLgh", "3542 0"),
                new PositionRule("AndelFormogenhetLgh", "-1726 0"),

                new PositionRule("AndelAvLgh", "1453 0"),
                new PositionRule("AndelArsavgiftLgh", "3620 0"),

                new PositionRule("AndelArsavgiftLgh", "1894 0"),

                new PositionRule("AreaLgh", "7982 0")
            ])
        ],
    'objektsforteckning-hyresgastforteckning' =>
        [
            PROP_TITLE => 'Objektsf&ouml;rteckning',
            PROP_COLUMNS => ['LghNr', 'Objekt', 'Namn1', 'Namn2', 'AdressGata', 'AdressVaning', 'AdressPostnr', 'AdressPost', 'Typ', 'Datum', 'KontaktNamn', 'KontaktEpost', 'KontaktTelefon'],
            PROP_URL => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/3C82828B45507253C12579C500429889/$File/48775_1003.pdf?openElement',
            PROP_ROWPROCESSOR => function ($row, $contacts) {
                $row['LghNr'] = [intval(substr($row['Objekt'][0], 6), 10)];

                addContactColumn($row, $contacts, 'Namn1');

                return $row;
            },
//            PROP_SUMMARY_GENERATOR => function ($data) {
//                $res = [];
//                foreach ($data as $row) {
//                    $aptNumber = intval($row['LghNr'][0]);
//                    if ($aptNumber < 1000) {
//                        $res[$row['AdressGata'][0]]['AdressGata'] = [$row['AdressGata'][0]];
//                        $floor = str_replace(' ', '', $row['AdressVaning'][0]);
//                        $res[$row['AdressGata'][0]]['Apt' . $floor . ($aptNumber % 2)] = [$row['Namn1'][0] . (!empty($row['Namn2'][0]) ? ', ' . $row['Namn2'][0] : '')];
//                    }
//                }
//                return array_values($res);
//            },
            PROP_REPORTREADER => new Reader("tenants", "/doc/row/Tj[text() = 'HYRESGÄSTFÖRTECKNING']", ["Header", "Footer"], false, [
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
            ])
        ],
    'huvudbok' => [
        PROP_ROWPROCESSOR => function ($row, $contacts) {
            static $lastAcc = null;
            static $lastAccName = null;
            if (!is_array($row['Konto']) && is_array($row['Verifikatid'])) {
                $row['Konto'] = $lastAcc;
                $row['Kontonamn'] = $lastAccName;
            }
            $lastAcc = $row['Konto'];
            $lastAccName = $row['Kontonamn'];

            $extraInformation = $row['ExtraInformation'][0];
            $slashPos = strrpos($extraInformation, '/');
            if ($slashPos !== false) {
                $row['Motpart'] = [substr($extraInformation, 0, $slashPos)];
                $row['MotpartId'] = [substr($extraInformation, $slashPos + 1)];
            }

            return $row;
        },
        PROP_REPORTREADER => new Reader("huvudbok", "/doc/row/Tj[text() = 'Huvudbok']", ["PageStart", "Query", "ColumnHeader", "HideRow"], false, [

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
        ])
    ],
    'huvudbok_with_summary' => [
        PROP_ROWPROCESSOR => function ($row, $contacts) {
            static $lastAcc = null;
            static $lastAccName = null;
            if (!is_array($row['Konto']) && is_array($row['Verifikatid'])) {
                $row['Konto'] = $lastAcc;
                $row['Kontonamn'] = $lastAccName;
            }
            $lastAcc = $row['Konto'];
            $lastAccName = $row['Kontonamn'];

            $extraInformation = $row['Radtext'][0];
            $slashPos = strrpos($extraInformation, '/');
            if ($slashPos !== false) {
                $row['Motpart'] = [substr($extraInformation, 0, $slashPos)];
                $row['MotpartId'] = [substr($extraInformation, $slashPos + 1)];
            }

            foreach (['Kredit', 'Debet', 'Radsumma', 'BalansIn', 'SaldoIn'] as $columnName) {
                $row[$columnName] = array_map(function ($value) {
                    return str_replace(' ', '', $value);
                }, $row[$columnName]);
            }

            return $row;
        },
        PROP_REPORTREADER => new Reader("huvudbok_with_summary", "/doc/row/Tj[text() = 'Huvudbok med radsaldo']", ["PageStart", "Query", "ColumnHeader", "HideRow"], false, [

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
    ],
    'apartments' => [
        PROP_ROWPROCESSOR => function ($row, $contacts) {
            $row['Insats'] = [preg_replace('/\D/', '', $row['Insats'][0])];
            $row['UpplatelseAvgift'] = [preg_replace('/\D/', '', $row['UpplatelseAvgift'][0])];
            return $row;
        },
        PROP_COLUMNS => ['LghData','Area','ProcentArsavgift','UpplatelseAvgift','Insats','AdressPostnr','AdressGata','Objekt','Vaning'],
        PROP_REPORTREADER => new Reader("apartments", "/doc/row/Tj[text() = 'LÄGENHETSFÖRTECKNING']", ["PageStart"], false, [

            new PositionRule("PageStart", "1454 -1422", true),
            new TextRule("PageStart", 'LÄGENHETSFÖRTECKNING', true),
            new TextRule("LghData", 'rum och', true, ["Area", "ProcentArsavgift", "UpplatelseAvgift", "Insats", "AdressPostnr"]),

            new TextRule("Objekt", '^18916-'),
            new PositionRule("AdressGata", "-5051 -2"),
            new PositionRule("Objekt", "-1710"),

            new TextRule("Vaning", 'tr$'),
            new TextRule("Vaning", 'NB'),
            new PositionRule("AdressGata", "-5051 -2")

        ])
    ],
    'resultatrakning' => [
        PROP_REPORTREADER => new Reader("resultatrakning", "/doc/row/Tj[text() = 'Resultaträkning']", ["PageStart", "Query"], true, [

            new TextRule("PageStart", '^Resultat', false),
            new PositionRule("PageStart", "-40.35 -12"),
            new PositionRule("Rubrik", "0 -27.5"),
            new PositionRule("Rubrik", "-371.25 -13.75"),
            new TextRule("PageStart", '\d{4}-\d{2}-\d{2}', false),

            new TextRule("PageStart", '^Sida', false),
            new TextRule("PageStart", 'Incit Xpand', false),

            new TextRule("Konto", '^\d{4}$', true, ["Kontonamn", "AccUtfall", "BudgetHelar", "Procent", "PeriodUtfall", "AccUtfallForegAr"]),
            new TextRule("Rubrik", '^summa', false, ["AccUtfall", "BudgetHelar", "Procent", "PeriodUtfall", "AccUtfallForegAr"])
        ])
    ],
    'klientmedelskonto' => [
        PROP_COLUMNS => ['Debet', 'Radsaldo', 'Kredit', 'Verifikatid', 'Radtext', 'BokfDatum', 'Motpart', 'MotpartId'],
        PROP_ROWPROCESSOR => function ($row, $contacts) {
            $extraInformation = $row['Radtext'][0];
            $slashPos = strrpos($extraInformation, '/');
            if ($slashPos !== false) {
                $row['Motpart'] = [substr($extraInformation, 0, $slashPos)];
                $row['MotpartId'] = [substr($extraInformation, $slashPos + 1)];
            }

            foreach (['Kredit', 'Debet', 'Radsaldo'] as $columnName) {
                $row[$columnName] = array_map(function ($value) {
                    return str_replace(' ', '', $value);
                }, $row[$columnName]);
            }

            return $row;
        },
        PROP_SUMMARY_GENERATOR => function ($data) {
            $res = [];
            $sum = 0.0;
            foreach ($data as $row) {
                $debtee = $row['Motpart'][0];
                if (!empty($debtee)) {
                    $amount = 1.0 * intval(preg_replace('/\D/', '', $row['Kredit'][0])) / 100;

                    $res[$debtee]['Motpart'][0] = $debtee;
                    $res[$debtee]['TotalKredit'][0] += $amount;
                    $res[$debtee]['AntalKredit'][0]++;
                    $sum += $amount;
                }
            }
            $res["sum"]['Motpart'][0] = "SUMMA";
            $res["sum"]['TotalKredit'][0] = $sum;
            $res["sum"]['AntalKredit'][0] = count($data);
            return array_values($res);
        },
        PROP_REPORTREADER => new Reader("klientmedelskonto", "/doc/row/Tj[text() = 'EF - Klientmedelskonto']", [], false, [

//            new TextRule("PageStart", '^Resultat', false),
//            new PositionRule("PageStart", "-40.35 -12"),
//            new PositionRule("Rubrik", "0 -27.5"),
//            new PositionRule("Rubrik", "-371.25 -13.75"),
//            new TextRule("PageStart", '\d{4}-\d{2}-\d{2}', false),
            new TextRule("Summering", 'ndring:', true),

            new TextRule("PageStart", '^Sida', false),
            new TextRule("PageStart", 'Incit Xpand', false),

            new TextRule("Verifikatid", '^18916\w{2}\d{4}', true, ["Radtext", "BokfDatum"]),
            new TextRule("Kredit", '^-\d[\s\d]*,\d{2}', false, ["Radsaldo"]),
            new TextRule("Debet", '^\d[\s\d]*,\d{2}', false, ["Radsaldo"]),
        ])
    ]
];

?>