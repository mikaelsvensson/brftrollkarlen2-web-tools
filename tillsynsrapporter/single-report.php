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
</head>
<body>
<div class="container-fluid">
<?php
include_once "SimpleXSLX.php";
include_once "core.php";

$file = $_GET["file"];
if (!file_exists($file)) {
    die("$file does not exist.");
}
if (substr($file, -5) != ".xlsx") {
    die("$file does not end with .xlsx");
}
$report = loadReport($file);

printf('<h1>%s</h1>', getReportId($file));
echo '<table class="table">';
echo '<thead>';
echo '<tr>';
echo "<th>" . join("</th><th>", ['Fastighet', 'Objekt', 'Anm&auml;rkning', 'Utan A.', 'Kommentar', 'Data']) . "</th>";
echo '</tr>';
echo '</thead>';
echo '<tbody>';
foreach ($report as $entry) {
    echo '<tr>';
    echo "<td>" . join("</td><td>", array_slice($entry, 0, -4)) . "</td>";
    echo '</tr>';
}
echo '</tbody>';
echo '</table>';
?>
</div>
</body>
</html>
