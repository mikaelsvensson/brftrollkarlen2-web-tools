<?php
function loadReport($file)
{
    $cacheFile = $file . ".json";
    if (true /*!file_exists($cacheFile)*/) {
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
            if (preg_match('/^0*(\d+[\.,]?\d*)\s*(.*)/', $Data, $matches)) {
                $swedishNewMeterPattern = "/ny m.+tare/i";
                $reportTitle = trim(preg_replace($swedishNewMeterPattern, "", "{$Fastighet}, {$Objekt}, {$Kommentar}"));
                $value = $matches[1];
                if (!empty($matches[2])) {
                    $unit = $matches[2];
                }
                if (isset($unit)) {
                    $Kommentar .= strtolower(" [{$unit}]");
                }
            } else {
                $reportTitle = trim("{$Fastighet}, {$Objekt}");
            }
            $key = getReportId($file);

            $res[] = [$Fastighet, $Objekt, $Anmarkning, $UtanAnmarkning, $Kommentar, $Data, $reportTitle, $key, $value, $unit];
        }

        $cacheEntry = array('payload' => $res);
        file_put_contents($cacheFile, json_encode($cacheEntry));
    }

    $cacheEntry = json_decode(file_get_contents($cacheFile));
    return $cacheEntry;
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