<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require_once 'renderer/BootstrapHtmlRenderer.php';
require_once 'ReportReader.php';
require_once 'config.php';
require_once '../lib/PdfParser.php';
require_once 'google-util.php';

$isAccessTokenSet = isset($_SESSION['access_token']) && $_SESSION['access_token'];
if (!$isAccessTokenSet) {
    header('Location: ' . filter_var(GOOGLE_OAUTHCALLBACK_URI, FILTER_SANITIZE_URL));
}

$client = createGoogleClient();

$client->setAccessToken($_SESSION['access_token']);

$renderer = new BootstrapHtmlRenderer();
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

//$renderer->writerDocStart();
$time = time();
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

    <link rel="stylesheet" href="https://code.jquery.com/ui/1.11.4/themes/flick/jquery-ui.css">
    <link rel="stylesheet" href="overlay.css?<?= $time ?>">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/2.2.3/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js"></script>

    <script type="text/javascript" src="template-links.js?<?= $time ?>"></script>
</head>
<body>
<div class="container-fluid">
    <p style="position: absolute; top: 0; right: 0; padding: 0.3em;">
        <a href="auth-signout.php">Logga ut</a>
    </p>
    <?php

    $feedURL = "https://www.google.com/m8/feeds/contacts/default/thin?max-results=1000&alt=json";
    //        $feedURL = "https://www.google.com/m8/feeds/contacts/default/full";
    $req = new Google_Http_Request($feedURL);
    $val = $client->getAuth()->authenticatedRequest($req);

    //        var_dump($val);
    // The contacts api only returns XML responses.
    $responseRaw = $val->getResponseBody();

    $response = json_decode($responseRaw, true)['feed']['entry'];

    $contacts = array_map(function ($contact) {
        return [
            'name' => $contact['title']['$t'],
            'updated' => $contact['updated']['$t'],
            'note' => $contact['content']['$t'],
            'email' => $contact['gd$email'][0]['address'],
            'phone' => $contact['gd$phoneNumber'][0]['$t'],
            'orgName' => $contact['gd$organization'][0]['gd$orgName']['$t'],
            'orgTitle' => $contact['gd$organization'][0]['gd$orgTitle']['$t'],
            'address' => explode("\n", $contact['gd$postalAddress'][0]['$t'])
        ];
    }, $response);

    //        print "<pre>" . print_r($contacts, true) . "</pre>";
    //        print "<pre>" . print_r($response, true) . "</pre>";
    //        $response = json_encode(simplexml_load_string($responseBody));
    //        print "<pre>" . print_r(json_decode($response, true), true) . "</pre>";


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
            printf('<h2>%s <small>%s</small></h2>',
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
    //$renderer->writerDocEnd();
    ?>
</div>
<div id="generator" class="hidden overlay">
    <div class="container-fluid">
        <form action="google-document-generator.php" method="get" target="_blank" class="form-horizontal">
            <?php
            $drive_service = new Google_Service_Drive($client);
            $files_list = $drive_service->files->listFiles(array(
                "q" => "mimeType = 'application/vnd.google-apps.document' and trashed = false",
                "fields" => "files(appProperties,id,name),kind,nextPageToken"
            ))->getFiles();
            ?>
            <h3>Vilken mall vill du anv&auml;nda?</h3>

            <div class="form-group">
                <label for="template" class="col-sm-4 control-label">Mall</label>

                <div class="col-sm-8">
                    <select name="template" class="form-control">
                        <?php
                        foreach ($files_list as $file) {
                            if (!$file->getAppProperties()['sourceTemplate']) {
                                printf('<option value="%s">%s</option>', $file->getId(), $file->getName());
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>

            <h3>Vad ska det st&aring; i dokumentet?</h3>
            <div id="parameters"></div>
            <div class="form-group">
                <div class="col-sm-offset-2 col-sm-10">
                    <button type="submit" class="btn btn-primary">Skapa dokument</button>
                    <button type="button" class="btn btn-default" id="button-close">St&auml;ng</button>
                </div>
            </div>
            <div>
                <p>Du kan ocks&aring; anv&auml;nda dessa parametrar i ditt dokument:</p>
                <ul>
                    <?php
                    foreach ($defaultParams as $key => $value) {
                        printf('<li><code>%s</code>: %s</li>', $key, $value);
                    }
                    ?>
                </ul>
            </div>
        </form>
    </div>
</div>
</body>
</html>

