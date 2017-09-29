<?php
// Include Composer autoloader if not already done.
include '../vendor/autoload.php';

//TODO Rename config folder as to not confuse it with config.php
require_once 'config/BaseRule.php';
require_once 'config/PositionRule.php';
require_once 'config/Reader.php';
require_once 'config/TextRule.php';
require_once 'config/Report.php';

use Config\PositionRule;
use Config\Reader;
use Config\Report;
use Config\TextRule;

$defaultParams = ['DATUM' => date('Ymd-His')];

$cfg = parse_ini_file("config.ini", true);

function fixFormattedCurrency(&$row, $columnNames)
{
    foreach ($columnNames as $columnName) {
        if (is_array($row[$columnName])) {
            $row[$columnName] = array_map("removeSpaces", $row[$columnName]);
        }
    }
}

function removeSpaces($value)
{
    return str_replace(' ', '', $value);
}

//TODO: Create utility class for Google Contacts?
function getGoogleContacts($client)
{
    $feedURL = "https://www.google.com/m8/feeds/contacts/default/full?max-results=1000&alt=json";
//    $req = new Google_Http_Request($feedURL, 'GET', array("GData-Version" => "3.0"));
//    $val = $client->getAuth()->authenticatedRequest($req);

    // The contacts api only returns XML responses.
//    $responseRaw = $val->getResponseBody();


    // returns a Guzzle HTTP Client
    $httpClient = $client->authorize();

    // make an HTTP request
    $response = $httpClient->get($feedURL);

    if ($response->getStatusCode() != 200) {
        printf('<p>Kunde inte h&auml;mta kontaktlistan. Felkod %d.</p>', $response->getStatusCode());
    }

    $body = json_decode($response->getBody(), true)['feed']['entry'];

    $contacts = array_map(function ($contact) {
        $aptAccessFromDateProps = isset($contact['gContact$userDefinedField']) && is_array($contact['gContact$userDefinedField']) ? array_filter($contact['gContact$userDefinedField'],
            function ($item) {
                return strpos($item['key'], 'Tilltr') != -1;
            }) : [];
        return [
            'name' => isset($contact['title']) ? $contact['title']['$t'] : null,
            'updated' => $contact['updated']['$t'],
            'note' => isset($contact['content']) ? $contact['content']['$t'] : null,
            'email' => isset($contact['gd$email']) ? $contact['gd$email'][0]['address'] : null,
            'phone' => isset($contact['gd$phoneNumber']) ? $contact['gd$phoneNumber'][0]['$t'] : null,
            'orgName' => isset($contact['gd$organization']) && isset($contact['gd$organization'][0]['gd$orgName']) ? $contact['gd$organization'][0]['gd$orgName']['$t'] : null,
            'orgTitle' => isset($contact['gd$organization']) && isset($contact['gd$organization'][0]['gd$orgTitle']) ? $contact['gd$organization'][0]['gd$orgTitle']['$t'] : null,
            'aptAccessFrom' => count($aptAccessFromDateProps) > 0 ? $aptAccessFromDateProps[0]['value'] : null,
            'address' => isset($contact['gd$postalAddress']) ? explode("\n", $contact['gd$postalAddress'][0]['$t']) : null
        ];
    }, $body);
    return $contacts;
}

