<?php


namespace Karl\Acme2\Key;

use Karl\Acme2\Helper;

class RSA implements KeyInterface
{
    /**
     * exported key as PEM string
     *
     * @var null|string
     */
    protected $pem;

    /**
     * the key id, this is the Location header as returned by newAccount
     *
     * @var string
     */
    protected $kid;

    /**
     * bit length
     *
     * @var int
     */
    protected $bits;

    public function __construct($pem = null, $bits = 2048)
    {
        $this->pem = $pem;
        $this->bits = $bits;
    }

    /**
     * generate a new key and return the key resource, set the new key as exported PEM
     * @param array $params
     *
     * @return resource
     */
    public function generate($params = [])
    {
        $configargs = [
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
            'private_key_bits' => isset($params['bits']) ? $params['bits'] : $this->bits
        ];

        $key = openssl_pkey_new($configargs);

        openssl_pkey_export($key, $this->pem);

        return $key;
    }

    public function getPem()
    {
        return $this->pem;
    }

    public function setPem($pem)
    {
        $this->pem = $pem;

        return $this;
    }

    public function setKid($keyId)
    {
        $this->kid = $keyId;

        return $this;
    }

    public function getKid()
    {
        return $this->kid;
    }

    /**
     * build JSON Web Key
     *
     * @return array
     */
    protected function getJWK()
    {
        $res     = openssl_pkey_get_private($this->pem);
        $details = openssl_pkey_get_details($res);

        return [
            'kty' => 'RSA',
            'n'   => Helper::base64urlEncode($details['rsa']['n']),
            'e'   => Helper::base64urlEncode($details['rsa']['e'])
        ];
    }

    /**
     * https://tools.ietf.org/html/rfc7638
     *
     * @return array
     */
    public function buildJWKThumbprint()
    {
        $jwk = $this->getJWK();

        // re-order, important
        $thumb = [
            'e'   => $jwk['e'],
            'kty' => $jwk['kty'],
            'n'   => $jwk['n']
        ];

        return hash('sha256', json_encode($thumb), true);
    }

    /**
     * @param array $jwsProtected
     * @param mixed $payload the raw payload
     *
     * @return array
     */
    public function sign($jwsProtected, $payload)
    {
        $jwsProtected['alg'] = 'RS256';

        // either KeyId or json web key must be present, not both
        if ($this->getKid() === null)
            $jwsProtected['jwk'] = $this->getJWK();
        else
            $jwsProtected['kid'] = $this->getKid();

        $payloadb64   = Helper::base64urlEncode(str_replace('\\/', '/', json_encode($payload)));
        $protectedb64 = Helper::base64urlEncode(json_encode($jwsProtected));

        openssl_sign($protectedb64 . '.' . $payloadb64, $signature, $this->pem, OPENSSL_ALGO_SHA256);

        return [
            'protected' => $protectedb64,
            'payload'   => $payloadb64,
            'signature' => Helper::base64urlEncode($signature)
        ];
    }
}