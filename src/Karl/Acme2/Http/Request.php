<?php

namespace Karl\Acme2\Http;


use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;
use InvalidArgumentException;

/**
 * Class Request
 *
 * based on Slims PSR-7 Uri implementation https://github.com/slimphp/Slim
 *
 * @package Karl\Acme2\Http
 */
class Request extends Message implements RequestInterface
{
    /** @var Uri */
    protected $uri;

    protected $method;

    protected $requestTarget;


    /**
     * Create new HTTP request.
     *
     * Adds a host header when none was provided and a host is defined in uri.
     *
     * @param string           $method        The request method
     * @param UriInterface     $uri           The request URI object
     * @param StreamInterface  $body          The request body object
     * @throws InvalidArgumentException on invalid HTTP method
     */
    public function __construct(
        $method,
        StreamInterface $body,
        UriInterface $uri = null
    ) {
        try {
            $this->method = $this->filterMethod($method);
        } catch (InvalidArgumentException $e) {
            $this->method = $method;
        }

        $this->uri = $uri;
        $this->body = $body;

        // if the request had an invalid method, we can throw it now
        if (isset($e) && $e instanceof InvalidArgumentException) {
            throw $e;
        }
    }

    /**
     * Retrieves the message's request target.
     *
     * Retrieves the message's request-target either as it will appear (for
     * clients), as it appeared at request (for servers), or as it was
     * specified for the instance (see withRequestTarget()).
     *
     * In most cases, this will be the origin-form of the composed URI,
     * unless a value was provided to the concrete implementation (see
     * withRequestTarget() below).
     *
     * If no URI is available, and no request-target has been specifically
     * provided, this method MUST return the string "/".
     *
     * @return string
     */
    public function getRequestTarget()
    {
        if ($this->requestTarget)
            return $this->requestTarget;

        if ($this->uri === null)
            return '/';

        $path     = $this->uri->getPath();
        $path     = '/' . ltrim($path, '/');

        $query = $this->uri->getQuery();
        if ($query)
            $path .= '?' . $query;

        $this->requestTarget = $path;

        return $this->requestTarget;
    }

    /**
     * Return an instance with the specific request-target.
     *
     * If the request needs a non-origin-form request-target — e.g., for
     * specifying an absolute-form, authority-form, or asterisk-form —
     * this method may be used to create an instance with the specified
     * request-target, verbatim.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request target.
     *
     * @link http://tools.ietf.org/html/rfc7230#section-5.3 (for the various
     *     request-target forms allowed in request messages)
     *
     * @param mixed $requestTarget
     *
     * @return static
     */
    public function withRequestTarget($requestTarget)
    {
        if (preg_match('#\s#', $requestTarget)) {
            throw new InvalidArgumentException(
                'Invalid request target provided; must be a string and cannot contain whitespace'
            );
        }
        $clone = clone $this;
        $clone->requestTarget = $requestTarget;

        return $clone;
    }

    /**
     * Retrieves the HTTP method of the request.
     *
     * @return string Returns the request method.
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Return an instance with the provided HTTP method.
     *
     * While HTTP method names are typically all uppercase characters, HTTP
     * method names are case-sensitive and thus implementations SHOULD NOT
     * modify the given string.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * changed request method.
     *
     * @param string $method Case-sensitive method.
     *
     * @return static
     * @throws \InvalidArgumentException for invalid HTTP methods.
     */
    public function withMethod($method)
    {
        $method                = $this->filterMethod($method);
        $clone                 = clone $this;
        $clone->method         = $method;

        return $clone;
    }

    /**
     * Retrieves the URI instance.
     *
     * This method MUST return a UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     * @return UriInterface Returns a UriInterface instance
     *     representing the URI of the request.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Returns an instance with the provided URI.
     *
     * This method MUST update the Host header of the returned request by
     * default if the URI contains a host component. If the URI does not
     * contain a host component, any pre-existing Host header MUST be carried
     * over to the returned request.
     *
     * You can opt-in to preserving the original state of the Host header by
     * setting `$preserveHost` to `true`. When `$preserveHost` is set to
     * `true`, this method interacts with the Host header in the following ways:
     *
     * - If the Host header is missing or empty, and the new URI contains
     *   a host component, this method MUST update the Host header in the returned
     *   request.
     * - If the Host header is missing or empty, and the new URI does not contain a
     *   host component, this method MUST NOT update the Host header in the returned
     *   request.
     * - If a Host header is present and non-empty, this method MUST NOT update
     *   the Host header in the returned request.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the message, and MUST return an instance that has the
     * new UriInterface instance.
     *
     * @link http://tools.ietf.org/html/rfc3986#section-4.3
     *
     * @param UriInterface $uri New request URI to use.
     * @param bool $preserveHost Preserve the original state of the Host header.
     *
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $clone      = clone $this;
        $clone->uri = $uri;

        if (!$preserveHost)
        {
            if ($uri->getHost() !== '')
                $clone->headers['Host'] = $uri->getHost();
        }
        else
        {
            if ($uri->getHost() !== '' && (!$this->hasHeader('Host') || $this->getHeaderLine('Host') === ''))
                $clone->headers['Host'] = $uri->getHost();
        }

        return $clone;
    }

    //
    // Helper
    //

    /**
     * Validate the HTTP method
     *
     * @param  null|string $method
     *
     * @return null|string
     * @throws \InvalidArgumentException on invalid HTTP method.
     */
    protected function filterMethod($method)
    {
        if ($method === null)
        {
            return $method;
        }

        if (!is_string($method))
        {
            throw new InvalidArgumentException(sprintf(
                'Unsupported HTTP method; must be a string, received %s',
                (is_object($method) ? get_class($method) : gettype($method))
            ));
        }

        $method = strtoupper($method);
        if (preg_match("/^[!#$%&'*+.^_`|~0-9a-z-]+$/i", $method) !== 1)
        {
            throw new InvalidArgumentException($method);
        }

        return $method;
    }

}