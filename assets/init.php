<?php
@ini_set('session.cookie_httponly',1);
@ini_set('session.use_only_cookies',1);
if (!version_compare(PHP_VERSION, '7.1.0', '>=')) {
    exit("Required PHP_VERSION >= 7.1.0 , Your PHP_VERSION is : " . PHP_VERSION . "\n");
}
if (!function_exists("mysqli_connect")) {
    exit("MySQLi is required to run the application, please contact your hosting to enable php mysqli.");
}
date_default_timezone_set('UTC');
session_start();
require('assets/includes/functions_general.php');
require('assets/includes/tables.php');
require('assets/includes/functions_one.php');