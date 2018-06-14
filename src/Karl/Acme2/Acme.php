<?php

namespace Karl\Acme2;

use Karl\Acme2\Exception\RequestException;
use Karl\Acme2\Http\ClientInterface;
use Karl\Acme2\Http\Dependency\HttpClient;
use Karl\Acme2\Key\KeyInterface;
use Karl\Acme2\Resources\Account;
use Karl\Acme2\Resources\Authorization;
use Karl\Acme2\Resources\Certificate;
use Karl\Acme2\Resources\Challenge;
use Karl\Acme2\Resources\Order;
use Psr\Http\Message\ResponseInterface;

use Karl\Acme2\Http\Body;
use Karl\Acme2\Http\Uri;
use Karl\Acme2\Http\Request;

use RuntimeException;

/**
 *
 * Class Acme
 *
 * @see https://ietf-wg-acme.github.io/acme/draft-ietf-acme-acme.html
 *
 * @package Karl\Acme2
 */
class Acme
{
    use HttpClient;

    /**
     * lets encrypt endpoints, for other CAs you have to set the URL in the constructor
     * @var array
     */
    protected $endpoints = [
        'prod'    => 'https://acme-v02.api.letsencrypt.org/',
        'staging' => 'https://acme-staging-v02.api.letsencrypt.org/'
    ];

    /**
     * url endpoint
     * @var string
     */
    protected $endpoint = null;

    /**
     * @var KeyInterface
     */
    protected $key = null;

    /**
     * @var \stdClass
     */
    protected $directory;

    /**
     * the last fetched nonce
     * @var string
     */
    protected $nonce;

    /**
     * Acme constructor.
     * @param bool|string $stagingUrl if it is a string it is treated as url
     * @param ClientInterface|null $httpClient
     */
    public function __construct($stagingUrl = true, ClientInterface $httpClient = null)
    {
        if (is_string($stagingUrl))
            $this->endpoint = $stagingUrl;
        else
            $this->endpoint = $stagingUrl ? $this->endpoints['staging'] : $this->endpoints['prod'];
        $this->setHttpClient($httpClient);
    }

    /**
     * send a head request and retrieve a new nonce
     * @return string
     */
    public function newNonce()
    {
        $directory = $this->getDirectory();
        $request = $this->emptyRequest('HEAD', $directory->newNonce);

        $response = $this->httpClient->send($request);

        return $response->getHeaderLine('Replay-Nonce');
    }

    /**
     * return directory and/or fetch it from the CA if not present
     * @return \stdClass
     */
    public function getDirectory()
    {
        if ($this->directory === null)
            $this->directory = $this->fetchDirectory();

        return $this->directory;
    }

    /**
     * fetch the directory containing endpoints for various requests
     * @return \stdClass
     */
    public function fetchDirectory()
    {
        $request = $this->emptyRequest('GET', $this->endpoint . 'directory')
            ->withHeader('Content-Type', 'application/json');
        $response = $this->httpClient->send($request);

        return json_decode($response->getBody());
    }

    /**
     * send JWK signed message to the CA
     *
     * @param string $resource url or resource key of the dictionary
     * @param $method
     * @param array $payload
     * @param string $urlAppend
     *
     * @return ResponseInterface
     * @throws RequestException
     */
    public function send($resource, $method, $payload = [], $urlAppend = '')
    {
        if ($this->key === null)
            throw new RuntimeException('Need a key for sending requests.');

        $directory = $this->getDirectory();
        $url = $resource;

        if (property_exists($directory, $resource))
            $url = $directory->$resource;

        if (strlen($urlAppend))
            $url = sprintf('%s/%s', $url, $urlAppend);

        if (is_array($payload) && !count($payload))
            $payload = new \stdClass();

        $data = $this->buildJWS($payload, $url);

        $request = $this->emptyRequest($method, $url)
            ->withHeader('Content-Type', 'application/jose+json');

        $request->getBody()->write(json_encode($data));

        $response = $this->httpClient->send($request);

        $this->checkResponse($response);

        return $response;
    }

    /**
     * send a get request
     *
     * @param $url
     * @param array $headers
     *
     * @return ResponseInterface
     * @throws RequestException
     */
    public function get($url, $headers = [])
    {
        $request = $this->emptyRequest('GET', $url)
            ->withHeader('Content-Type', 'application/json');

        foreach ($headers as $k => $v)
        {
            $request = $request->withHeader($k, $v);
        }

        $response = $this->httpClient->send($request);

        $this->checkResponse($response);

        return $response;
    }

    /**
     * @param ResponseInterface $response
     *
     * @throws RequestException
     */
    protected function checkResponse(ResponseInterface $response)
    {
        $data = null;
        if ($response->getBody()->getSize() > 0)
            $data = json_decode($response->getBody());

        $response->getBody()->rewind();

        if ($this->httpClient->isClientError($response))
            throw new RequestException($response, $data);

        if (!$this->httpClient->isSuccessful($response))
            throw new RequestException($response);
    }

    /**
     * build a new empty PSR-7 HttpRequest
     * @param $method
     * @param $url
     * @return Request
     */
    protected function emptyRequest($method, $url)
    {
        return new Request($method, new Body(''), Uri::createFromString($url));
    }

    /**
     * build JSON Web Signature
     *
     * @see https://tools.ietf.org/html/rfc7515
     * @see https://tools.ietf.org/html/rfc7517
     *
     * @param $payload
     * @param $url
     *
     * @return array
     */
    public function buildJWS($payload, $url)
    {
        $this->nonce = $this->newNonce();

        $jwsProtected = [
            'nonce' => $this->nonce,
            'url'   => $url
        ];

        return $this->key->sign($jwsProtected, $payload);
    }

    /**
     * @see https://tools.ietf.org/html/rfc7638
     */
    public function getJWKThumbprint()
    {
        return Helper::base64urlEncode($this->key->buildJWKThumbprint());
    }

    /**
     * @param KeyInterface $key
     * @return $this
     */
    public function setKey(KeyInterface $key)
    {
        $this->key = $key;

        return $this;
    }

    /**
     * @return KeyInterface
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * return main communication endpoint
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    // some helpers

    /**
     * @return Account
     */
    public function account()
    {
        return new Account($this);
    }

    /**
     * @return Order
     */
    public function order()
    {
        return new Order($this);
    }

    /**
     * @return Authorization
     */
    public function authorization()
    {
        return new Authorization($this);
    }

    /**
     * @return Challenge
     */
    public function challenge()
    {
        return new Challenge($this);
    }

    /**
     * @return Certificate
     */
    public function certificate()
    {
        return new Certificate($this);
    }
}
