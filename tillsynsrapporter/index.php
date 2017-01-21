<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title></title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">
    <link rel="stylesheet" href="style.css">
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>
<body>
<div class="container-fluid">
    <h1>Enskilda Rapporter</h1>
<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
include_once "SimpleXSLX.php";
include_once "core.php";

function getReportFilePaths()
{
    $Directory = new RecursiveDirectoryIterator('.');
    $Iterator = new RecursiveIteratorIterator($Directory);
    $Regex = new RegexIterator($Iterator, '/^.+\.xlsx$/i', RecursiveRegexIterator::GET_MATCH);

    $files = [];
    foreach ($Regex as $f) {
        $files[] = $f[0];
    }
    return $files;
}

function handleUploadedReportFile($uploadPath)
{
    $year = $_POST['year'];
    if (empty($year) || !is_numeric($year)) {
        die("Ogiltigt &aring;rtal.");
    }
    $filename = $_FILES['userfile']['name'];
    if (getReportId($filename) == "") {
        die("Ogiltigt filnamn eftersom det inte inneh&aring;ller n&aring;got l&ouml;pnummer (anv&auml;s n&auml;r filerna sorteras i nummerordning).");
    }
    mkdir($year, 0700);
    $destination = $year . "/" . $filename;
    move_uploaded_file($uploadPath, $destination);
}

$removeRatio = isset($_GET['removeRatio']) ? $_GET['removeRatio'] : 100;
$uploadPath = $_FILES['userfile']['tmp_name'];
if (!empty($uploadPath)) {
    handleUploadedReportFile($uploadPath);
}

$config = json_decode(file_get_contents("config.json"));

$files = getReportFilePaths();

$dataPoints = [];
$reports = [];

foreach ($files as $file) {
    $report = loadReport($file, $config);
    $reports[$file] = $report->payload;
}

$links = array_combine(array_map("getReportId", array_keys($reports)), array_keys($reports));
ksort($links);

echo "<p>";
foreach ($links as $title => $file) {
    printf('<span><a href="single-report.php?file=%s">%s</a> </span>', urlencode($file), $title);
}
echo "</p>";
?>
    <form enctype="multipart/form-data" action="." method="POST">
        <input type="hidden" name="MAX_FILE_SIZE" value="300000"/>

        <div class="form-group">
            <label for="file">V&auml;lj vilken rapportfil du vill ladda upp</label>
            <input name="userfile" type="file" id="file"/>
        </div>
        <div class="form-group">
            <label for="file">&Aring;r</label>
            <input name="year" type="number" id="file" value="<?=date("Y")?>" maxlength="4" size="4" min="2000"/>
        </div>
        <button type="submit" class="btn btn-default">Ladda upp</button>
    </form>
    <h1>Anteckningar fr&aring;n de 10 senaste rapporterna</h1>
<?php
$dataPoints = [];
$checkPoints = [];
foreach ($reports as $file => $data) {
//    var_dump($data);
    foreach ($data as $entry) {
        list ($building, $object, $note, $noNote, $comment, $data, $reportTitle, $key, $value, $unit) = $entry;

        if (isset($reportTitle) && isset($value)) {
            $dataPoints[$reportTitle][$key] = $value;
        } else {
            $building = preg_replace('/(Cirkus|Tombola|Karusell)\\w*/u','$1v',$building);
            $title = sprintf('%s, %s', $object, $building);
            $checkPoints[$title][$key] = "$note $noNote $comment";
        }
    }
}

$allKeys = array_unique(array_merge(array_map(function ($k, $v) {
//    var_dump($k);
//    var_dump($v);
    return array_keys($v);
}, array_keys($checkPoints), $checkPoints)))[0];
//var_dump($allKeys);
$latest = 10;
sort($allKeys);
if (isset($latest)) {
    $allKeys = array_slice($allKeys, -$latest);
}

printf('<table class="table table-striped table-hover table-condensed"><thead>');
printf("<tr><td></td>");
foreach ($allKeys as $key) {
    printf('<td class="text-nowrap">%s</td>', $key);
}
printf('</tr></thead><tbody>');
ksort($checkPoints);
foreach ($checkPoints as $title => $pairs) {
    if (strlen(trim(join("", $pairs))) > 0) {
        printf('<tr><td class="text-nowrap">%s</td>', $title);
        foreach ($allKeys as $key) {
            printf('<td class="text-nowrap narrow-column" title="%s">%s</td>', $pairs[$key], $pairs[$key]);
        }
        printf("</tr>");
    }
}
printf('</tbody></table>');
?>
    <h1>Diagram</h1>
