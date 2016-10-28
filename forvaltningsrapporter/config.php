<?php
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
        $aptAccessFromDateProps = array_filter($contact['gContact$userDefinedField'],
            function ($item) {
                return strpos($item['key'], 'Tilltr') != -1;
            });
        return [
            'name' => isset($contact['title']) ? $contact['title']['$t'] : null,
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
            'title' => 'Hyresfordran',
            'columns' => ['LghNr', 'Fakturanr', 'Restbelopp', 'Hyresgast', 'Forfallodatum', 'DagarForsening', 'KontaktNamn', 'KontaktEpost', 'KontaktTelefon'],
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/5CA3A55A0BECD55DC12579C50042A060/$File/48775_1001.pdf?openElement',
            'rowprocessor' => function ($row, $contacts) {
                $row['LghNr'] = [intval(substr($row['Objekt'][0], 6), 10)];

                $date = DateTime::createFromFormat('Y-m-d', $row['Forfallodatum'][0]);
                $interval = date_diff(new DateTime(), $date);
                $row['DagarForsening'] = [$interval->days];

                addContactColumn($row, $contacts, 'Hyresgast');

                return $row;
            },
            'summarygenerator' => function ($data) {
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
            }
        ],
    'medlemsforteckning' =>
        [
            'title' => 'Medlemmar',
            'columns' => null,
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/563B9D7BB796E5D7C12579B70048844B/$File/48775_1005.pdf?openElement',
            'rowprocessor' => function ($row, $contacts) {
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
            }
        ],
    'kontrakt-upphor' =>
        [
            'title' => 'Kontrakt',
            'columns' => ['Objekt', 'Typ', 'Hyresgast', 'Area', 'Fran', 'Till', 'DagarKvar', 'KontaktNamn', 'KontaktEpost', 'KontaktTelefon'],
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/F0DAE4CB20A599E8C12579C50042A74F/$File/48775_1002.pdf?openElement',
            'rowprocessor' => function ($row, $contacts) {
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
            }
        ],
    'andelstal' =>
        [
            'title' => 'Andelstal',
            'columns' => null,
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/C99AFBE828B40038C12579F900117247/$File/48775_1004.pdf?openElement',
            'rowprocessor' => function ($row) {
                static $last = null;
                if (!is_array($row['Objekt'])) {
                    $row['Objekt'] = $last;
                }
                $row['Objekt'] = [substr($row['Objekt'][0], 6)];
                $last = $row['Objekt'];

                $row['Personnr'] = [substr($row['Personnr'][0], 0, 6) . "-****"];
                return $row;
            }
        ],
    'objektsforteckning-hyresgastforteckning' =>
        [
            'title' => 'Objektsf&ouml;rteckning',
            'columns' => ['LghNr', 'Objekt', 'Namn1', 'Namn2', 'AdressGata', 'AdressVaning', 'AdressPostnr', 'AdressPost', 'Typ', 'Datum', 'KontaktNamn', 'KontaktEpost', 'KontaktTelefon'],
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/3C82828B45507253C12579C500429889/$File/48775_1003.pdf?openElement',
            'rowprocessor' => function ($row, $contacts) {
                $row['LghNr'] = [intval(substr($row['Objekt'][0], 6), 10)];

                addContactColumn($row, $contacts, 'Namn1');

                return $row;
            },
            'summarygenerator' => function ($data) {
                $res = [];
                foreach ($data as $row) {
                    $aptNumber = intval($row['LghNr'][0]);
                    if ($aptNumber < 1000) {
                        $res[$row['AdressGata'][0]]['AdressGata'] = [$row['AdressGata'][0]];
                        $floor = str_replace(' ', '', $row['AdressVaning'][0]);
                        $res[$row['AdressGata'][0]]['Apt' . $floor . ($aptNumber % 2)] = [$row['Namn1'][0] . (!empty($row['Namn2'][0]) ? ', ' . $row['Namn2'][0] : '')];
                    }
                }
                return array_values($res);
            }
        ],
    'huvudbok' => [
        'rowprocessor' => function ($row, $contacts) {
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
                $row['MotpartId'] = [substr($extraInformation, $slashPos+1)];
            }

            return $row;
        }
    ],
    'huvudbok_with_summary' => [
        'rowprocessor' => function ($row, $contacts) {
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
                $row['MotpartId'] = [substr($extraInformation, $slashPos+1)];
            }

            return $row;
        }
    ]
];

?>