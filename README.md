
# acme2-library

[![Coverage Status](https://coveralls.io/repos/github/mbretter/acme2-library/badge.svg?branch=master)](https://coveralls.io/github/mbretter/acme2-library?branch=master)
[![Build Status](https://travis-ci.org/mbretter/acme2-library.svg?branch=master)](https://travis-ci.org/mbretter/acme2-library)
[![Latest Stable Version](https://img.shields.io/packagist/v/mbretter/acme2-library.svg)](https://packagist.org/packages/mbretter/acme2-library)
[![Total Downloads](http://img.shields.io/packagist/dt/mbretter/acme2-library.svg)](https://packagist.org/packages/mbretter/acme2-library)
[![License](http://img.shields.io/packagist/l/mbretter/acme2-library.svg)](https://packagist.org/packages/mbretter/acme2-library)

ACME2 low level php library

This library has been built to be integrated into applications, not as standalone acme client.

Benefits:

* no dependencies to other packages, like http clients, curl
* it comes up with a builtin http client (based on PHP streams), though any other PSR-7 compliant http client could be used
* the PSR-7 implementation is heavily based on slim, with some modifications
* it uses standardclasses and arrays, no fancy data objects or sophisticated data models
* it does not take care about data storage, it is up to you to store credentials/orders/states

## namespaces

```php
use Karl\Acme2;
use Karl\Acme2\Resources;
```

## acme

The Acme class is the manager for all requests, it carries the directory, the private key, fetches nonces and is the 
interface between the resource objects and the http client. 

```php
$acme = new Acme2\Acme(); // without any args staging urls are used

$acme = new Acme2\Acme(true); // for production use

$acme = new Acme2\Acme(true, $myHttpClient); // use my own http client, it must implement the Acme2\Http\ClientInterface
```

## account management

Before you can send any other requests you must subscribe for an account, this is done by generating your private key and submitting the 
create call.

```php
$acme = new Acme2\Acme();

$key = new Acme2\Key\RSA(); // we use an RSA key
$key->generate();
$pem = $key->getPem(); // get the PEM, store your key somewhere

$acme->setKey($key); // acme needs a key to operate

$account    = new Resources\Account($acme);
$accountData = $account->create(['termsOfServiceAgreed' => true, 'contact' => ['mailto:example@example.com']]);
$kid = $accountData->url; // acme uses the account url as keyId
```

You have to store the private key PEM and the kid somewhere in your system. 

### account lookup

If you have the PEM only, the key id can be retrieved using the lookup method:

```php
$acme = new Acme2\Acme();

$key = new Acme2\Key\RSA($pemKey);

$account = new Resources\Account($acme);
$info = $account->lookup();
if ($info !== null)
{
    $key->setKid($info->url); // account location is used as kid
}

```

### account deactivation


```php
$acme = new Acme2\Acme();
$key = new Acme2\Key\RSA($pemKey);
$acme->setKey($key);

$account = new Resources\Account($acme);
$account->deactivate($kid);

```

## orders

### create new order

```php
$acme = new Acme2\Acme();
$key = new Acme2\Key\RSA($pemKey);
$key->setKid($kid);
$acme->setKey($key);

$order = new Acme2\Resources\Order($acme);
$newOrder = $order->addIdentifier(null, 'acme01.example.com'); // create a new order object 
$order->addIdentifier($newOrder, 'acme02.example.com'); // add another identifier

$orderData = $order->create($newOrder);

$orderUrl = $orderData->url; // store the orderUrl somewhere

```

### get an existing order

```php
$order = new Acme2\Resources\Order($acme);

$orderData = $order->get($orderUrl);

print_r($orderData);
```

output:
```
stdClass Object
(
    [status] => valid
    [expires] => 2018-05-23T14:02:32Z
    [identifiers] => Array
        (
            [0] => stdClass Object
                (
                    [type] => dns
                    [value] => acme01.example.com
                )

        )

    [authorizations] => Array
        (
            [0] => https://acme-staging-v02.api.letsencrypt.org/acme/authz/AAAAA8
        )

    [finalize] => https://acme-staging-v02.api.letsencrypt.org/acme/finalize/999999/111111
    [certificate] => https://acme-staging-v02.api.letsencrypt.org/acme/cert/a83732947234cdef
    [url] => https://acme-staging-v02.api.letsencrypt.org/acme/order/999999/111111
)

```

