<?php
require_once 'config.php';
require_once '../lib/google-api-php-client-1-master/src/Google/autoload.php';

const GOOGLE_OAUTHCALLBACK_URI = 'http://www.trollkarlen2.se/verktyg/forvaltningsrapporter/auth-google.php';
const GOOGLE_CLIENT_SECRET_FILE = '../client_secret_109116865971-kkggsgkcf1ak2ffg6vlr2lj80vv8opb1.apps.googleusercontent.com.json';

set_include_path(get_include_path() . PATH_SEPARATOR . '../lib/google-api-php-client-1-master/src');

session_start();

function createGoogleClient()
{
    $client = new Google_Client();
    $client->setAuthConfigFile(GOOGLE_CLIENT_SECRET_FILE);
    $client->setRedirectUri(GOOGLE_OAUTHCALLBACK_URI);
//    $client->addScope(Google_Service_Drive::DRIVE_METADATA);
    $client->addScope(Google_Service_Drive::DRIVE);
    $client->addScope(Google_Service_Plus::USERINFO_EMAIL);
    $client->addScope("http://www.google.com/m8/feeds/");
    return $client;
}
?>