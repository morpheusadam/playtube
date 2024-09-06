# SecurionPay PHP Library

If you don't already have SecurionPay account you can create it [here](https://securionpay.com/signup). 

## Installation 

### Composer

Best way to use this library is via [Composer](http://getcomposer.org/).

To do this you will need to add this configuration to your `composer.json`:

```json
{
  "require": {
    "securionpay/securionpay-php": "^2.4.0"
  }
}
```

Then to use the library, you can use Composer's autoloader:

```php
require_once('vendor/autoload.php');
```

### Manual installation

If you don't want to use Composer then you can download [the latest release](https://github.com/securionpay/securionpay-php/releases).

Then to use the library, you can either configure your autoloader to load classes from the `lib/` directory or use included autoloader:

```php
 require_once 'lib/SecurionPay/Util/SecurionPayAutoloader.php';
 \SecurionPay\Util\SecurionPayAutoloader::register();
```

## Quick start example

```php
use SecurionPay\SecurionPayGateway;
use SecurionPay\Exception\SecurionPayException;

$gateway = new SecurionPayGateway('sk_test_[YOUR_SECRET_KEY]');

$request = array(
    'amount' => 499,
    'currency' => 'EUR',
    'card' => array(
        'number' => '4242424242424242',
        'expMonth' => 11,
        'expYear' => 2022
    )
);

try {
    $charge = $gateway->createCharge($request);

    // do something with charge object - see https://securionpay.com/docs/api#charge-object
    $chargeId = $charge->getId();

} catch (SecurionPayException $e) {
    // handle error response - see https://securionpay.com/docs/api#error-object
    $errorType = $e->getType();
    $errorCode = $e->getCode();
    $errorMessage = $e->getMessage();
}
```

## Documentation

For further information, please refer to our official documentation at https://securionpay.com/docs.
