<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config.php';
require_once 'google-util.php';

$isAccessTokenSet = isset($_SESSION['access_token']) && $_SESSION['access_token'];
if (!$isAccessTokenSet) {
    header('Location: ' . filter_var(GOOGLE_OAUTHCALLBACK_URI, FILTER_SANITIZE_URL));
}

$client = createGoogleClient();

$client->setAccessToken($_SESSION['access_token']);
$drive_service = new Google_Service_Drive($client);

$defaultParams = ['DATUM' => date('Ymd-His')];

if (isset($_GET['template'])) {
    $template = $_GET['template'];
    $templateName = $drive_service->files->get($template)->name;
    $exported = $drive_service->files->export($template, "application/rtf", ['alt' => 'media']);

    $params = array_merge($_GET, $defaultParams);

    $content = $exported;//file_get_contents('files/report.csv');
    foreach ($params as $key => $value) {
        $content = str_replace($key, utf8_decode($value), $content);
    }

    $newName = strtr($templateName, $params);
    if ($newName == $templateName) {
        // Add date to make name unique (to not replace the template with the generated file)
        $newName .= " " . date('Ymd-His');
    }
    $fileMetadata = new Google_Service_Drive_DriveFile(array(
        'name' => $newName,
        'mimeType' => 'application/vnd.google-apps.document'));
    $file = $drive_service->files->create($fileMetadata, array(
        'data' => $content,
        'mimeType' => 'application/rtf',
        'uploadType' => 'multipart',
        'fields' => 'id'));

    header("Content-Type: application/pdf");
    $pdfData = $drive_service->files->export($file->getId(), "application/pdf", ['alt' => 'media']);
    print $pdfData;
    return;
}
?>
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
<div class="container">
    <h1>Skapa dokument</h1>

    <p style="position: absolute; top: 0; right: 0; padding: 0.3em;">
        <a href="auth-signout.php">Logga ut</a>
    </p>

    <form action="google-document-generator.php" method="get" class="form-horizontal">
        <?php
        $files_list = $drive_service->files->listFiles(array("q" => "mimeType = 'application/vnd.google-apps.document'"))->getFiles();
        ?>
        <h3>Vilken mall vill du anv&auml;nda?</h3>

        <div class="form-group">
            <label for="template" class="col-sm-2 control-label">Mall</label>

            <div class="col-sm-10">
                <select name="template" class="form-control">
                    <?php
                    foreach ($files_list as $file) {
                        printf('<option value="%s">%s</option>', $file->getId(), $file->getName());
                    }
                    ?>
                </select>
            </div>
        </div>

        <h3>Vad ska det st&aring; i dokumentet?</h3>
        <?php foreach ($_GET as $key => $value) { ?>
            <div class="form-group">
                <label for="field-<?= $key ?>" class="col-sm-2 control-label"><?= $key ?></label>

                <div class="col-sm-10">
                    <input type="text" class="form-control" id="field-<?= $key ?>" name="<?= $key ?>"
                           value="<?= $value ?>">
                </div>
            </div>
        <?php } ?>
        <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
                <button type="submit" class="btn btn-default">Skapa dokument</button>
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
</body>
</html>
