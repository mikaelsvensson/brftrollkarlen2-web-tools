<?php
const FILES_FOLDER = './reports-archive/';

const REPORT_DAYS = ["01", "03", "05", "15", "26", "28", "30"];

const REPORTS = [
    'hyresfordran' =>
        [
            'columns' => ['Fakturanr', 'Restbelopp', 'Hyresgast'],
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/5CA3A55A0BECD55DC12579C50042A060/$File/48775_1001.pdf?openElement'
        ],
    'medlemsforteckning' =>
        [
            'columns' => null,
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/563B9D7BB796E5D7C12579B70048844B/$File/48775_1005.pdf?openElement'
        ],
    'kontrakt-upphor' =>
        [
            'columns' => ['Objekt', 'Typ', 'Hyresgast', 'Area', 'Kontraktstid'],
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/F0DAE4CB20A599E8C12579C50042A74F/$File/48775_1002.pdf?openElement'
        ],
    'andelstal' =>
        [
            'columns' => null,
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/C99AFBE828B40038C12579F900117247/$File/48775_1004.pdf?openElement'
        ],
    'objektsforteckning-hyresgastforteckning' =>
        [
            'columns' => ['Id', 'Name1', 'Name2', 'Addr', 'AddrFloor', 'AddrPostNo', 'AddrPost', 'Type', 'Date'],
            'url' => 'https://entre.stofast.se/ts/portaler/portal579.nsf/0/3C82828B45507253C12579C500429889/$File/48775_1003.pdf?openElement'
        ]
];

?>