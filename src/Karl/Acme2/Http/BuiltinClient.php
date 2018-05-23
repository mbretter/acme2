<?php

namespace Karl\Acme2\Http;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class BuiltinClient implements ClientInterface
{
    /**
     * send request
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function send(RequestInterface $request)
    {
        $method = strtolower($request->getMethod());

        if (!method_exists($this, $method))
            throw new \InvalidArgumentException("Method '$method' not supported.");

        $request->getBody()->rewind();
        return $this->$method($request);
    }

    /**
     * send get request
     *
     * @param RequestInterface $request
     *
     * @return Response
     */
    protected function get(RequestInterface $request)
    {
        $opts = [
            'http' => [
                'ignore_errors' => true,
            ]
        ];

        $opts['http']['header'] = $this->buildRequestHeaders($request);

        $context = stream_context_create($opts);

        $body = file_get_contents((string)$request->getUri(), false, $context);

        $statusInfo = $this->parseStatusLine($http_response_header[0]);

        $headers = $this->parseHeaders($http_response_header);

        $response = new Response($headers, $body);

        return $response->withStatus($statusInfo->statusCode, $statusInfo->reasonPhrase);
    }

    /**
     * send post request
     *
     * @param RequestInterface $request
     *
     * @return Response
     */
    protected function post(RequestInterface $request)
    {

        $opts = [
            'http' => [
                'method'        => 'POST',
                'ignore_errors' => true,
                'content'       => (string)$request->getBody()
            ]
        ];

        $opts['http']['header'] = $this->buildRequestHeaders($request);

        $context = stream_context_create($opts);

        $body = @file_get_contents((string)$request->getUri(), false, $context);

        $statusInfo = $this->parseStatusLine($http_response_header[0]);

        $headers = $this->parseHeaders($http_response_header);

        $response = new Response($headers, $body);

        return $response->withStatus($statusInfo->statusCode, $statusInfo->reasonPhrase);
    }

    /**
     * send head request
     *
     * @param RequestInterface $request
     *
     * @return bool|Response
     */
    protected function head(RequestInterface $request)
    {
        $context = stream_context_create([
            'http' => [
                'method' => 'HEAD'
            ]
        ]);

        $opts['http']['header'] = $this->buildRequestHeaders($request);

        $headers = get_headers((string)$request->getUri(), 0, $context);
        if ($headers === false)
            return false;

        $statusInfo = $this->parseStatusLine($http_response_header[0]);

        $headers = $this->parseHeaders($http_response_header);

        $response = new Response($headers);

        return $response->withStatus($statusInfo->statusCode, $statusInfo->reasonPhrase);
    }


    /**
     * Build request header string
     *
     * @param RequestInterface $request
     *
     * @return string
     */
    protected function buildRequestHeaders(RequestInterface $request)
    {
        $hdrs = [];
        foreach ($request->getHeaders() as $name => $values)
        {
            $hdrs[] = $name . ": " . implode(", ", $values);
        }

        return implode("\r\n", $hdrs);
    }

    /**
     * parse status code this line is part of the http_response_headers
     *
     * @param $statusLine
     *
     * @return \stdClass
     */
    protected function parseStatusLine($statusLine)
    {
        $ret               = new \stdClass();
        $ret->statusCode   = 200;
        $ret->reasonPhrase = '';

        $statusAll       = explode(' ', $statusLine, 3);
        $ret->protocol   = $statusAll[0];
        $ret->statusCode = (int)$statusAll[1];
        if (count($statusAll) > 2)
            $ret->reasonPhrase = $statusAll[2];

        return $ret;
    }

    /**
     * simple parsing method for headers as returned by superglobal $http_response_header
     * not very sophisticated, but enough for us
     *
     * @param $headers
     *
     * @return array
     */
    protected function parseHeaders($headers)
    {
        $ret = [];
        foreach ($headers as $idx => $v)
        {
            $t = explode(':', $v, 2);
            if (count($t) != 2)
                continue;

            $key = trim($t[0]);
            if (!isset($ret[$key]))
                $ret[$key] = [];

            $ret[$key][] = trim($t[1]);
        }

        return $ret;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return bool
     */
    public function isSuccessful(ResponseInterface $response)
    {
        return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return bool
     */
    public function isClientError(ResponseInterface $response)
    {
        return $response->getStatusCode() >= 400 && $response->getStatusCode() < 500;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return bool
     */
    public function isOk(ResponseInterface $response)
    {
        return $response->getStatusCode() == 200;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return bool
     */
    public function isNotFound(ResponseInterface $response)
    {
        return $response->getStatusCode() == 404;
    }

}