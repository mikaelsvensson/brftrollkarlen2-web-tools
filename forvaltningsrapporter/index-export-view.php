<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
const WORDS = [
    "person",
    "kvinna",
    "tid",
    "plats",
    "hand",
    "huvud",
    "ansikte",
    "pengar",
    "hus",
    "minut",
    "man",
    "dag",
    "match",
    "hand",
    "folk",
    "barn",
    "bord",
    "land",
    "pojke",
    "bil",
    "skola",
    "pappa",
    "golv",
    "meter",
    "vatten",
    "natt",
    "leende",
    "klocka",
    "jobb",
    "stad",
    "fotboll",
    "film",
    "musik",
    "barn",
    "flicka",
    "procent",
    "trappa",
    "luft",
    "glas",
    "doktor",
    "kaffe",
    "klocka",
    "tanke",
    "hund",
    "morgon",
    "salt",
    "hals",
    "bild",
    "sommar",
    "hav",
    "himmel",
    "ljus",
    "dotter",
    "kapten",
    "arm",
    "bord",
    "telefon",
    "framtid",
    "bror",
    "moster",
    "energi",
    "skatt",
    "musik",
    "pojke",
    "finger",
    "tidning",
    "dotter",
    "axel",
    "ljud",
    "kille",
    "guld",
    "familj",
    "flicka",
    "kvalitet",
    "skratt",
    "siffra",
    "forskare",
    "kontor",
    "trafik",
    "historia",
    "paket",
    "vardagsrum",
    "flod",
    "debatt",
    "soffa",
    "centrum",
    "skjorta",
    "eftermiddag",
    "tjej",
    "spegel",
    "syster",
    "bostad",
    "vinter",
    "jord",
    "flaska",
    "restaurang",
    "resa",
    "ishockey"];

header("Content-Type: text/plain");
header("Content-Disposition: attachment; filename=export.csv");
$data = unserialize(base64_decode($_POST["data"]));
//var_dump($_POST);
if ("true" != $_POST["add_headers"]) {
    array_shift($data);
}
if ("true" == $_POST["add_row_number"]) {
    array_walk($data, function (&$row, $i) {
        array_unshift($row, $i + 1);
    });
}
if ("true" == $_POST["add_unique_id"]) {
    array_walk($data, function (&$row, $i) {
        array_unshift($row, bin2hex(openssl_random_pseudo_bytes(10)));
    });
}
if ("true" == $_POST["add_threeword_password"]) {
    $usedPwds = [];
    array_walk($data, function (&$row, $i) use ($usedPwds) {
        do {
            $x = 0;
            $value = "";
            while ($x++ < 3) {
                $value .= ($value != "" ? "-" : "") . WORDS[rand(0, count(WORDS) - 1)];
            }
        } while (in_array($value, $usedPwds));
        $usedPwds[] = $value;
        array_unshift($row, $value);
    });
}
if ("true" == $_POST["add_alphanumeric_password"]) {
    array_walk($data, function (&$row, $i) {
        $i = 0;
        $value = "";
        $chars = "qwertyupasdfghjkzxcvbnm23456789";
        while ($i++ < 10) {
            $value .= $chars[rand(0, strlen($chars) - 1)];
        }
        array_unshift($row, $value);
    });
}
$file = tmpfile();
array_walk($data, function ($row) use ($file) {
    fputcsv($file, $row);
});
rewind($file);
fpassthru($file);
fclose($file);