<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
// Include Composer autoloader if not already done.
include '../vendor/autoload.php';

require_once 'renderer/BootstrapHtmlRenderer.php';
require_once 'ReportReader.php';
require_once 'config.php';
require_once 'PdfParserWrapper.php';
require_once 'google-util.php';

$isAccessTokenSet = isset($_SESSION['access_token']) && $_SESSION['access_token'];
if (!$isAccessTokenSet) {
    header('Location: ' . filter_var(GOOGLE_OAUTHCALLBACK_URI, FILTER_SANITIZE_URL));
}

$reportId = $_GET['report'];

$client = createGoogleClient();

$client->setAccessToken($_SESSION['access_token']);

function create_column_filter_function($columns)
{
    return function ($obj) use ($columns) {
        return array_intersect_key($obj, $columns);
    };
}

function create_starts_with_function($prefix)
{
    return function ($str) use ($prefix) {
        return substr($str, 0, strlen($prefix)) == $prefix;
    };
}

function printReport($title, $contacts)
{
    global $REPORTS;
    $joinAll = function ($a, $b) {
        $compA = join(array_map("join", $a));
        $compB = join(array_map("join", $b));
        return strcmp($compA, $compB);
    };

    $files = scandir(FILES_FOLDER, SCANDIR_SORT_DESCENDING);
    $renderer = new BootstrapHtmlRenderer();
    $rendererCfg = simplexml_load_file("cfg.xml");

    $reportCfg = $REPORTS[$title];

    // Configuration specifies columns in array. Filtering function should use array values as keys.
    $columns = array_fill_keys($reportCfg['columns'], null);

    $joiner = isset($reportCfg['columns']) ? create_column_filter_function($columns) : null;

    // Pick the 3 most recent PDF for current type of report
    $reportFiles = array_slice(array_filter($files, create_starts_with_function($title)), 0, 3);

    $reportsData = [];

    foreach ($reportFiles as $file) {
        if (substr($file, 0, strlen($title)) == $title) {
            $filename = FILES_FOLDER . $file;

            $wrapper = new PdfParserWrapper();
            $content = $wrapper->pdfToXml($filename);

            $xml = simplexml_load_string($content);

            $reader = new ReportReader();
            $apts = $reader->getReportObjects($rendererCfg, $xml);

            if (isset($reportCfg['rowprocessor'])) {
                $rowprocessor = $reportCfg['rowprocessor'];
                $fn = function ($apt) use ($contacts, $rowprocessor) {
                    return $rowprocessor($apt, $contacts);
                };
                $apts = array_map($fn, $apts);
            }

            if (isset($apts)) {
                if (isset($joiner)) {
                    $apts = array_map($joiner, $apts);
                }
                $reportsData[$file] = $apts;
            } else {
                echo "<p>$filename kan inte l&auml;sas.</p>";
            }
        }
    }
    $reportFiles = array_keys($reportsData);
    foreach ($reportFiles as $i => $file) {
        printf('<h3>%s <small>%s</small></h3>',
            substr(substr($file, 0, -4), strlen($title) + 1),
            $i == 0 ? "Nul&auml;ge" : "Enbart skillnader");

        if ($i == 0) {
            $renderer->write($reportsData[$file]);

            if (isset($reportCfg['summarygenerator'])) {
                $rowprocessor = $reportCfg['summarygenerator'];
                $summaryData = $rowprocessor($reportsData[$file]);
                $renderer->write($summaryData);
            }
        }
        if ($i < count($reportsData) - 1) {
            $newEntries = array_udiff($reportsData[$file], $reportsData[$reportFiles[$i + 1]], $joinAll);
            if (count($newEntries) > 0) {
                echo '<div class="diff">';
                echo "<p>Nya sedan f&ouml;rra rapporten (p&aring; denna men inte p&aring; n&auml;sta):</p>";
                $renderer->write($newEntries);
                echo '</div>';
            }
            $deletedEntries = array_udiff($reportsData[$reportFiles[$i + 1]], $reportsData[$file], $joinAll);
            if (count($deletedEntries) > 0) {
                echo '<div class="diff">';
                echo "<p>Borttagna sedan f&ouml;rra rapporten (p&aring; n&auml;sta men inte p&aring; denna):</p>";
                $renderer->write($deletedEntries);
                echo '</div>';
            }
        }
    }
}

function printReportsMenu($selectedReportId)
{
    global $REPORTS;
    print join('', array_map(function ($title, $reportCfg) use ($selectedReportId) {
        return sprintf('<li class="%s"><a href="?report=%s">%s</a></li>', $title == $selectedReportId ? 'active' : '', $title, $reportCfg['title']);
    }, array_keys($REPORTS), $REPORTS));
}


?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>F&ouml;rvaltningsrapporter</title>
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

    <?php include("index-generator-head.php") ?>
</head>
<body>
<div class="container-fluid">
    <p style="position: absolute; top: 0; right: 0; padding: 0.3em;">
        <a href="auth-signout.php">Logga ut och logga in</a>
    </p>
    <div class="page-header">
        <h1>F&ouml;rvaltningsrapporter</h1>
    </div>

    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <!-- Brand and toggle get grouped for better mobile display -->
            <div class="navbar-header">
                <a class="navbar-brand" href="#">Rapporter:</a>
            </div>
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <?php
                    printReportsMenu($reportId);
                    ?>
                </ul>
            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
    <?php

    $contacts = getGoogleContacts($client);

    if (isset($REPORTS[$reportId])) {
        printReport($reportId, $contacts);
    }
    ?>
</div>
<?php include("index-generator-form.php") ?>
<?php include("index-json-form.php") ?>
</body>
</html>

