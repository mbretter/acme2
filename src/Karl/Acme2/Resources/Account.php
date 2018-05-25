<?php

namespace Karl\Acme2\Resources;

use Karl\Acme2;
use Karl\Acme2\Exception\RequestException;

class Account
{
    use Acme2\Dependency\Acme;

    public function __construct(Acme2\Acme $acme)
    {
        $this->acme = $acme;
    }

    /**
     * lookup whether account exists for the given key
     *
     * @return null|\stdClass
     * @throws RequestException
     */
    public function lookup()
    {
        $payload = ['onlyReturnExisting' => true];

        $response = $this->acme->send('newAccount', 'post', $payload);

        if ($response->getStatusCode() == 200)
        {
            $ret      = new \stdClass();
            $ret->url = $response->getHeaderLine('Location');

            return $ret;
        }

        return null;
    }

    /**
     * create an account
     *
     * params may contain the contact key, this is an array of strings (email addresses)
     * for more params @see https://ietf-wg-acme.github.io/acme/draft-ietf-acme-acme.html#rfc.section.7.3
     *
     * ```
     * $acme = new Acme2\Acme();
     * $key = new Acme2\Key\RSA();
     * $key->generate();
     * $pem = $key->getPem(); // store the pem key somehow somewhere
     * $acme->setKey($key);
     * $account = new Acme2\Resources\Account($acme);
     * $account->create(['termsOfServiceAgreed' => true, 'contact' => ['mailto:foo@example.com']]);
     * ```
     *
     * @param array $params
     *
     * @return mixed
     * @throws RequestException
     */
    public function create($params = [])
    {
        $response = $this->acme->send('newAccount', 'post', $params);

        return json_decode($response->getBody());
    }

    /**
     * query account details, by sending an empty update
     *
     * @param $location
     *
     * @return mixed
     * @throws RequestException
     */
    public function get($location)
    {
        $response = $this->acme->send($location, 'post');

        return json_decode($response->getBody());
    }

    /**
     * deactivate account
     *
     * @param $location
     *
     * @return mixed
     * @throws RequestException
     */
    public function deactivate($location)
    {
        $response = $this->acme->send($location, 'post', ['status' => 'deactivated']);

        return json_decode($response->getBody());
    }

    /**
     *
     *
     * @param string $url
     * @param array $params
     *
     * @return mixed
     * @throws RequestException
     */
    public function update($url, $params = [])
    {
        $payload = [];
        if (isset($params['contact']))
            $payload['contact'] = $params['contact'];

        if (isset($params['termsOfServiceAgreed']))
            $payload['termsOfServiceAgreed'] = true;

        $response = $this->acme->send($url, 'post', $payload);

        return json_decode($response->getBody());
    }


    /**
     *
     * @param $location
     *
     * @return mixed
     * @throws RequestException
     */
    public function getOrders($location)
    {
        $response = $this->acme->get($location . '/orders');

        return json_decode($response->getBody());
    }
}