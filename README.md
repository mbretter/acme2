
# acme2-library

[![Coverage Status](https://coveralls.io/repos/github/mbretter/acme2-library/badge.svg?branch=master)](https://coveralls.io/github/mbretter/acme2-library?branch=master)
[![Build Status](https://travis-ci.org/mbretter/acme2-library.svg?branch=master)](https://travis-ci.org/mbretter/acme2-library)
[![Latest Stable Version](https://img.shields.io/packagist/v/mbretter/acme2-library.svg)](https://packagist.org/packages/mbretter/acme2-library)
[![Total Downloads](http://img.shields.io/packagist/dt/mbretter/acme2-library.svg)](https://packagist.org/packages/mbretter/acme2-library)
[![License](http://img.shields.io/packagist/l/mbretter/acme2-library.svg)](https://packagist.org/packages/mbretter/acme2-library)

ACME2 low level php library

This library has been built to be integrated into applications, not as standalone acme client.

Benefits:

* no dependencies like curl or other packages
* it comes up with a builtin httpclient, but any other PSR7 compliant http client may be used
* it uses standard classes, but you can use your own data objects

## setup new account

```php
use Karl\Acme2;
use Karl\Acme2\Resources;

$acme = new Acme2\Acme();

$key = new Acme2\Key\RSA();
$key->generate();
$pem = $key->getPem(); // store your key somewhere

$acme->setKey($key); // acme needs a key to operate

$account    = new Resources\Account($acme);
$account->create(['termsOfServiceAgreed' => true, 'contact' => ['mailto:example@example.com']]);
```
