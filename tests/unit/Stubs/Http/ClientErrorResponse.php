<?php

namespace Acme2\Tests\Unit\Stubs\Http;

use Karl\Acme2\Http\Response;

class ClientErrorResponse extends Response
{
    protected $statusCode = 400;
    protected $reasonPhrase = 'something wrong';

    public function __construct($headers = [], $bodyString = null)
    {
        $headers = [
            'Server'                    => ['nginx'],
            'X-Frame-Options'           => ['DENY',],
            'Strict-Transport-Security' => ['max-age=604800'],
            'Expires'                   => ['Fri, 01 Jun 2018 07:19:24 GMT'],
            'Cache-Control'             => ['max-age=0, no-cache, no-store'],
            'Pragma'                    => ['no-cache'],
            'Date'                      => ['Fri, 01 Jun 2018 07:19:24 GMT'],
            'Connection'                => ['close'],
        ];

        if ($bodyString === null)
            $bodyString = <<<EOT
{
    "type": "urn:ietf:params:acme:error:malformed",
    "detail": "Some of the identifiers requested were rejected",
    "subproblems": [
        {
            "type": "urn:ietf:params:acme:error:malformed",
            "detail": "Invalid underscore in DNS name \"_example.com\"",
            "identifier": {
                "type": "dns",
                "value": "_example.com"
            }
        },
        {
            "type": "urn:ietf:params:acme:error:rejectedIdentifier",
            "detail": "This CA will not issue for \"example.net\"",
            "identifier": {
                "type": "dns",
                "value": "example.net"
            }
        }
    ]
}
EOT;

        parent::__construct($headers, $bodyString);
    }
}
