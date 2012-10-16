Kohana Liberty Reserve payment module
=============

This module created for easy use of LR API in Kohana Framework, version 3.2.

This code is just port of standard LR API code from their site to Kohana 3.2.

Supports
-------

Here are 2 parts of LR API:

* Payment API to make transactions, view history, lookup transactions with batch, get account name, etc
* Store API (SCI) to accept payments using Liberty Reserve

Usage
-------

Add this module to 'modules' and enable it in bootstrap:

    'lr'        => MODPATH.'kohana-liberty-reserve',

Copy example controller, modify it, add route similar to that:

    Route::set('payment', 'payment(/<action>)')
    ->defaults(array(
        'directory' => 'lr',
        'controller' => 'payment',
        'action'     => 'index',
    ));

Modify config:

    return array(
        'account'   => 'U1234567',
        'api'       => 'payment_api_name',
        'api-security-word' => 'payment_api_pass',
        'store' => 'lr_store',
        'store-security-word' => 'store_pass',
    );

Status URL on the merchant configuration page should be set to:

    https://your.domain.tld/payment/status

For getting current balance:

    $auth = LR::auth($acc, $payment_api, $payment_api_pass);
    $lr = LR::factory($auth);
    $balance = $lr->balance(LR::USD);

Or:

    $auth = LR::auth(); // will take default values from config
    $lr = LR::factory($auth);
    $balance = $lr->balance(LR::USD);

Or even:

    $lr = LR::factory(); // auth values are taken from config. if none defined, LR_Exception is thrown
    $balance = $lr->balance(LR::USD);

Testing
-------

Tested:

* JSON API

Tested a bit

* XML API
* NVP API

Not tested, surely has bugs:

* SOAP API

If you'll find some bugs and have fixes - please let me know, i'll add them here
