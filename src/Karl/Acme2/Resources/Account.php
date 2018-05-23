<?php

namespace Karl\Acme2\Resources;

use Karl\Acme2;

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
     * @throws Acme2\Exception\RequestException
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
     * @param array $params
     *
     * @return mixed
     * @throws Acme2\Exception\RequestException
     */
    public function create($params = [])
    {
        $payload = [];
        if (isset($params['contact']))
            $payload['contact'] = $params['contact'];

        if (isset($params['termsOfServiceAgreed']))
            $payload['termsOfServiceAgreed'] = true;

        $response = $this->acme->send('newAccount', 'post', $payload);

        return json_decode($response->getBody());
    }

    /**
     * query account details, by sending an empty update
     *
     * @param $location
     *
     * @return mixed
     * @throws Acme2\Exception\RequestException
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
     * @throws Acme2\Exception\RequestException
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
     */
    public function getOrders($location)
    {
        $response = $this->acme->get($location . '/orders');

        return json_decode($response->getBody());
    }
}