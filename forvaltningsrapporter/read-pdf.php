<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

// Include Composer autoloader if not already done.
require_once '../vendor/autoload.php';

require_once 'renderer/HtmlRenderer.php';
require_once 'renderer/TextRenderer.php';
require_once 'renderer/XmlRenderer.php';
require_once 'ReportReader.php';
require_once 'PdfParserWrapper.php';

//if ($_SERVER['REQUEST_METHOD'] == 'GET') {
//    readfile('index-custom-report-form.html');
//    exit;
//}

$filename = $_FILES['userfile']['tmp_name'];
if (!file_exists($filename)) {
    die('Ingen fil');
}
$wrapper = new PdfParserWrapper();
$content = $wrapper->pdfToXml($filename);

if ($_POST['renderer']) {
    $className = $_POST['renderer'] . 'Renderer';
    $renderer = new $className;
    if ($renderer) {
        $xml = simplexml_load_string($content);
        $cfg = simplexml_load_file("ReportReaderConfig.xml");

        $reader = new ReportReader();
        $apts = $reader->getReportObjects($cfg, $xml);
        if (!isset($apts)) {
            die("Hittar ingen beskrivning f?r hur filen ska l?sas.");
        }

        $renderer->writerDocStart();
        $renderer->write($apts);
        $renderer->writerDocEnd();
    } else {
        print "No renderer";
    }
} else {
    header('Content-Type: text/xml');
    print $content;
}