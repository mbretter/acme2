
# acme2-library

[![Coverage Status](https://coveralls.io/repos/github/mbretter/acme2-library/badge.svg?branch=master)](https://coveralls.io/github/mbretter/acme2-library?branch=master)
[![Build Status](https://travis-ci.org/mbretter/acme2-library.svg?branch=master)](https://travis-ci.org/mbretter/acme2-library)
[![Latest Stable Version](https://img.shields.io/packagist/v/mbretter/acme2-library.svg)](https://packagist.org/packages/mbretter/acme2-library)
[![Total Downloads](http://img.shields.io/packagist/dt/mbretter/acme2-library.svg)](https://packagist.org/packages/mbretter/acme2-library)
[![License](http://img.shields.io/packagist/l/mbretter/acme2-library.svg)](https://packagist.org/packages/mbretter/acme2-library)

ACME2 low level php library

This library has been built to be integrated into applications, not as a standalone acme client.

The ACME2 specs: [https://ietf-wg-acme.github.io/acme/draft-ietf-acme-acme.html](https://ietf-wg-acme.github.io/acme/draft-ietf-acme-acme.html)

Benefits:

* no dependencies to other packages, like http clients
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
$acme = new Acme2\Acme(); // without any args letsencrypt staging urls are used

$acme = new Acme2\Acme(true); // for letsencrypt production use

$acme = new Acme2\Acme('https://someca.example.com/acme'); // for any other acme compatible CA

$acme = new Acme2\Acme(true, $myHttpClient); // use your own http client
```

## resources

You can create the objects yourself, this is useful, if you have your own DI/Container system:

```php
$acme = new Acme2\Acme();

$key = new Acme2\Key\RSA($pemKey);
$acme->setKey($key);

$account = new Resources\Account($acme)
$accountData = $account->lookup();
```

The other way ist to use the acme object to retrieve the resource objects, which is more fluent:

```php
$acme = new Acme2\Acme();

$key = new Acme2\Key\RSA($pemKey);
$acme->setKey($key);

$accountData = $acme->account()->lookup();
...
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

$accountData = $acme->account()->create([
    'termsOfServiceAgreed' => true, 
    'contact' => ['mailto:example@example.com']
]);
$kid = $accountData->url; // acme uses the account url as keyId
```

You have to store the private key PEM and the kid somewhere in your system. 

### account lookup

If you have the PEM only, the key id can be retrieved using the lookup method:

```php
$acme = new Acme2\Acme();

$key = new Acme2\Key\RSA($pemKey);

$info = $acme->account()->lookup();
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

$newOrder = $acme->order()->addIdentifier(null, 'acme01.example.com'); // create a new order object 
$acme->order()->addIdentifier($newOrder, 'acme02.example.com'); // add another identifier

$orderData = $acme->order()->create($newOrder);

$orderUrl = $orderData->url; // store the orderUrl somewhere
```

create an order for a wildcard domain:

```php
...
$newOrder = $acme->order()->addIdentifier(null, '*.example.com');

$orderData = $acme->order()->create($newOrder);

$orderUrl = $orderData->url; // store the orderUrl somewhere
```

Note: When using wildcard domains, Lets encrypt supports DNS validation only.


### get an existing order

```php
$order = new Acme2\Resources\Order($acme);

$orderData = $order->get($orderUrl);

print_r($orderData);
```

example output:
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

## authorization

Basically there are two possibilities to validate your orders, the first one is to put the key authorization into a wellknown path and the other one is to provision a DNS TXT record with the authentication key.

Once you have done one of these steps, you have to tell the CA to verify the order, the verification is done by either querying the DNS record or by fetching the key authorization from the well known path.

The authentication must be done for each identifier added to the order, each authentication usually offers the DNS and the HTTP method, they are called challenges, for wildcard domains the DNS challenge is supported only.


```php
$orderData = $acme->order()->get($orderUrl);

foreach ($orderData->authorizations as $a)
{
    $authData = $acme->authorization()->get($a);

    printf("authorization for: %s\n", $authData->identifier->value);

    $challengeData = $acme->authorization()->getChallenge($authData, 'dns-01');
    if ($challengeData === null)
        continue;

    // you have to add the $authKey to the DNS TXT record
    $authKey = $acme->challenge()->buildKeyAuthorization($challengeData);
    printf("DNS auth key is: %s\n", $authKey);

    // tell the CA to validate the challenge
    $acme->challenge()->validate($challengeData->url);

    $challengeData = $acme->authorization()->getChallenge($authData, 'http-01');
    if ($challengeData === null)
        continue;

    // you have to put the $authKey to the well known path
    $authKey = $acme->challenge()->buildKeyAuthorization($challengeData);
    printf("HTTP auth key is: %s\n", $authKey);

    // tell the CA to validate the challenge
    $acme->challenge()->validate($challengeData->url);
}
```

practically, only one challenge type needs to succeed for successfully validating the identifier.

### DNS challenge

The DNS TXT record, where you have to put the auth key, is called _acme-challenge, e.g.

_acme-challenge.example.org 300 IN TXT "w2toDKxcQx2N8zcu4HnDboT1FceHs7lupLMTXsPbXCQ".

You can put multiple TXT records with the same name there, this is needed if you are using wildcard domains and an alternative subject name with the domainname.

### HTTP challenge

When using HTTP challenges, you have to put the auth key under the path:

/.well-known/acme-challenge/&lt;token&gt;

/.well-known/acme-challenge/LoqXcYV8q5ONbJQxbmR7SCTNo3tiAXDfowyjxAjEuX0

The token can be found inside the challenge data.

The Content-Type of the response must be application/octet-stream.

Important: the well known path must be available using HTTP not HTTPS, even if you have a valid certificate, otherwise you will have problems when renewing your certificate.

## finalize

ToDo

## download the certificate

ToDo

## renew

ToDo

## ToDos

* EC keys

