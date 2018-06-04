<?php

namespace Acme2\Tests\Unit\Resources;

use Acme2\Tests\Unit\Stubs\Http\ClientErrorResponse;
use Acme2\Tests\Unit\Stubs\Http\GenericResponse;
use Acme2\Tests\Unit\Stubs\Http\OrderResponse;
use Karl\Acme2\Acme;
use Karl\Acme2\Exception\RequestException;
use Karl\Acme2\Http\Body;
use Karl\Acme2\Resources\Order;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class OrderTest extends TestCase
{
    /**
     * @var MockObject|Acme
     */
    protected $acmeMock;

    /**
     * @var Order
     */
    protected $order;

    public function setUp()
    {
        $this->acmeMock = $this->createMock(Acme::class);

        $this->order = new Order($this->acmeMock);
    }

    public function testCreate()
    {
        $response = (new GenericResponse())->withBody(new Body(<<<JSON
{
  "status": "pending",
  "expires": "2018-06-11T05:57:44Z",
  "identifiers": [
    {
      "type": "dns",
      "value": "acmetestaaa1.bretterklieber.com"
    }
  ],
  "authorizations": [
    "https://acme-staging-v02.api.letsencrypt.org/acme/authz/JxlGP0dEaCZxs7ofxZ_d5At7mx7sNdKEnUgpeiAmB7Y"
  ],
  "finalize": "https://acme-staging-v02.api.letsencrypt.org/acme/finalize/5994507/1732297"
}
JSON
        ))->withHeader('Location', 'https://acme-staging-v02.api.letsencrypt.org/acme/order/5994507/1732297')
            ->withStatus(201);

        $this->acmeMock->method('send')->willReturn($response);

        $ret = $this->order->create($this->getOrderData());

        $this->assertEquals((object)array(
            'status'         => 'pending',
            'expires'        => '2018-06-11T05:57:44Z',
            'identifiers'    =>
                [
                    (object)array(
                        'type'  => 'dns',
                        'value' => 'acmetestaaa1.bretterklieber.com',
                    ),
                ],
            'authorizations' =>
                ['https://acme-staging-v02.api.letsencrypt.org/acme/authz/JxlGP0dEaCZxs7ofxZ_d5At7mx7sNdKEnUgpeiAmB7Y'],
            'finalize'       => 'https://acme-staging-v02.api.letsencrypt.org/acme/finalize/5994507/1732297',
            'url'            => 'https://acme-staging-v02.api.letsencrypt.org/acme/order/5994507/1732297',
        ), $ret);
    }

    public function testCreateFailed()
    {
        $response = (new GenericResponse())->withStatus(400);

        $this->acmeMock->method('send')->willReturn($response);

        $ret = $this->order->create($this->getOrderData());

        $this->assertNull($ret);
    }

    /**
     * @expectedException \Karl\Acme2\Exception\RequestException
     */
    public function testCreateFailedNoOrderData()
    {
        $response = (new GenericResponse())->withStatus(400);

        $errorDetails = (object)[
            'type'   => 'urn:ietf:params:acme:error:malformed',
            'detail' => 'NewOrder request did not specify any identifiers',
            'status' => 400
        ];

        $this->acmeMock->method('send')->willThrowException(new RequestException($response, $errorDetails));

        $ret = $this->order->create(null);

        $this->assertNull($ret);
    }

    public function testGet()
    {
        $response = new OrderResponse();
        $this->acmeMock->method('get')->willReturn($response);

        $ret = $this->order->get('https://acme-staging-v02.api.letsencrypt.org/acme/order/5994507/1002745');

        $this->assertEquals((object)array(
            'status'         => 'valid',
            'expires'        => '2015-03-01T14:09:00Z',
            'identifiers'    =>
                [
                    (object)array(
                        'type'  => 'dns',
                        'value' => 'example.com',
                    ),
                    (object)array(
                        'type'  => 'dns',
                        'value' => 'www.example.com',
                    ),
                ],
            'notBefore'      => '2016-01-01T00:00:00Z',
            'notAfter'       => '2016-01-08T00:00:00Z',
            'authorizations' =>
                array(
                    0 => 'https://example.com/acme/authz/1234',
                    1 => 'https://example.com/acme/authz/2345',
                ),
            'finalize'       => 'https://example.com/acme/acct/1/order/1/finalize',
            'certificate'    => 'https://example.com/acme/cert/1234',
            'url'            => 'https://acme-staging-v02.api.letsencrypt.org/acme/order/5994507/1002745',
        ), $ret);
    }

    public function testGetNotFound()
    {
        $response = (new GenericResponse())->withStatus(404);

        $this->acmeMock->method('get')->willThrowException(new RequestException($response));

        $ret = $this->order->get('https://acme-staging-v02.api.letsencrypt.org/acme/order/5994507/1002745');

        $this->assertEquals(null, $ret);
    }

    /**
     * @expectedException \Karl\Acme2\Exception\RequestException
     */
    public function testGetWithError()
    {
        $response = new ClientErrorResponse();

        $this->acmeMock->method('get')->willThrowException(new RequestException($response));

        $this->order->get('https://acme-staging-v02.api.letsencrypt.org/acme/order/5994507/1002745');
    }

    public function testFinalize()
    {
        $response = new OrderResponse();

        $this->acmeMock->method('send')->willReturn($response);

        $ret = $this->order->finalize('https://acme-staging-v02.api.letsencrypt.org/acme/order/5994507/1002745', $this->getCSRSample());

        $this->assertEquals((object)array(
            'status'         => 'valid',
            'expires'        => '2015-03-01T14:09:00Z',
            'identifiers'    =>
                array(
                    (object)array(
                        'type'  => 'dns',
                        'value' => 'example.com',
                    ),
                    (object)array(
                        'type'  => 'dns',
                        'value' => 'www.example.com',
                    ),
                ),
            'notBefore'      => '2016-01-01T00:00:00Z',
            'notAfter'       => '2016-01-08T00:00:00Z',
            'authorizations' =>
                array(
                    0 => 'https://example.com/acme/authz/1234',
                    1 => 'https://example.com/acme/authz/2345',
                ),
            'finalize'       => 'https://example.com/acme/acct/1/order/1/finalize',
            'certificate'    => 'https://example.com/acme/cert/1234',
        ), $ret);
    }

    /**
     * @expectedException \Karl\Acme2\Exception\RequestException
     */
    public function testFinalizeInvalidCSR()
    {
        $response = new ClientErrorResponse();

        $this->acmeMock->method('send')->willThrowException(new RequestException($response));

        $this->order->finalize('https://acme-staging-v02.api.letsencrypt.org/acme/order/5994507/1002745', 'XXXX');
    }

    public function testAddIdentifier()
    {
        $orderData = $this->order->addIdentifier(null, 'foo.example.com');
        $this->assertInternalType('object', $orderData);
        $this->assertEquals((object)array(
            'identifiers' => [
                array(
                    'type'  => 'dns',
                    'value' => 'foo.example.com',
                ),
            ],
        ), $orderData);
    }

    public function testAddIdentifierExisting()
    {
        $orderData = new \stdClass();

        $this->order->addIdentifier($orderData, 'foo.example.com');
        $this->assertInternalType('object', $orderData);
        $this->assertEquals((object)array(
            'identifiers' => [
                array(
                    'type'  => 'dns',
                    'value' => 'foo.example.com',
                ),
            ],
        ), $orderData);
    }

    public function testAddMultipleIdentifiers()
    {
        $orderData = new \stdClass();

        $this->order->addIdentifier($orderData, 'foo.example.com');
        $this->order->addIdentifier($orderData, 'example.com');
        $this->assertInternalType('object', $orderData);
        $this->assertEquals((object)array(
            'identifiers' => [
                array(
                    'type'  => 'dns',
                    'value' => 'foo.example.com',
                ),
                array(
                    'type'  => 'dns',
                    'value' => 'example.com',
                ),
            ],
        ), $orderData);
    }


    protected function getOrderData()
    {
        $data              = new \stdClass();
        $data->identifiers = [
            [
                'type'  => "dns",
                'value' => "acmetestXXX1.bretterklieber.com"
            ]
        ];

        return $data;
    }

    protected function getCSRSample()
    {
        return <<<EOT
-----BEGIN CERTIFICATE REQUEST-----
MIICvzCCAacCAQAwejELMAkGA1UEBhMCQVQxEjAQBgNVBAgMCVN0ZWllcm1hazEN
MAsGA1UEBwwER3JhejEhMB8GA1UECgwYSW50ZXJuZXQgV2lkZ2l0cyBQdHkgTHRk
MSUwIwYDVQQDDBxhY21ldGVzdDYuYnJldHRlcmtsaWViZXIuY29tMIIBIjANBgkq
hkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA0n6LMuzG1kVfFNzdih76wn+15poR75Gk
pqYdWf8svVXcex0QUxkQTqceNm6qs2ISEh07QnDblBeN07HzxosWap3bYOcwOFXU
La96uisfBl7yBVQmXJFksGYdiVbZFBFJ4ONw2KfXLkkjmtHSoXuEdFeIKCgOSzxY
N+uzBUvV6TYp9rBtRttzJ6TMFL81ur7qzmRhy6bpA4lv/B5nQPeXwVc6YF0+nFiQ
hXD0NOuE4K1BshoPzS/4PXpuVFZN9tKdJ5PxybwIeJl+0Su9CtvRHoZosgulMS6K
7yVpiwDW5p9kijcIOj4LdS7+3SsnAec2m1IfzEp6e/2UzkwA0/oyUQIDAQABoAAw
DQYJKoZIhvcNAQELBQADggEBAFIVZx4YYc3tQCp6HRON+Rzib9HHOD57QMle1wcw
/OHuniREZRj8/o+OSX27idA6ZTd9xmPRjrpm7q1hyPTRdd88f1GvzdqNWY3+p2d9
8h5FYFYfLiXspzCFJXLkojdxUlC0CwuHbHOw0QKAY0jT9fwGl51/VHotP0OCQWMD
2JeBjDjRHtTj8Rx39crT7U2AP1VU8qj3kPNt/xx/JN1PfhH6vcPhAeNds1dRazxX
MwMrgmMCApWLGONICuP440rGcd/wGoH3Kh4I0Ov/5A9e/JwcS/+UcsQ2XETVFI5F
2as5IcF2amCL09h3bPJBKeFlwX9I1gw0iaxxmwUtGX+glQo=
-----END CERTIFICATE REQUEST-----
EOT;

    }
}
