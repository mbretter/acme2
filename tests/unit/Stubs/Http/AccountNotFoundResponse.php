<?php

namespace Acme2\Tests\Unit\Stubs\Http;

class AccountNotFoundResponse extends ClientErrorResponse
{
    public function __construct($headers = [], $bodyString = null)
    {
        $bodyString = <<<EOT
{
    "type": "urn:ietf:params:acme:error:accountDoesNotExist",
    "detail": "Account does not exist",
    "subproblems": [
    ]
}
EOT;

        parent::__construct($headers, $bodyString);
    }
}
