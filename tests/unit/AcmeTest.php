<?php

namespace Acme2\Tests\Unit;

use Acme2\Tests\Unit\Stubs\Http\ClientErrorResponse;
use Acme2\Tests\Unit\Stubs\Http\DirectoryResponse;
use Acme2\Tests\Unit\Stubs\Http\GenericResponse;
use Acme2\Tests\Unit\Stubs\Http\NonceResponse;
use Acme2\Tests\Unit\Stubs\Http\OrderResponse;
use Karl\Acme2\Acme;
use Karl\Acme2\Exception\RequestException;
use Karl\Acme2\Http\ClientInterface;
use Karl\Acme2\Key\KeyInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;

class AcmeTest extends TestCase
{
    /**
     * @var MockObject|ClientInterface
     */
    protected $httpClientMock;

    /**
     * @var MockObject|KeyInterface
     */
    protected $keyMock;

    /**
     * @var Acme
     */
    protected $acme;

    public function setUp()
    {
        $this->httpClientMock = $this->createMock(ClientInterface::class);

        $this->keyMock = $this->createMock(KeyInterface::class);

        $this->acme = new Acme();
        $this->acme->setHttpClient($this->httpClientMock);
    }

    public function testConstructor()
    {
        $acme = new Acme(false);
        $this->assertEquals('https://acme-v02.api.letsencrypt.org/', $acme->getEndpoint());

        $acme = new Acme();
        $this->assertEquals('https://acme-staging-v02.api.letsencrypt.org/', $acme->getEndpoint());

        $acme = new Acme('https://fooca.example.com/');
        $this->assertEquals('https://fooca.example.com/', $acme->getEndpoint());
    }

    public function testFetchDirectory()
    {
        $this->httpClientMock->method('send')->willReturn(new DirectoryResponse());

        $ret = $this->acme->fetchDirectory();
        $this->assertInstanceOf(\stdClass::class, $ret);
        $this->assertObjectHasAttribute('newAccount', $ret);
        $this->assertObjectHasAttribute('newNonce', $ret);
        $this->assertObjectHasAttribute('newOrder', $ret);
        $this->assertObjectHasAttribute('revokeCert', $ret);
    }

    public function testGetDirectory()
    {
        $this->httpClientMock->expects($this->exactly(1))->method('send')->willReturn(new DirectoryResponse());

        $ret1 = $this->acme->getDirectory();
        $ret2 = $this->acme->getDirectory();
        $this->assertEquals($ret1, $ret2);
    }

