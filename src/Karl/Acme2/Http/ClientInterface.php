<?php

namespace Karl\Acme2\Http;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
    /**
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     */
    public function send(RequestInterface $request);

    public function isSuccessful(ResponseInterface $response);

    public function isClientError(ResponseInterface $response);

    public function isOk(ResponseInterface $response);

    public function isNotFound(ResponseInterface $response);
}