<?php
foreach ($dataPoints as $graph => $pairs) {

    $matches = array_filter($config->graphs, function ($object) use ($graph) {
        return in_array($graph, $object->reportTitles, false);
    });
    $graphConfig = array_values($matches)[0];
    $title = isset($graphConfig->title) ? $graphConfig->title : $graph;
    $description = $graphConfig->description;

    $chartId = md5($graph);
    printf("<h3>%s</h3>", $title);
    if (isset($description)) {
        printf("<p>%s</p>", $description);
    }

    ksort($pairs);

    $removedPairs = [];
    $keys = array_keys($pairs);

    $lastReportId = $keys[count($keys) - 1];
    $d = new DateTime(substr($lastReportId, 0, 4) . '-01-01');
    $d->modify('+'.substr($lastReportId, 5).' weeks');
    $daysSinceLastMeasurement = $d->diff(new DateTime())->days;
    if ($daysSinceLastMeasurement > 90) {
        printf('<p>Hoppar &ouml;ver diagrammet eftersom inga v&auml;rden rapporterats p&aring; %s dagar.</p>', $daysSinceLastMeasurement);
        continue;
    }

    printf('<div id="chart-%s">Laddar diagram...</div>', $chartId);

    $newPairs = [];
    $lastValid = null;
    $decrements = 0;
    for ($i = 0; $i < count($keys); $i++) {
        $thisValue = $pairs[$keys[$i]];
        $thisValue = str_replace(',', '.', $thisValue);
        if (isset($lastValid)) {
            $ratio = 1.0 * $thisValue / $lastValid;
            if ($ratio < (1 / $removeRatio) || $ratio > $removeRatio) {
                $removedPairs[$keys[$i]] = $thisValue;
                continue;
            }
        }
        $newPairs[$keys[$i]] = $thisValue;
        if ($thisValue < $lastValid) {
            $decrements++;
        }
        $lastValid = $thisValue;
    }
    $pairs = $newPairs;

    $jsData[$chartId] = join(',', array_map(function ($k, $v) {
        return "['$k', $v]";
    }, array_keys($pairs), $pairs));

    if (!$decrements) {
        printf('<p>&Ouml;kningar:</p>');
        printf('<div id="chart-%s-delta">Laddar diagram...</div>', $chartId);

        $deltaPairs = [];
        $labels = array_keys($pairs);
        for ($i = 0; $i < count($labels); $i++) {
            $label = $labels[$i];
            $thisValue = $pairs[$label];
            $prevLabel = $labels[$i - 1];
            $prevValue = $i == 0 ? $thisValue : $pairs[$prevLabel];
            $delta = $thisValue - $prevValue;
            $deltaPairs[$label] = $delta;
        }

        $jsData["$chartId-delta"] = join(',', array_map(function ($k, $v) {
            return "['$k', $v]";
        }, array_keys($deltaPairs), $deltaPairs));
    }

    if (count($removedPairs) > 0) {
        printf('<p>Automatiskt borttagna m&auml;tv&auml;rden (<a href="?removeRatio=3">rensa mycket</a>, <a href="?removeRatio=100">rensa lite</a>):<ul><li>%s</li></ul></p>', join('</li><li>', array_map(function ($k, $v) {
            return "$k: $v";
        }, array_keys($removedPairs), $removedPairs)));
    }
}
?>
</div>
<script type="text/javascript">

    // Callback that creates and populates a data table,
    // instantiates the pie chart, passes in the data and
    // draws it.
    function drawChart() {

        <?php foreach ($jsData as $chartId => $data) { ?>

        // Create the data table.
        var data = new google.visualization.DataTable();
        data.addColumn('string', 'Rapport');
        data.addColumn('number', 'Matvarde');
        data.addRows([
            <?=$data?>
        ]);

        // Set chart options
        var options = {
            width: screen.availWidth - 100,
            height: <?=substr($chartId, -6) == '-delta' ? 200 : 300?>,
            legend: {position: "none"},
            colors: ['<?=substr($chartId, -6) == '-delta' ? '#84BB61' : '#217c00'?>']
        };

        // Instantiate and draw our chart, passing in some options.
        var chart = new google.visualization.ColumnChart(document.getElementById('chart-<?=$chartId?>'));
        chart.draw(data, options);

        <?php } ?>
    }

    // Load the Visualization API and the corechart package.
    google.charts.load('current', {'packages': ['corechart', 'bar']});

    // Set a callback to run when the Google Visualization API is loaded.
    google.charts.setOnLoadCallback(drawChart);
</script>
</body>
</html>
