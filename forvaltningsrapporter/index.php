<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require_once 'renderer/HtmlRenderer.php';
require_once 'ReportReader.php';
require_once 'config.php';
require_once '../lib/PdfParser.php';

$renderer = new HtmlRenderer();
$rendererCfg = simplexml_load_file("cfg.xml");

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

$joinAll = function ($a, $b) {
    $compA = join(array_map("join", $a));
    $compB = join(array_map("join", $b));
    return strcmp($compA, $compB);
};

$renderer->writerDocStart();

$files = scandir(FILES_FOLDER, SCANDIR_SORT_DESCENDING);
foreach ($REPORTS as $title => $reportCfg) {

    echo "<h1>$title</h1>";

    // Configuration specifies columns in array. Filtering function should use array values as keys.
    $columns = array_fill_keys($reportCfg['columns'], null);

    $joiner = isset($reportCfg['columns']) ? create_column_filter_function($columns) : null;

    // Pick the 3 most recent PDF for current type of report
    $reportFiles = array_slice(array_filter($files, create_starts_with_function($title)), 0, 3);

    $reportsData = [];

    foreach ($reportFiles as $file) {
        if (substr($file, 0, strlen($title)) == $title) {
            $filename = FILES_FOLDER . $file;

            $content = PdfParser::parseFile($filename);

            $xml = simplexml_load_string($content);

            $reader = new ReportReader();
            $apts = $reader->getReportObjects($rendererCfg, $xml);

            if (isset($reportCfg['rowprocessor'])) {
                $apts = array_map($reportCfg['rowprocessor'], $apts);
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
        printf('<h2>%s<span>%s</span></h2>',
            substr($file, strlen($title) + 1),
            $i == 0 ? "Nul&auml;ge" : "");

        if ($i == 0) {
            $renderer->write($reportsData[$file]);
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
$renderer->writerDocEnd();
?>