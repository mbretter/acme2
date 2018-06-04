<?php


namespace Acme2\Tests\Unit\Exception;

use Karl\Acme2\Exception\RequestException;
use Karl\Acme2\Http\Response;
use PHPUnit\Framework\TestCase;

class BuiltinClientTest extends TestCase
{

    public function testThrowWithDetails()
    {
        $details  = json_decode('{
  "type": "urn:ietf:params:acme:error:userActionRequired",
  "detail": "Terms of service have changed",
  "instance": "https://example.com/acme/agreement/?token=W8Ih3PswD-8"
}');
        $response = (new Response([]))->withStatus(400);

        try
        {
            $exc = new RequestException($response, $details);
            throw $exc;
        } catch (RequestException $e)
        {
            $this->assertEquals($details, $e->getDetails());
            $this->assertEquals('urn:ietf:params:acme:error:userActionRequired', $e->getDetailType());
        }

    }

    public function testThrowWithoutDetails()
    {
        $response = (new Response([]))->withStatus(400);

        try
        {
            $exc = new RequestException($response, new \stdClass());
            throw $exc;
        } catch (RequestException $e)
        {
            $this->assertNull($e->getDetailType());
        }

    }
}

