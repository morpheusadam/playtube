<?php
// Tell PayFast that this page is reachable by triggering a header 200
header( 'HTTP/1.0 200 OK' );
flush();

require('../../vendor/autoload.php');

use PayFast\PayFastPayment;

$amount = '5.00';

// $myFile = fopen('notify.txt', 'wb') or die('failed to open');

try {
    $payfast = new PayFastPayment(
        [
            'merchantId' => '10000100',
            'merchantKey' => '46f0cd694581a',
            'passPhrase' => '',
            'testMode' => true
        ]
    );

    $notification = $payfast->notification->isValidNotification($_POST, ['amount_gross' => $amount]);
    if($notification === true) {
        // All checks have passed, the payment is successful
        // fwrite($myFile, "All checks valid\n");
    } else {
        // Some checks have failed, check payment manually and log for investigation -> PayFastPayment::$errorMsg
        // fwrite($myFile, implode("\n",PayFastPayment::$errorMsg));
    }
} catch(Exception $e) {
    // Handle exception
    // fwrite($myFile, 'There was an exception: '.$e->getMessage());
}
