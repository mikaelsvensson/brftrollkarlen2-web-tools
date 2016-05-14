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
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
</head>
<body>
<div class="container-fluid">
    <h1>Enskilda Rapporter</h1>
<?php
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

$uploadPath = $_FILES['userfile']['tmp_name'];
if (!empty($uploadPath)) {
    handleUploadedReportFile($uploadPath);
}

$files = getReportFilePaths();

$dataPoints = [];
$reports = [];

foreach ($files as $file) {
    $reports[$file] = loadReport($file);
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
    <h1>Sammanfattande Diagram</h1>
<?php
$dataPoints = [];
foreach ($reports as $file => $data) {
    foreach ($data as $entry) {
        list (, , , , , , $reportTitle, $key, $value, $unit) = $entry;
        if (isset($reportTitle)) {
            $dataPoints[$reportTitle][$key] = $value;
        }
    }
}

foreach ($dataPoints as $graph => $pairs) {
    $chartId = md5($graph);
    printf("<h3>%s</h3>", $graph);
    printf('<div id="chart-%s">Laddar diagram...</div>', $chartId);

    ksort($pairs);

    $removedPairs = [];
    $keys = array_keys($pairs);
    $newPairs = [];
    $lastValid = null;
    $decrements = 0;
    for ($i = 0; $i < count($keys); $i++) {
        $thisValue = $pairs[$keys[$i]];
        $thisValue = str_replace(',', '.', $thisValue);
        if (isset($lastValid)) {
            $ratio = 1.0 * $thisValue / $lastValid;
            if ($ratio < (1 / 4) || $ratio > 4) {
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
        printf('<p>Automatiskt borttagna m&auml;tv&auml;rden:<ul><li>%s</li></ul></p>', join('</li><li>', array_map(function ($k, $v) {
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
            height: 300,
            legend: {position: "none"}
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
