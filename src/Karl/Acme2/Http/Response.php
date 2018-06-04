<?php

namespace Karl\Acme2\Http;

use Psr\Http\Message\ResponseInterface;

/**
 * Class Response
 *
 * @package Karl\Acme2\Http
 * @codeCoverageIgnore
 */
class Response extends Message implements ResponseInterface
{
    protected $headers = [];
    protected $statusCode = 200;
    protected $reasonPhrase = '';
    protected $statusLine = '';
    protected $body;

    public function __construct($headers, $bodyString = null)
    {
        $this->headers    = $headers;
        $this->body       = new Body($bodyString);
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     *
     * If no reason phrase is specified, implementations MAY choose to default
     * to the RFC 7231 or IANA recommended reason phrase for the response's
     * status code.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * updated status and reason phrase.
     *
     * @link http://tools.ietf.org/html/rfc7231#section-6
     * @link http://www.iana.org/assignments/http-status-codes/http-status-codes.xhtml
     *
     * @param int $code The 3-digit integer result code to set.
     * @param string $reasonPhrase The reason phrase to use with the
     *     provided status code; if none is provided, implementations MAY
     *     use the defaults as suggested in the HTTP specification.
     *
     * @return static
     * @throws \InvalidArgumentException For invalid status code arguments.
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $clone               = clone $this;
        $clone->statusCode   = $code;
        $clone->reasonPhrase = $reasonPhrase;

        return $clone;
    }

}