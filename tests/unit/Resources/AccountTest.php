<?php

namespace Acme2\Tests\Unit\Resources;

use Acme2\Tests\Unit\Stubs\Http\AccountNotFoundResponse;
use Acme2\Tests\Unit\Stubs\Http\AccountResponse;
use Acme2\Tests\Unit\Stubs\Http\ClientErrorResponse;
use Acme2\Tests\Unit\Stubs\Http\GenericResponse;
use Karl\Acme2\Acme;
use Karl\Acme2\Exception\RequestException;
use Karl\Acme2\Http\Body;
use Karl\Acme2\Resources\Account;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class AccountTest extends TestCase
{
    /**
     * @var MockObject|Acme
     */
    protected $acmeMock;

    /**
     * @var Account
     */
    protected $account;

    public function setUp()
    {
        $this->acmeMock = $this->createMock(Acme::class);

        $this->account = new Account($this->acmeMock);
    }

    public function testLookup()
    {
        $accountUrl = 'https://acme-v02.api.letsencrypt.org/acme/acct/35470286';
        $response   = (new GenericResponse())->withHeader('Location', $accountUrl);

        $this->acmeMock->method('send')->with('newAccount', 'post', ['onlyReturnExisting' => true])->willReturn($response);

        $ret = $this->account->lookup();

        $this->assertInstanceOf(\stdClass::class, $ret);
        $this->assertEquals($accountUrl, $ret->url);
    }

    public function testLookupNotFound()
    {
        $response = new AccountNotFoundResponse();

        $this->acmeMock->method('send')->willThrowException(new RequestException($response, json_decode($response->getBody())));

        $this->assertNull($this->account->lookup());
    }

    public function testLookupWith201()
    {
        $response = (new GenericResponse())->withStatus(201);

        $this->acmeMock->method('send')->willReturn($response);

        $this->assertNull($this->account->lookup());
    }

    /**
     * @expectedException \Karl\Acme2\Exception\RequestException
     */
    public function testLookupWithError()
    {
        $response = new ClientErrorResponse();

        $this->acmeMock->method('send')->willThrowException(new RequestException($response, json_decode($response->getBody())));

        $this->assertNull($this->account->lookup());
    }

    public function testCreate()
    {
        $response = (new AccountResponse())->withHeader('Location', 'https://example.com/acme/acct/1')->withStatus(201);

        $this->acmeMock->method('send')->willReturn($response);

        $ret = $this->account->create();

        $this->assertInstanceOf(\stdClass::class, $ret);
        $this->assertEquals('valid', $ret->status);
        $this->assertEquals('mailto:cert-admin@example.com', $ret->contact[0]);
    }

    public function testGet()
    {
        $response = new AccountResponse();

        $this->acmeMock->method('send')->willReturn($response);

        $ret = $this->account->get('https://example.com/acme/acct/1');

        $this->assertInstanceOf(\stdClass::class, $ret);
        $this->assertEquals('valid', $ret->status);
        $this->assertEquals('mailto:cert-admin@example.com', $ret->contact[0]);
    }

    public function testDeactivate()
    {
        $response = new AccountResponse();

        $this->acmeMock->method('send')->willReturn($response);

        $ret = $this->account->deactivate('https://example.com/acme/acct/1');

        $this->assertInstanceOf(\stdClass::class, $ret);
        $this->assertEquals('valid', $ret->status);
        $this->assertEquals('mailto:cert-admin@example.com', $ret->contact[0]);
    }

    public function testUpdate()
    {
        $response = new AccountResponse();

        $this->acmeMock->method('send')->willReturn($response);

        $ret = $this->account->update('https://example.com/acme/acct/1', [
            'contact'              => [
                'mailto:foo@example.com'
            ],
            'termsOfServiceAgreed' => true
        ]);

        $this->assertInstanceOf(\stdClass::class, $ret);
        $this->assertEquals('valid', $ret->status);
    }

    public function testGetOrders()
    {
        $response = (new GenericResponse())->withBody(new Body(<<<JSON
{
  "orders": [
    "https://example.com/acme/acct/1/order/1",
    "https://example.com/acme/acct/1/order/2",
    "https://example.com/acme/acct/1/order/50"
  ]
}
JSON
        ));

        $this->acmeMock->method('get')->willReturn($response);

        $ret = $this->account->getOrders('https://example.com/acme/acct/1/orders');

        $this->assertInstanceOf(\stdClass::class, $ret);
        $this->assertObjectHasAttribute('orders', $ret);
        $this->assertInternalType('array', $ret->orders);
        $this->assertCount(3, $ret->orders);
    }
}
