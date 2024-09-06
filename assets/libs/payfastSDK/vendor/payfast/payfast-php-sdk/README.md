# PayFast PHP SDK

The PayFast PHP SDK provides an easy-to-use library for integrating PayFast payments into your project.
This includes Custom Integration, Onsite Integration and all APIs.

## Requirements

PHP 7.2.5 and later.

## Documentation

See the [Developer Docs](https://developers.payfast.co.za/docs)

## Composer

You can install the library via [Composer](http://getcomposer.org/). Run the following command:

```bash
composer require payfast/payfast-php-sdk
```

To use the library, use Composer's [autoload](https://getcomposer.org/doc/01-basic-usage.md#autoloading):

```php
require_once('vendor/autoload.php');
```

## Getting Started

### Custom Integration

Build a checkout form and receive payments securely from the PayFast payment platform.

See the [Developer Docs](https://developers.payfast.co.za/docs#quickstart)

```php
try {
    $payfast = new PayFastPayment(
        [
            'merchantId' => '10000100',
            'merchantKey' => '46f0cd694581a',
            'passPhrase' => '',
            'testMode' => true
        ]
    );

    $data = [
        'amount' => '100.00',
        'item_name' => 'Order#123'
    ];

    echo $payfast->custom->createFormFields($data, ['value' => 'PAY NOW', 'class' => 'btn']);
} catch(Exception $e) {
    echo 'There was an exception: '.$e->getMessage();
}
```

### Onsite Payments

Integrate PayFast’s secure payment engine directly into your checkout page.

See the [Developer Docs](https://developers.payfast.co.za/docs#onsite_payments)

```php
// Include: <script src="https://www.payfast.co.za/onsite/engine.js"></script>

try {
    $payfast = new PayFastPayment(
        [
            'merchantId' => '10000100',
            'merchantKey' => '46f0cd694581a',
            'passPhrase' => '',
            'testMode' => false
        ]
    );

    $data = [
        'amount' => '100.00',
        'item_name' => 'Order#123'
    ];

    // Generate payment identifier
    $identifier = $payfast->onsite->generatePaymentIdentifier($data);

    if($identifier!== null){
        echo '<script type="text/javascript">window.payfast_do_onsite_payment({"uuid":"'.$identifier.'"});</script>';
    }
} catch(Exception $e) {
    echo 'There was an exception: '.$e->getMessage();
}
```

### API

#### Recurring Billing

The Subscription Payments API gives Merchants the ability to interact with subscriptions on their accounts.

See the [Developer Docs](https://developers.payfast.co.za/api#recurring-billing)

```php
try {
    $api = new PayFastApi(
        [
            'merchantId' => '10018867',
            'passPhrase' => '2uU_k5q_vRS_',
            'testMode' => true
        ]
    );

    $fetchArray = $api->subscriptions->fetch('2afa4575-5628-051a-d0ed-4e071b56a7b0');

    $pauseArray = $api->subscriptions->pause('2afa4575-5628-051a-d0ed-4e071b56a7b0', ['cycles' => 1]);

    $unpauseArray = $api->subscriptions->unpause('2afa4575-5628-051a-d0ed-4e071b56a7b0');
    
    $cancelArray = $api->subscriptions->cancel('2afa4575-5628-051a-d0ed-4e071b56a7b0');

    $updateArray = $api->subscriptions->update('2afa4575-5628-051a-d0ed-4e071b56a7b0', ['cycles' => 1]);

    $adhocArray = $api->subscriptions->adhoc('290ac9a6-25f1-cce4-5801-67a644068818', ['amount' => 500, 'item_name' => 'Test adhoc']);

} catch(Exception $e) {
    echo 'There was an exception: '.$e->getMessage();
}
```

#### Update card

The update card endpoint allows you to provide buyers with a link to update their card details on a Recurring Billing subscription or Tokenization charges.

See the [Developer Docs](https://developers.payfast.co.za/docs#recurring_card_update)

```php
try {
    $payfast = new PayFastPayment(
            [
                'merchantId' => '10000100',
                'merchantKey' => '46f0cd694581a',
                'passPhrase' => '',
                'testMode' => false
            ]
        );

    echo $payfast->custom->createCardUpdateLink('2afa4575-5628-051a-d0ed-4e071b56a7b0', 'https://www.example.com/return', 'Update your card', ['target' => '_blank']);

} catch(Exception $e) {
    echo 'There was an exception: '.$e->getMessage();
}
```

#### Transaction History

The transaction history API gives Merchants the ability to interact with their PayFast account.

See the [Developer Docs](https://developers.payfast.co.za/api#transaction-history)

```php
try {
    $api = new PayFastApi(
        [
            'merchantId' => '10018867',
            'passPhrase' => '2uU_k5q_vRS_',
            'testMode' => true
        ]
    );

    $rangeArray = $api->transactionHistory->range(['from' => '2020-08-01', 'to' => '2020-08-07', 'offset' => 0, 'limit' => 1000]);
    
    $dailyArray = $api->transactionHistory->daily(['date' => '2020-08-07', 'offset' => 0, 'limit' => 1000]);
    
    $weeklyArray = $api->transactionHistory->weekly(['date' => '2020-08-07', 'offset' => 0, 'limit' => 1000]);
    
    $monthlyArray = $api->transactionHistory->monthly(['date' => '2020-08', 'offset' => 0, 'limit' => 1000]);

} catch(Exception $e) {
    echo 'There was an exception: '.$e->getMessage();
}
```

#### Credit card transaction query

The credit card transaction query API gives Merchants the ability to query credit card transactions.

See the [Developer Docs](https://developers.payfast.co.za/api#credit-card-transactions)

```php
try {
    $api = new PayFastApi(
        [
            'merchantId' => '10018867',
            'passPhrase' => '2uU_k5q_vRS_',
            'testMode' => true
        ]
    );

    $creditCardArray = $api->creditCardTransactions->fetch('1124148');

} catch(Exception $e) {
    echo 'There was an exception: '.$e->getMessage();
}
```

#### Refunds

The Refunds API Providing gives Merchants the ability to perform refunds on their account.

See the [Developer Docs](https://developers.payfast.co.za/api#refunds)

```php
try {
    $api = new PayFastApi(
        [
            'merchantId' => '10018867',
            'passPhrase' => '2uU_k5q_vRS_',
            'testMode' => false
        ]
    );

    $refundFetchArray = $api->refunds->fetch('dc0521d3-55fe-269b-fa00-b647310d760f');
    
    $refundCreateArray = $api->refunds->create('dc0521d3-55fe-269b-fa00-b647310d760f', ['amount' => 50, 'reason' => 'Product returned', 'acc_type' => 'savings']);

} catch(Exception $e) {
    echo 'There was an exception: '.$e->getMessage();
}
```

