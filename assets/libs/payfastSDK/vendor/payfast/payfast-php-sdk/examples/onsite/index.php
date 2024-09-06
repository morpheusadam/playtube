<?php
require('../../vendor/autoload.php');

use PayFast\PayFastPayment;

$amount = '5.00';

$data = [
    // Merchant details
    'return_url' => 'https://yoursite.com/return.php', // Requires HTTPS
    'cancel_url' => 'https://yoursite.com/cancel.php', // Requires HTTPS
    'notify_url' => 'https://yoursite.com/notify.php', // Requires HTTPS
    // Buyer details
    'name_first' => 'First Name',
    'name_last'  => 'Last Name',
    'email_address'=> 'test@test.com',
    // Transaction details
    'm_payment_id' => '1234', //Unique payment ID to pass through to notify_url
    'amount' => $amount,
    'item_name' => 'Order#123'
];
?>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="css/main.css">
    <script src="https://www.payfast.co.za/onsite/engine.js"></script>
</head>
<body>

<div class='subject'>Checkout&nbsp;&nbsp;Check<br>Checkout&nbsp;&nbsp;Check</div>

<div class='checkout'>
    <div class='order'>
        <h2>Onsite Checkout Demo</h2>
        <h5>Order #000001</h5>
        <ul class='order-list'>
            <li><img src='https://via.placeholder.com/150'><h4>Product 1</h4><h5>R2</h5></li>
            <li><img src='https://via.placeholder.com/150'><h4>Product 2</h4><h5>R1</h5></li>
            <li><img src='https://via.placeholder.com/150'><h4>Product 3</h4><h5>R1</h5></li>
        </ul>
        <h5>Shipping</h5><h4>R 1.00</h4>
        <h5 class='total'>Total</h5><h1>R <?= $amount ;?></h1>

        <form method="post" action="index.php">
            <input type="submit" class="button-cta" name="paynow" value="PAY NOW">
        </form>

    </div>
</div>

<?php
if(isset($_POST['paynow'])) {
    try {
        $payfast = new PayFastPayment(
            [
                'merchantId' => '10000100', // Your merchant id
                'merchantKey' => '46f0cd694581a', // Your merchant key
                'passPhrase' => '',
                'testMode' => false
            ]
        );

        // Generate payment identifier
        $identifier = $payfast->onsite->generatePaymentIdentifier($data);

        if($identifier!== null){
            echo '<script type="text/javascript">window.payfast_do_onsite_payment({"uuid":"'.$identifier.'"});</script>';
        }
    } catch(Exception $e) {
        echo 'There was an exception: '.$e->getMessage();
    }
}
?>

</body>
</html>

