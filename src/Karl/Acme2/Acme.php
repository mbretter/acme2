<?php


namespace Karl\Acme2;

use Karl\Acme2\Exception\RequestException;
use Karl\Acme2\Http\ClientInterface;
use Karl\Acme2\Http\Dependency\HttpClient;
use Karl\Acme2\Key\KeyInterface;
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

    protected $nonce;

    protected $endpoints = [
        'prod'    => 'https://acme-v02.api.letsencrypt.org/',
        'staging' => 'https://acme-staging-v02.api.letsencrypt.org/'
    ];

    protected $endpoint = null;

    /**
     * @var KeyInterface
     */
    protected $key = null;

    /** @var \stdClass */
    protected $directory;


    public function __construct($staging = true, ClientInterface $httpClient = null)
    {
        $this->endpoint = $staging ? $this->endpoints['staging'] : $this->endpoints['prod'];
        $this->setHttpClient($httpClient);
    }

    public function newNonce()
    {
        $directory = $this->getDirectory();
        $request   = $this->emptyRequest('HEAD', $directory->newNonce);

        $response = $this->httpClient->send($request);

        return $response->getHeaderLine('Replay-Nonce');
    }

    public function getDirectory()
    {
        if ($this->directory === null)
            $this->directory = $this->fetchDirectory();

        return $this->directory;
    }

    public function fetchDirectory()
    {
        $request  = $this->emptyRequest('GET', $this->endpoint . 'directory')
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
        $url       = $resource;

        if (property_exists($directory, $resource))
            $url = $directory->$resource;

        if (strlen($urlAppend))
            $url = sprintf('%s/%s', $url, $urlAppend);

        if (!count($payload))
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
     * @param $url
     *
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
            $request = $request->withHeader($k, $v);

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

    public function setKey(KeyInterface $key)
    {
        $this->key = $key;

        return $this;
    }

    public function getKey()
    {
        return $this->key;
    }

    protected function emptyRequest($method, $url)
    {
        return new Request($method, new Body(''), Uri::createFromString($url));
    }

}
