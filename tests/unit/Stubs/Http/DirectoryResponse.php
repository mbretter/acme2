<?php

namespace Acme2\Tests\Unit\Stubs\Http;

use Karl\Acme2\Http\Response;

class DirectoryResponse extends Response
{
    public function __construct($headers = [], $bodyString = null)
    {
        $bodyString = <<<EOT
{
  "8eKhv10vEf4": "https://community.letsencrypt.org/t/adding-random-entries-to-the-directory/33417",
  "keyChange": "https://acme-staging-v02.api.letsencrypt.org/acme/key-change",
  "meta": {
    "caaIdentities": [
      "letsencrypt.org"
    ],
    "termsOfService": "https://letsencrypt.org/documents/LE-SA-v1.2-November-15-2017.pdf",
    "website": "https://letsencrypt.org/docs/staging-environment/"
  },
  "newAccount": "https://acme-staging-v02.api.letsencrypt.org/acme/new-acct",
  "newNonce": "https://acme-staging-v02.api.letsencrypt.org/acme/new-nonce",
  "newOrder": "https://acme-staging-v02.api.letsencrypt.org/acme/new-order",
  "revokeCert": "https://acme-staging-v02.api.letsencrypt.org/acme/revoke-cert"
}
EOT;

        parent::__construct($headers, $bodyString);
    }
}
