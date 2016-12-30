<?php
require_once 'config.php';
if (!isset($_GET['file'])) {
    die('No file specified');
}
if (preg_match('/[^a-z0-9.-]/', $_GET['file'])) {
    die('File name seems invalid');
}
$path = FILES_FOLDER . $_GET['file'];
if (!is_file($path)) {
    die('File not found');
}
header("Content-Type: ".mime_content_type($path));
readfile($path);