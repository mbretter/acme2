<?php

namespace Acme2\Tests\Unit\Stubs\Http;

use Karl\Acme2\Http\Response;

class NonceResponse extends Response
{
    public function __construct($headers = [], $bodyString = null)
    {
        $headers = [
            'Server'                    => ['nginx'],
            'Replay-Nonce'              => ['5ZnnhQhAj5l5qm4Y9ZYvjE9Fh5mIEsc9hBEy_lhDKqM',],
            'X-Frame-Options'           => ['DENY',],
            'Strict-Transport-Security' => ['max-age=604800'],
            'Expires'                   => ['Fri, 01 Jun 2018 07:19:24 GMT'],
            'Cache-Control'             => ['max-age=0, no-cache, no-store'],
            'Pragma'                    => ['no-cache'],
            'Date'                      => ['Fri, 01 Jun 2018 07:19:24 GMT'],
            'Connection'                => ['close'],
        ];
        parent::__construct($headers, $bodyString);
    }

    public function getNonce()
    {
        return $this->getHeaderLine('Replay-Nonce');
    }
}
