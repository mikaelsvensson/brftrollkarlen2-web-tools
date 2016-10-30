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

require_once 'config.php';

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
        $reader = new ReportReader(array_map(function ($cfg) {
            return $cfg['reportreader'];
        }, $REPORTS));
        $apts = $reader->getReportObjects($xml);
        if (!isset($apts)) {
            die("Hittar ingen beskrivning f&ouml;r hur filen ska l&auml;sas.");
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