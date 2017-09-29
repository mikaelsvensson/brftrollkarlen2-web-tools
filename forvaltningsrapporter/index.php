<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);
$script_start_time = microtime(true);
require_once 'config.php';
require_once 'google-util.php';
require_once 'index-showreport-latest.php';

$cfg = parse_ini_file("config.ini", true);

$isAccessTokenSet = isset($_SESSION['access_token']) && $_SESSION['access_token'];
if (!$isAccessTokenSet) {
    header('Location: ' . filter_var($cfg['google']['oauthcallback_uri'], FILTER_SANITIZE_URL));
}
$authorized_user = $cfg['google']['authorized_gmail_user'].'@gmail.com';
if ($_SESSION['email'] != $authorized_user) {
    die("Only $authorized_user may access this page.");
};


$reportId = @$_GET['report'];

$client = createGoogleClient();

$client->setAccessToken($_SESSION['access_token']);

function printReportsMenu($selectedReportId)
{
    global $REPORTS;
    print join('', array_map(function ($title, $reportCfg) use ($selectedReportId) {
        $reportTitle = $reportCfg->getTitle();
        // Print link to report if report has a title:
        return !empty($reportTitle) ? sprintf('<li class="%s"><a href="?report=%s">%s</a></li>', $title == $selectedReportId ? 'active' : '', $title, $reportTitle) : '';
    }, array_keys($REPORTS), $REPORTS));
}

?>
<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>F&ouml;rvaltningsrapporter</title>

    <!-- TODO: Use Composer do include CSS files instead of relying on CDN. -->
    <!-- Latest compiled and minified CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.min.css">

    <!-- Optional theme -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap-theme.min.css">

    <?php include("index-generator-head.php") ?>
</head>
<body>
<div class="container-fluid">
    <?php
    try {
        $contacts = getGoogleContacts($client);
    } catch (Exception $e) {
        printf('<p>%s</p><p>Ofta hj&auml;per det att <a href="auth-signout.php">logga in igen</a>.</p>', $e->getMessage());
        echo '</div></body></html>';
        exit();
    }
    ?>
    <p style="position: absolute; top: 0; right: 0; padding: 0.3em;">
        <a href="auth-signout.php">Logga ut och logga in</a>
    </p>
    <div class="page-header">
        <h1>F&ouml;rvaltningsrapporter</h1>
    </div>

    <nav class="navbar navbar-default">
        <div class="container-fluid">
            <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
                <ul class="nav navbar-nav">
                    <?php
                    printReportsMenu($reportId);
                    ?>
                    <li class="<?= $reportId == 'custom' ? 'active' : '' ?>"><a href="?report=custom">Egen...</a></li>
                    <li class="<?= $reportId == 'sync' ? 'active' : '' ?>"><a href="?report=sync">Synka...</a></li>
                </ul>
            </div>
        </div>
    </nav>
    <?php

    if ($reportId == 'custom') {
        readfile('index-custom-report-form.html');;
    } elseif ($reportId == 'sync') {
        include 'index-sync-form.php';
    } elseif (isset($REPORTS[$reportId])) {
        printReport($reportId, $contacts);
    }
    ?>
</div>
<?php include("index-generator-form.php") ?>
<?php include("index-export-form.php") ?>
<?php include("index-json-form.php") ?>
</body>
</html>

