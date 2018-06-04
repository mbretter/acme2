<?php

namespace Acme2\Tests\Unit\Stubs\Http;

class AccountResponse extends GenericResponse
{
    public function __construct($headers = [], $bodyString = null)
    {
        $bodyString = <<<EOT
{
  "status": "valid",

  "contact": [
    "mailto:cert-admin@example.com",
    "mailto:admin@example.com"
  ],

  "orders": "https://example.com/acme/acct/1/orders"
}
EOT;

        parent::__construct($headers, $bodyString);
    }
}