    public function testNewNonce()
    {
        $this->httpClientMock->expects($this->at(0))->method('send')->willReturn(new DirectoryResponse());
        $this->httpClientMock->expects($this->at(1))->method('send')->willReturn(new NonceResponse());

        $nonce = $this->acme->newNonce();
        $this->assertEquals('5ZnnhQhAj5l5qm4Y9ZYvjE9Fh5mIEsc9hBEy_lhDKqM', $nonce);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Need a key
     */
    public function testSendWithoutKey()
    {
        $this->acme->send('newAccount', 'post');
    }

    public function testSend()
    {
        $this->acme->setKey($this->keyMock);
        $nonceResponse = new NonceResponse();
        $this->httpClientMock->expects($this->at(0))->method('send')->willReturn(new DirectoryResponse());
        $this->httpClientMock->expects($this->at(1))->method('send')->willReturn($nonceResponse);
        $response = new GenericResponse();
        $this->httpClientMock->expects($this->at(2))->method('send')->willReturn($response);

        $this->keyMock->method('sign')->with([
            'nonce' => $nonceResponse->getNonce(),
            'url'   => 'https://acme-staging-v02.api.letsencrypt.org/acme/new-acct',
        ], new \stdClass());

        $this->httpClientMock->method('isSuccessful')->willReturn(true);

        $resp = $this->acme->send('newAccount', 'post');
        $this->assertEquals($response, $resp);
    }

    public function testSendWithError()
    {
        $this->acme->setKey($this->keyMock);
        $this->httpClientMock->expects($this->at(0))->method('send')->willReturn(new DirectoryResponse());
        $this->httpClientMock->expects($this->at(1))->method('send')->willReturn(new NonceResponse());
        $response = new ClientErrorResponse();
        $this->httpClientMock->expects($this->at(2))->method('send')->willReturn($response);

        $this->httpClientMock->method('isClientError')->willReturn(false);
        $this->httpClientMock->method('isSuccessful')->willReturn(false);

        $this->expectException(RequestException::class);

        try
        {
            $this->acme->send('newAccount', 'post');
        } catch (RequestException $e)
        {
            $this->assertNull($e->getDetails());
            throw $e;
        }
    }

    public function testSendWithErrorDetails()
    {
        $this->acme->setKey($this->keyMock);
        $this->httpClientMock->expects($this->at(0))->method('send')->willReturn(new DirectoryResponse());
        $this->httpClientMock->expects($this->at(1))->method('send')->willReturn(new NonceResponse());
        $response = new ClientErrorResponse();
        $this->httpClientMock->expects($this->at(2))->method('send')->willReturn($response);

        $this->httpClientMock->method('isClientError')->willReturn(true);

        $this->expectException(RequestException::class);

        try
        {
            $this->acme->send('newAccount', 'post');
        } catch (RequestException $e)
        {
            $det = $e->getDetails();
            $this->assertInstanceOf(\stdClass::class, $det);
            $this->assertObjectHasAttribute('type', $det);
            $this->assertObjectHasAttribute('detail', $det);
            $this->assertObjectHasAttribute('subproblems', $det);
            $this->assertEquals('urn:ietf:params:acme:error:malformed', $det->type);
            $this->assertEquals('Some of the identifiers requested were rejected', $det->detail);
            $this->assertInternalType('array', $det->subproblems);
            $this->assertCount(2, $det->subproblems);
            throw $e;
        }
    }

    public function testSendWithPayload()
    {
        $payload = (object)['foo' => 'bar'];
        $protected = [
            'protected' => 'eyJub25jZSI6IjVabm5oUWhBajVsNXFtNFk5Wll2akU5Rmg1bUlFc2M5aEJFeV9saERLcU0iLCJ1cmwiOiJodHRwczpcL1wvYWNtZS1zdGFnaW5nLXYwMi5hcGkubGV0c2VuY3J5cHQub3JnXC9hY21lXC9uZXctYWNjdCIsImFsZyI6IlJTMjU2IiwiandrIjp7Imt0eSI6IlJTQSIsIm4iOiJzTmM5dW42ZUZYcWw4eldxLVY3SVhScVhPSmtiNGIwMWl3aXlZMnNlbWR1RGU3eVgtWEl5VnJXcWExUU1tZkRPMHZ6YldyWkpLSFIxMV80bjMwekJjTzlYN0Foek50LXRJVm95MU9CVUVDMDNvdUFVOXJlaGExUFpBb2liX3JWUGNadVNqWjVnRHhwUEV0TXJuS0JJeVM3QXIzd3Z5N3ZHUWo1RjgxV3docEgxUGgzeElDSmtYeFhuNlNnTGdOdGN1TUNaZzFnODUzbFFoSHVRVzc0Z2FsdTJ4NFA5TmtfQ0FOXzlsYUJJamZHVnhlMzZUYUZXOEprQzQwUWYxTDduaHQ3NWZDMzVOVnp0Y2prWkRId3ZNSGszV0wwaGdjZU1wT19KQ1RqNTM2T1pnb3pOWkJRU2pFYzFPT0lweV96OVBQVWJiZWZRbWh2QkR1TXR1ZjhOMFEiLCJlIjoiQVFBQiJ9fQ',
            'payload'   => 'eyJmb28iOiJiYXIifQ',
            'signature' => 'ef9xSZ2ktbYZ6BtsIfOG7ypvac46dVXGsvdzAX9IkcwJSabeSP7U9fZX6iNBvvMpkaEO7VJ-hnXGCtWhqF5EzlCMgD_8U3MN21K3-9cJHM-CCp7d23Z3sd_NWqGeNcsfDqcIVi9SZdtCAH2FVbA7cXdYTmDdUsn5q3dNhRxhqpRFqfET9S5BMyn3vDZi-gcjl8vsqtV6nZq_G1Mjso6JpgxSnnsW4qzv5K4xP8ar9JsAa9tEDA8-kUS1VGRrdI74a4cR_zttp5OkwZf-nGD281TZE7UQIXDpmUQNV3IUxMmKsbNksfNvb0byGQyaUiPdkQVML6Mit-A3RbZ7jj3Uxw'
        ];

        $this->keyMock->method('sign')->willReturn($protected);
        $this->acme->setKey($this->keyMock);

        $this->httpClientMock->expects($this->at(0))->method('send')->willReturn(new DirectoryResponse());
        $this->httpClientMock->expects($this->at(1))->method('send')->willReturn(new NonceResponse());
        $response = new GenericResponse();
        $this->httpClientMock->expects($this->at(2))->method('send')->with($this->callback(function ($subject) use ($protected) {
            /** @var RequestInterface $subject */
            $subject->getBody()->rewind();

            return (string)$subject->getBody() == json_encode($protected);
        }))->willReturn($response);

        $this->httpClientMock->method('isSuccessful')->willReturn(true);

        $resp = $this->acme->send('newAccount', 'post', $payload);
        $this->assertEquals($response, $resp);
    }

    public function testSendWithUrlAppend()
    {
        $this->acme->setKey($this->keyMock);
        $nonceResponse = new NonceResponse();
        $this->httpClientMock->expects($this->at(0))->method('send')->willReturn(new DirectoryResponse());
        $this->httpClientMock->expects($this->at(1))->method('send')->willReturn($nonceResponse);
        $response = new GenericResponse();
        $this->httpClientMock->expects($this->at(2))->method('send')->willReturn($response);

        $this->keyMock->method('sign')->with([
            'nonce' => $nonceResponse->getNonce(),
            'url'   => 'https://acme-staging-v02.api.letsencrypt.org/acme/new-acct/whatever',
        ], new \stdClass());

        $this->httpClientMock->method('isSuccessful')->willReturn(true);

        $resp = $this->acme->send('newAccount', 'post', [], 'whatever');
        $this->assertEquals($response, $resp);
    }

    public function testGetKey()
    {
        $this->acme->setKey($this->keyMock);
        $this->assertEquals($this->keyMock, $this->acme->getKey());
    }

    public function testBuildJWKThumbprint()
    {
        $this->keyMock->method('buildJWKThumbprint')->willReturn(hex2bin('da7ad19c1cc5ed6af7862b98150ca7885245f808a8c83390d55141dfa2a3f7a4'));

        $this->acme->setKey($this->keyMock);

        $res = $this->acme->getJWKThumbprint();
        $this->assertEquals('2nrRnBzF7Wr3hiuYFQyniFJF-AioyDOQ1VFB36Kj96Q', $res);
    }

    public function testGet()
    {
        $this->acme->setKey($this->keyMock);
        $response = new OrderResponse();

        $this->httpClientMock->method('send')->with($this->callback(function ($subject) {
            /** @var RequestInterface $subject */
            return $subject->getHeaderLine('Content-Type') == 'application/json';
        }))->willReturn($response);

        $this->httpClientMock->method('isSuccessful')->willReturn(true);
        $res = $this->acme->get('https://acme-staging-v02.api.letsencrypt.org/acme/order/5994507/1002745', ['Content-Type' => 'application/json']);
        $this->assertEquals($res, $response);
    }

    public function testAccount()
    {
        $this->assertInstanceOf('Karl\Acme2\Resources\Account', $this->acme->account());
    }

    public function testAuthorization()
    {
        $this->assertInstanceOf('Karl\Acme2\Resources\Authorization', $this->acme->authorization());
    }

    public function testCertificate()
    {
        $this->assertInstanceOf('Karl\Acme2\Resources\Certificate', $this->acme->certificate());
    }

    public function testChallenge()
    {
        $this->assertInstanceOf('Karl\Acme2\Resources\Challenge', $this->acme->challenge());
    }

    public function testOrder()
    {
        $this->assertInstanceOf('Karl\Acme2\Resources\Order', $this->acme->order());
    }

}
