<?php


namespace PayFast;

use PayFast\PaymentIntegrations\CustomIntegration;
use PayFast\PaymentIntegrations\Notification;
use PayFast\PaymentIntegrations\OnsiteIntegration;
use PayFast\Services\CreditCardTransactions;
use PayFast\Services\Refunds;
use PayFast\Services\Subscriptions;
use PayFast\Services\TransactionHistory;

class ServiceMapper
{

    private static $map = [
        'custom' => CustomIntegration::class,
        'onsite' => OnsiteIntegration::class,
        'notification' => Notification::class,
        'transactionHistory' => TransactionHistory::class,
        'subscriptions' => Subscriptions::class,
        'creditCardTransactions' => CreditCardTransactions::class,
        'refunds' => Refunds::class
    ];

    public static function getClass($name)
    {
        return self::$map[$name] ?? null;
    }

}
