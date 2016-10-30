<?php
error_reporting(E_WARNING);
ini_set('display_errors', 1);
require_once 'config.php';
require_once 'google-util.php';
require_once 'index-showreport-latest.php';

$isAccessTokenSet = isset($_SESSION['access_token']) && $_SESSION['access_token'];
if (!$isAccessTokenSet) {
    header('Location: ' . filter_var(GOOGLE_OAUTHCALLBACK_URI, FILTER_SANITIZE_URL));
}
const AUTHORIZED_USER = 'brf.trollkarlen2'.'@'.'g'.'mail.com';
if ($_SESSION['email'] != AUTHORIZED_USER) {
    die("Only the ".AUTHORIZED_USER." may access this page.");
};

$reportId = $_GET['report'];

$client = createGoogleClient();

$client->setAccessToken($_SESSION['access_token']);

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
                    <li class="<?= $reportId == 'custom' ? 'active' : '' ?>"><a href="?report=custom">Egen...</a></li>
                </ul>
            </div><!-- /.navbar-collapse -->
        </div><!-- /.container-fluid -->
    </nav>
    <?php

    if ($reportId == 'custom') {
        readfile('index-custom-report-form.html');;
    } elseif (isset($REPORTS[$reportId])) {
        printReport($reportId, $contacts);
    }
    ?>
</div>
<?php include("index-generator-form.php") ?>
<?php include("index-json-form.php") ?>
</body>
</html>

