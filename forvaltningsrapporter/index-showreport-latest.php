<?php
// Include Composer autoloader if not already done.
require_once '../vendor/autoload.php';
require_once 'config.php';

$cfg = parse_ini_file("config.ini", true);

require_once 'renderer/BootstrapHtmlRenderer.php';
require_once 'ReportReader.php';
require_once 'PdfParserWrapper.php';

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
    global $REPORTS, $cfg;
    $joinAll = function ($a, $b) {
        $compA = join(array_map("join", $a));
        $compB = join(array_map("join", $b));
        return strcmp($compA, $compB);
    };

    $files = scandir($cfg['reports']['archive_folder'], SCANDIR_SORT_DESCENDING);
    $renderer = new BootstrapHtmlRenderer();
    $reportCfg = $REPORTS[$title];

    // Pick the 3 most recent PDF for current type of report
    $reportFiles = array_slice(array_filter($files, create_starts_with_function($title)), 0, 3);

    $reportsData = [];

    foreach ($reportFiles as $file) {
        if (substr($file, 0, strlen($title)) == $title) {
            $filename = $cfg['reports']['archive_folder'] . $file;

            try {
                $wrapper = new PdfParserWrapper();
                $content = $wrapper->pdfToXml($filename);
            } catch (Exception $e) {
                printf('<p>Kan inte visa <var>%s</var> pga. "%s".</p>', $file, $e->getMessage());
                continue;
            }

            $xml = simplexml_load_string($content);

            $reader = new ReportReader($reportCfg->getReportReader());
            $apts = $reader->getReportObjects($xml);

            if ($reportCfg->getRowProcessor() != null) {
                $rowprocessor = $reportCfg->getRowProcessor();
                $fn = function ($apt) use ($contacts, $rowprocessor) {
                    return $rowprocessor($apt, $contacts);
                };
                $apts = array_map($fn, $apts);
            }

            if (isset($apts)) {
                // Configuration specifies columns in array. Filtering function should use array values as keys.
                $joiner = $reportCfg->getColumns() != null ? create_column_filter_function(array_fill_keys($reportCfg->getColumns(), null)) : null;

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
        printf('<h3><a href="arkiv/%s" target="_blank">%s</a> <small>%s</small></h3>',
            $file,
            substr(substr($file, 0, -4), strlen($title) + 1),
            $i == 0 ? "Nul&auml;ge" : "Enbart skillnader");

        if ($i == 0) {
            $renderer->write($reportsData[$file]);

            if ($reportCfg->getSummaryGenerator() != null) {
                $rowprocessor = $reportCfg->getSummaryGenerator();
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

?>