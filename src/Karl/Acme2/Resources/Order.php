<?php

namespace Karl\Acme2\Resources;

use Karl\Acme2;
use Karl\Acme2\Exception;

class Order
{
    use Acme2\Dependency\Acme;

    public function __construct(Acme2\Acme $acme)
    {
        $this->acme = $acme;
    }

    /**
     * @param object $orderData
     *
     * @return mixed|null
     * @throws Acme2\Exception\RequestException
     */
    public function create($orderData)
    {
        $response = $this->acme->send('newOrder', 'post', $orderData);

        if ($response->getStatusCode() == 201)
        {
            $ret      = json_decode((string)$response->getBody());
            $ret->url = $response->getHeaderLine('Location');

            return $ret;
        }

        return null;
    }

    /**
     * read order data
     * returns null, if no order was found
     *
     * @param $url
     *
     * @return mixed
     * @throws Exception\RequestException
     */
    public function get($url)
    {
        try
        {
            $response = $this->acme->get($url);
        } catch (Exception\RequestException $re) {
            if ($re->getCode() == 404)
                return null;

            throw $re;
        }

        $order      = json_decode($response->getBody());
        $order->url = $url;

        return $order;
    }

    /**
     * finalize request, send the CSR, the CA returns the order object
     *
     * @param string $url finalize url
     * @param string $csr PEM or DER formatted CSR
     *
     * @return mixed
     * @throws Acme2\Exception\RequestException
     */
    public function finalize($url, $csr)
    {
        // check if csr is PEM formatted, base64decode to binary and recode base64urlsafe
        if (preg_match('/^-+BEGIN CERTIFICATE REQUEST-+(.+)-+END CERTIFICATE REQUEST-+$/s', $csr, $matches))
            $csr = base64_decode($matches[1]);

        $csr = Acme2\Helper::base64urlEncode($csr);

        $response = $this->acme->send($url, 'post', ['csr' => $csr]);

        return json_decode($response->getBody());
    }

    /**
     * @param object $orderData
     * @param mixed $value
     * @param string $type
     *
     * @return object
     */
    public function addIdentifier($orderData, $value, $type = 'dns')
    {
        if (!is_object($orderData))
            $orderData = new \stdClass();

        if (!property_exists($orderData, 'identifiers') || !is_array($orderData->identifiers))
            $orderData->identifiers = [];

        array_push($orderData->identifiers, [
            'type'  => $type,
            'value' => $value
        ]);

        return $orderData;
    }
}