<?php
require('../../vendor/autoload.php');

use PayFast\PayFastPayment;

$amount = '5.00';

$payfast = new PayFastPayment(
    [
        'merchantId' => '10000100',
        'merchantKey' => '46f0cd694581a',
        'passPhrase' => '',
        'testMode' => true
    ]
);

$data = [
    // Merchant details
    'return_url' => 'https://www.example.com/return.php',
    'cancel_url' => 'https://www.example.com/cancel.php',
    'notify_url' => 'https://www.example.com/notify.php',
    // Buyer details
    'name_first' => 'First Name',
    'name_last'  => 'Last Name',
    'email_address'=> 'test@test.com',
    // Transaction details
    'm_payment_id' => '1234', //Unique payment ID to pass through to notify_url
    'amount' => $amount,
    'item_name' => 'Order#123'
];

$htmlForm = $payfast->custom->createFormFields($data, ['value' => 'PLEASE PAY', 'class' => 'button-cta']);
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="css/main.css">
</head>
<body>

<div class='subject'>Checkout&nbsp;&nbsp;Check<br>Checkout&nbsp;&nbsp;Check</div>

<div class='checkout'>
    <div class='order'>
        <h2>Custom Checkout Demo</h2>
        <h5>Order #000001</h5>
        <ul class='order-list'>
            <li><img src='https://via.placeholder.com/150'><h4>Product 1</h4><h5>R2</h5></li>
            <li><img src='https://via.placeholder.com/150'><h4>Product 2</h4><h5>R1</h5></li>
            <li><img src='https://via.placeholder.com/150'><h4>Product 3</h4><h5>R1</h5></li>
        </ul>
        <h5>Shipping</h5><h4>R 1.00</h4>
        <h5 class='total'>Total</h5><h1>R <?= $amount ;?></h1>

        <?= $htmlForm ;?>

    </div>
</div>

</body>
</html>

