<?php
include 'renderer/HtmlRenderer.php';
include 'renderer/TextRenderer.php';
include 'renderer/XmlRenderer.php';
include 'ReportReader.php';

require_once '../lib/PdfParser.php';

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    readfile('read.html');
    exit;
}

$filename = $_FILES['userfile']['tmp_name'];
$content = PdfParser::parseFile($filename);

if ($_POST['renderer']) {
    $className = $_POST['renderer'] . 'Renderer';
    $renderer = new $className;
    if ($renderer) {
        $xml = simplexml_load_string($content);
        $cfg = simplexml_load_file("cfg.xml");

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