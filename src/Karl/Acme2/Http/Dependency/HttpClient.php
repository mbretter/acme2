<?php

namespace Karl\Acme2\Http\Dependency;

use Karl\Acme2\Http\BuiltinClient;
use Karl\Acme2\Http\ClientInterface;

trait HttpClient
{
    /** @var ClientInterface */
    protected $httpClient;

    public function setHttpClient(ClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient;
        if ($httpClient === null)
        {
            $this->httpClient = new BuiltinClient();
        }

        return $this;
    }

    /**
     * @codeCoverageIgnore
     *
     * @return ClientInterface
     */
    public function getHttpClient()
    {
        return $this->httpClient;
    }

}