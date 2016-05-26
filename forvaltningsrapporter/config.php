<?php
const FILES_FOLDER = './reports-archive/';

const REPORT_DAYS = ["01", "03", "05", "15", "26", "28", "30"];

$REPORTS = [
    'hyresfordran' =>
        [
            'columns' => ['LghNr', 'Fakturanr', 'Restbelopp', 'Hyresgast', 'Forfallodatum', 'DagarForsening'],
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/5CA3A55A0BECD55DC12579C50042A060/$File/48775_1001.pdf?openElement',
            'rowprocessor' => function ($row) {
                $row['LghNr'] = [intval(substr($row['Objekt'][0], 6), 10)];

                $date = DateTime::createFromFormat('Y-m-d', $row['Forfallodatum'][0]);
                $interval = date_diff(new DateTime(), $date);
                $row['DagarForsening'] = [$interval->days];

                return $row;
            }
        ],
    'medlemsforteckning' =>
        [
            'columns' => null,
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/563B9D7BB796E5D7C12579B70048844B/$File/48775_1005.pdf?openElement',
            'rowprocessor' => function ($row) {
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

                $row['Adress'] = array_unique($row['Adress']);
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


                return $row;
            }
        ],
    'kontrakt-upphor' =>
        [
            'columns' => ['Objekt', 'Typ', 'Hyresgast', 'Area', 'Fran', 'Till', 'DagarKvar'],
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/F0DAE4CB20A599E8C12579C50042A74F/$File/48775_1002.pdf?openElement',
            'rowprocessor' => function ($row) {
                $period = $row['Kontraktstid'][0];
                list($from, $to) = explode(' - ', $period);
                $row['Fran'] = [$from];
                $row['Till'] = [$to];
                $row['Objekt'] = [substr($row['Objekt'][0], 6)];

                $toDate = DateTime::createFromFormat('Y-m-d', $to);
                $interval = date_diff(new DateTime(), $toDate);
                $row['DagarKvar'] = [$interval->days];
                return $row;
            }
        ],
    'andelstal' =>
        [
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
            'columns' => ['LghNr', 'Objekt', 'Namn1', 'Namn2', 'Adress', 'AdressVaning', 'AdressPostnr', 'AdressPost', 'Typ', 'Datum'],
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/3C82828B45507253C12579C500429889/$File/48775_1003.pdf?openElement',
            'rowprocessor' => function ($row) {
                $row['LghNr'] = [intval(substr($row['Objekt'][0], 6), 10)];
                return $row;
            }
        ]
];

?>