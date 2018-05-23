<?php

namespace Karl\Acme2\Exception;

use Exception;
use Psr\Http\Message\ResponseInterface;

class RequestException extends Exception
{
    protected $details;

    public function __construct(ResponseInterface $response, $details = null)
    {
        $this->details = $details;
        $message = $response->getReasonPhrase();
        if ($details !== null && isset($details->detail))
            $message = $details->detail;

        parent::__construct($message, $response->getStatusCode());
    }

    public function getDetails()
    {
        return $this->details;
    }
}