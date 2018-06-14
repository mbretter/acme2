<?php

namespace Karl\Acme2\Resources;

use Karl\Acme2;
use Karl\Acme2\Helper;

use InvalidArgumentException;

class Challenge
{
    use Acme2\Dependency\Acme;

    public function __construct(Acme2\Acme $acme)
    {
        $this->acme = $acme;
    }

    /**
     * retrieve the challenge information from the CA
     *
     * @param $url
     *
     * @return mixed
     * @throws Acme2\Exception\RequestException
     */
    public function get($url)
    {
        $response = $this->acme->get($url);

        return json_decode($response->getBody());
    }

    /**
     * tell the CA, that the challenges can be validated by sending an empty post to the authz location
     *
     * @param $url
     *
     * @return mixed
     * @throws Acme2\Exception\RequestException
     */
    public function validate($url)
    {
        $response = $this->acme->send($url, 'post');

        return json_decode($response->getBody());
    }

    /**
     * build the key authorization which must be deployed to dns or a well known path followed by the token
     * /.well-known/acme-challenge/LoqXcYV8q5ONbJQxbmR7SCTNo3tiAXDfowyjxAjEuX0
     *
     * for dns challenges the base64urlencoded sha256 hash must be build around the key auth
     * for deploying into the DNS TXT record:
     * _acme-challenge.example.org 300 IN TXT "w2toDKxcQx2N8zcu4HnDboT1FceHs7lupLMTXsPbXCQ"
     *
     * keyAuthorization = token || '.' || base64url(JWK_Thumbprint(accountKey))
     *
     * @param object $challenge the challenge object
     *
     * @return string
     */
    public function buildKeyAuthorization($challenge)
    {
        if (!is_object($challenge) || !isset($challenge->token) || !isset($challenge->type))
            throw new InvalidArgumentException('invalid challenge object.');

        $thumbprint = $this->acme->getJWKThumbprint();

        $keyAuth = sprintf('%s.%s', $challenge->token, $thumbprint);

        if ($challenge->type == 'dns-01')
            return Helper::base64urlEncode(hash('sha256', $keyAuth, true));

        return $keyAuth;
    }

}