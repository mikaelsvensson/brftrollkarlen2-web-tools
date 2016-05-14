<?php
function loadReport($file)
{
    $xlsx = new SimpleXLSX($file);
    $rows = array_slice($xlsx->rows(), 2);
    $Fastighet = null;
    $Objekt = null;
    $Anmarkning = null;
    $UtanAnmarkning = null;
    $Kommentar = null;
    $Data = null;
    $res = [];
    foreach ($rows as $row) {
        if (empty(join("", $row))) {
            continue;
        }
        $Fastighet = !empty($row[0]) ? $row[0] : $Fastighet;
        $Objekt = !empty($row[1]) ? $row[1] : $Objekt;
        $Anmarkning = !empty($row[2]) ? "X" : "";
        $UtanAnmarkning = !empty($row[3]) ? "X" : "";
        $Kommentar = $row[4];
        $Data = $row[5];
        $value = null;
        $unit = null;
        $matches = [];
        $reportTitle = null;
        $key = null;
        if (preg_match('/^0*(\d+[\.,]?\d*)\s*(.*)/', $Data, $matches)) {
            $swedishNewMeterPattern = "/ny m.+tare/i";
            $reportTitle = trim(preg_replace($swedishNewMeterPattern, "", "{$Fastighet}, {$Objekt}, {$Kommentar}"));
            $value = $matches[1];
            if (!empty($matches[2])) {
                $unit = $matches[2];
            }
            $key = getReportId($file);
            if (isset($unit)) {
                $Kommentar .= strtolower(" [{$unit}]");
            }
        }

        $res[] = [$Fastighet, $Objekt, $Anmarkning, $UtanAnmarkning, $Kommentar, $Data, $reportTitle, $key, $value, $unit];
    }
    return $res;
}

function getReportId($file)
{
    $numbers = [];
    preg_match_all('/[1-9]\d*/', $file, $numbers);
    $padder = function ($s) {
        return str_pad($s, 4, "0", STR_PAD_LEFT);
    };
    $key = join("-", array_map($padder, $numbers[0]));
    return $key;
}

?>