function findContacts($row, $contacts, $column)
{
    $fn = function ($value) {
        $nameParts = explode(' ', $value);
        sort($nameParts);
        return implode(' ', array_map("soundex", $nameParts));
    };
    $str = @$fn($row[$column][0]);
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

function getDebtReportConfig()
{
    $report = new Report('Hyresfordran');
    $report->setColumns(['LghNr', 'Fakturanr', 'Restbelopp', 'Hyresgast', 'Forfallodatum', 'DagarForsening', 'KontaktNamn', 'KontaktEpost', 'KontaktTelefon']);
    $report->setUrl('https://entre.stofast.se/ts/portaler/portal579.nsf/0/5CA3A55A0BECD55DC12579C50042A060/$File/48775_1001.pdf?openElement');
    $report->setRowProcessor(function ($row, $contacts) {
        if (isset($row['Objekt'])) {
            $row['LghNr'] = [intval(substr($row['Objekt'][0], 6), 10)];
        }

        $date = DateTime::createFromFormat('Y-m-d', $row['Forfallodatum'][0]);
        $interval = date_diff(new DateTime(), $date);
        $row['DagarForsening'] = [$interval->days];

        addContactColumn($row, $contacts, 'Hyresgast');

        return $row;
    });
    $report->setSummaryGenerator(function ($data) {
        $res = [];
        $sum = 0.0;
        foreach ($data as $row) {
            $debtee = $row['Hyresgast'][0];
            $amount = intval(preg_replace('/\D/', '', $row['Restbelopp'][0]));

            @$res[$debtee]['Hyresgast'][0] = $debtee;
            @$res[$debtee]['TotalRestbelopp'][0] += $amount;
            @$res[$debtee]['AntalRestbelopp'][0]++;
            $sum += $amount;
        }
        @$res["sum"]['Hyresgast'][0] = "SUMMA";
        @$res["sum"]['TotalRestbelopp'][0] = $sum;
        @$res["sum"]['AntalRestbelopp'][0] = count($data);
        return array_values($res);
    });
    $report->setReportReader(new Reader("hyresfordran", "/doc/row/Tj[text() = 'HYRESFORDRAN']", ["Header", "Footer"], false, [
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
    ]));
    return $report;
}

function getMembersReportConfig()
{
    $report = new Report('Medlemmar');
    $report->setUrl('https://entre.stofast.se/ts/portaler/portal579.nsf/0/563B9D7BB796E5D7C12579B70048844B/$File/48775_1005.pdf?openElement');
    $report->setRowProcessor(function ($row, $contacts) {
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
    });
    $report->setReportReader(new Reader("medlemsforteckning", "/doc/row/Tj[text() = 'Medlemsförteckning']", ["Header", "Footer"], false, [

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
    ]));
    return $report;
}

function getContractsReportConfig()
{
    $report = new Report('Kontrakt');
    $report->setColumns(['Objekt', 'Typ', 'Hyresgast', 'Area', 'Fran', 'Till', 'DagarKvar', 'KontaktNamn', 'KontaktEpost', 'KontaktTelefon']);
    $report->setUrl('https://entre.stofast.se/ts/portaler/portal579.nsf/0/F0DAE4CB20A599E8C12579C50042A74F/$File/48775_1002.pdf?openElement');
    $report->setRowProcessor(function ($row, $contacts) {
        if (isset($row['Kontraktstid'])) {
            $period = $row['Kontraktstid'][0];
            list($from, $to) = explode(' - ', $period);
            $row['Fran'] = [$from];
            $row['Till'] = [$to];
            $toDate = DateTime::createFromFormat('Y-m-d', $to);
            $interval = date_diff(new DateTime(), $toDate);
            $row['DagarKvar'] = [$interval->days];
        }
        $row['Objekt'] = [substr($row['Objekt'][0], 6)];

        addContactColumn($row, $contacts, 'Hyresgast');

        return $row;
    });
    $report->setReportReader(new Reader("kontrakt-upphor", "/doc/row/Tj[text() = 'KONTRAKT UPPHÖR']", ["Header", "Footer"], false, [

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

    ]));
    return $report;
}

function getSharesReportConfig()
{
    $report = new Report('Andelstal');
    $report->setUrl('https://entre.stofast.se/ts/portaler/portal579.nsf/0/C99AFBE828B40038C12579F900117247/$File/48775_1004.pdf?openElement');
    $report->setRowProcessor(function ($row) {
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
    });
    $report->setReportReader(new Reader("andelstal", "/doc/row/Tj[text() = 'Andelstal']", ["Header", "Footer"], false, [

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
    ]));
    return $report;
}

function getObjectsReportConfig()
{
    $report = new Report('Objektsf&ouml;rteckning');
    $report->setColumns(['LghNr', 'Objekt', 'Namn1', 'Namn2', 'AdressGata', 'AdressVaning', 'AdressPostnr', 'AdressPost', 'Typ', 'Datum', 'KontaktNamn', 'KontaktEpost', 'KontaktTelefon', 'LghArea', 'Belopp', 'LghData']);
    $report->setUrl('https://entre.stofast.se/ts/portaler/portal579.nsf/0/3C82828B45507253C12579C500429889/$File/48775_1003.pdf?openElement');
    $report->setRowProcessor(function ($row, $contacts) {
        $row['LghNr'] = [intval(substr($row['Objekt'][0], 6), 10)];
        $row['Objekt'] = array_unique($row['Objekt']);

        if (isset($row['Belopp'])) {
            fixFormattedCurrency($row, ['Belopp']);
            $row['Belopp'] = array_unique($row['Belopp']);
        }

        if (isset($row['LghData'])) {
            // Remove trailing marker for "Bostadsrätt"
            $row['LghData'] = [str_replace(" B", "", $row['LghData'][0])];
        }

        addContactColumn($row, $contacts, 'Namn1');

        return $row;
    });
//            setSummaryGenerator(function ($data) {
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
//            })
    $report->setReportReader(new Reader("objektsforteckning-hyresgastforteckning", "/doc/row/Tj[text() = 'HYRESGÄSTFÖRTECKNING']", ["Header", "Footer"], false, [
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
        new TextRule("LghArea", '\d{1,2},0', false, ["ContractArea"]),
        new PositionRule("LghData", "0 -240"),
        new PositionRule("Belopp", "-802 -1166"),
        new PositionRule("Belopp", "6452 0"),
        new PositionRule("Belopp", "6374 0"),
        new PositionRule("Belopp", "6374 240"),
        new PositionRule("Avisering", "-12854 -720"),
        new PositionRule("AdressGata", "0 -480"),
        new PositionRule("AdressPost", "0 240"),
        new PositionRule("AdressGata", "0 -720"),
        new PositionRule("AdressGata", "1440 480"),
    ], function ($previous, $current) {
        return $previous["Objekt"][0] == $current["Objekt"][0];
    }));
    return $report;
}


function getAccountingBooksReportConfig()
{
    $report = new Report();
    $report->setRowProcessor(function ($row, $contacts) {
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
    });
    $report->setReportReader(new Reader("huvudbok", "/doc/row/Tj[text() = 'Huvudbok']", ["PageStart", "Query", "ColumnHeader", "HideRow"], false, [

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
    ]));
    return $report;
}

function getAccountingBooksWithSummaryReportConfig()
{
    $report = new Report();
    $report->setRowProcessor(function ($row, $contacts) {
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

        fixFormattedCurrency($row, ['Kredit', 'Debet', 'Radsumma', 'BalansIn', 'SaldoIn']);

        return $row;
    });
    $report->setReportReader(new Reader("huvudbok_with_summary", "/doc/row/Tj[text() = 'Huvudbok med radsaldo']", ["PageStart", "Query", "ColumnHeader", "HideRow"], false, [

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
    ]));
    return $report;
}

function getApartmentsReportConfig()
{
    $report = new Report('M&auml;klarbild');
    $report->setRowProcessor(function ($row, $contacts) {
        global $cfg;
        $row['Insats'] = [preg_replace('/\D/', '', $row['Insats'][0])];
        $row['UpplatelseAvgift'] = [preg_replace('/\D/', '', $row['UpplatelseAvgift'][0])];
        $apt_number = substr($row['Objekt'][0], 6);
        $filename = "maklarbild.lgh-$apt_number.pdf";
        if (file_exists($cfg['reports']['archive_folder'] . $filename)) {
            $row['Maklarbild'] = [sprintf('<a href="arkiv/%s">H&auml;mta</a>', $filename)];
        }
        return $row;
    });
    $report->setUrl('https://entre.stofast.se/ts/portaler/portal579.nsf/0/95E006CB94B971C5C1257A31003E5CAB/$File/48775_1006.pdf?openElement');
    $report->setAfterDownloadProcessor(function ($filename) {
        global $cfg;

        // This post-download processor takes the full report, which has exactly one page per apartment,
        // and splits it into one new PDF per apartment.

        // Note: This might not hold true in the future for apartments which have been sold a lot of times.

        $pdf = new FPDI();

        // Find out how many pages the full report has:
        $page_count = $pdf->setSourceFile($filename);

        for ($page_number = 1; $page_number <= $page_count; $page_number++) {
            $new_pdf = new FPDI();
            $new_pdf->setSourceFile($filename);

            // Add page to new PDF (and determine if page orientation is portrait or landscape):
            $specs = $pdf->getTemplateSize($page_number);
            $new_pdf->AddPage($specs['h'] > $specs['w'] ? 'P' : 'L');
            $new_pdf->useTemplate($new_pdf->importPage($page_number));

            try {
                // Save new PDF:
                $new_filename = $cfg['reports']['archive_folder'] . "maklarbild.temp-$page_number.pdf";
                $new_pdf->Output($new_filename, "F");
                $new_pdf->Close();

                // Parse new PDF, i.e. the new PDF which only has information about a single apartment:
                $parser = new \Smalot\PdfParser\Parser();
                $new_pdf_parsed = $parser->parseFile($new_filename);

                $pages = $new_pdf_parsed->getPages();

                // There will only be one page in $pages
                foreach ($pages as $page) {
                    // Try to find the apartment id in one of the text objects in the PDF:
                    $pageContents = $page->getText();
                    $texts = explode("\n", $pageContents);
                    $text_matches = array_values(array_filter(array_map(function ($text) {
                        $matches = array();
                        $is_match = preg_match('/^\d{5}-(\d{4})$/', $text, $matches);
                        return $is_match ? $matches[1] : null;
                    }, $texts), function ($value) {
                        return $value != null;
                    }));

                    // Only trust the search if a single aparment id is found:
                    if (count($text_matches) == 1) {
                        $apt_number = $text_matches[0];

                        // Rename the new PDF so that the name includes the apartment number.
                        $even_newer_filename = $cfg['reports']['archive_folder'] . "maklarbild.lgh-$apt_number.pdf";
                        rename($new_filename, $even_newer_filename);
                    }
                }
            } catch (Exception $e) {
                // TODO: Exception should probably be handled in some way.
                // die($e->getMessage());
            }
        }
    });
    $report->setColumns(['LghData', 'Area', 'ProcentArsavgift', 'UpplatelseAvgift', 'Insats', 'AdressPostnr', 'AdressGata', 'Objekt', 'Vaning', 'Maklarbild']);
    $report->setReportReader(new Reader("apartments", "/doc/row/Tj[text() = 'LÄGENHETSFÖRTECKNING']", ["PageStart"], false, [

        new PositionRule("PageStart", "1454 -1422", true),
        new TextRule("PageStart", 'LÄGENHETSFÖRTECKNING', true),
        new TextRule("LghData", 'rum och', true, ["Area", "ProcentArsavgift", "UpplatelseAvgift", "Insats", "AdressPostnr"]),

        new TextRule("Objekt", '^18916-'),
        new PositionRule("AdressGata", "-5051 -2"),
        new PositionRule("Objekt", "-1710"),

        new TextRule("Vaning", 'tr$'),
        new TextRule("Vaning", 'NB'),
        new PositionRule("AdressGata", "-5051 -2")

    ]));
    return $report;
}


function getAccountingResultReportConfig()
{
    $report = new Report();
    $report->setReportReader(new Reader("resultatrakning", "/doc/row/Tj[text() = 'Resultaträkning']", ["PageStart", "Query"], true, [

        new TextRule("PageStart", '^Resultat', false),
        new PositionRule("PageStart", "-40.35 -12"),
        new PositionRule("Rubrik", "0 -27.5"),
        new PositionRule("Rubrik", "-371.25 -13.75"),
        new TextRule("PageStart", '\d{4}-\d{2}-\d{2}', false),

        new TextRule("PageStart", '^Sida', false),
        new TextRule("PageStart", 'Incit Xpand', false),

        new TextRule("Konto", '^\d{4}$', true, ["Kontonamn", "AccUtfall", "BudgetHelar", "Procent", "PeriodUtfall", "AccUtfallForegAr"]),
        new TextRule("Rubrik", '(^summa|resultat)', false, ["AccUtfall", "BudgetHelar", "Procent", "PeriodUtfall", "AccUtfallForegAr"])
    ]));
    return $report;
}

function getAccountingShorttermaccountReportConfig()
{
    $report = new Report();
    $report->setColumns(['Debet', 'Radsaldo', 'Kredit', 'Verifikatid', 'Radtext', 'BokfDatum', 'Motpart', 'MotpartId']);
    $report->setRowProcessor(function ($row, $contacts) {
        $extraInformation = $row['Radtext'][0];
        $slashPos = strrpos($extraInformation, '/');
        if ($slashPos !== false) {
            $row['Motpart'] = [substr($extraInformation, 0, $slashPos)];
            $row['MotpartId'] = [substr($extraInformation, $slashPos + 1)];
        }

        fixFormattedCurrency($row, ['Kredit', 'Debet', 'Radsaldo']);

        return $row;
    });
    $report->setSummaryGenerator(function ($data) {
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
    });
    $report->setReportReader(new Reader("klientmedelskonto", "/doc/row/Tj[text() = 'EF - Klientmedelskonto']", ["PageStart"], false, [

//            new TextRule("PageStart", '^Resultat', false),
//            new PositionRule("PageStart", "-40.35 -12"),
//            new PositionRule("Rubrik", "0 -27.5"),
//            new PositionRule("Rubrik", "-371.25 -13.75"),
//            new TextRule("PageStart", '\d{4}-\d{2}-\d{2}', false),
        new TextRule("Summering", 'ndring:', true),

        new TextRule("PageStart", '^Sida', false),
        new TextRule("PageStart", 'Incit Xpand', false),

        new TextRule("Verifikatid", '^18916\w{2}\d{4}', true),
        new TextRule("BokfDatum", '^\d{4}-\d{2}-\d{2}$', false),
        new TextRule("Kredit", '^-\d[\s\d]*,\d{2}', false, ["Radsaldo"]),
        new TextRule("Debet", '^\d[\s\d]*,\d{2}', false, ["Radsaldo"]),
        new TextRule("Radtext", '.', false),
        new TextRule("PageStart", 'Incit Xpand', false)
    ]));
    return $report;
}

$REPORTS['hyresfordran'] = getDebtReportConfig();
$REPORTS['medlemsforteckning'] = getMembersReportConfig();
$REPORTS['kontrakt-upphor'] = getContractsReportConfig();
$REPORTS['objektsforteckning-hyresgastforteckning'] = getObjectsReportConfig();
$REPORTS['huvudbok'] = getAccountingBooksReportConfig();
$REPORTS['andelstal'] = getSharesReportConfig();
$REPORTS['apartments'] = getApartmentsReportConfig();
$REPORTS['huvudbok_with_summary'] = getAccountingBooksWithSummaryReportConfig();
$REPORTS['resultatrakning'] = getAccountingResultReportConfig();
$REPORTS['klientmedelskonto'] = getAccountingShorttermaccountReportConfig();
?>