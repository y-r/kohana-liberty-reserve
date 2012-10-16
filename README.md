Kohana Liberty Reserve payment module
=============

This module created for easy use of LR API in Kohana Framework, version 3.2.

Supports
-------

Here are 2 parts of LR API:

* Merchant API to make transactions, view history, lookup transactions with batch, get account name, etc
* Store API to accept payments using Liberty Reserve

Usage
-------

Add this module to 'modules' and enable it in bootstrap:

    'lr'        => MODPATH.'lr',

Copy example controller, modify it, add route similar to that:

    Route::set('payment', 'payment(/<action>)')
    ->defaults(array(
        'directory' => 'lr',
        'controller' => 'payment',
        'action'     => 'index',
    ));


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
