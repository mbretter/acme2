<?php

namespace Acme2\Tests\Unit\Resources;

use Acme2\Tests\Unit\Stubs\Http\GenericResponse;
use Karl\Acme2\Acme;
use Karl\Acme2\Http\Body;
use Karl\Acme2\Resources\Authorization;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class AuthorizationTest extends TestCase
{
    /**
     * @var MockObject|Acme
     */
    protected $acmeMock;

    /**
     * @var Authorization
     */
    protected $authorization;

    public function setUp()
    {
        $this->acmeMock = $this->createMock(Acme::class);

        $this->authorization = new Authorization($this->acmeMock);
    }

    public function testGet()
    {
        $response = (new GenericResponse())->withBody(new Body($this->getSample()));

        $this->acmeMock->method('get')->willReturn($response);

        $ret = $this->authorization->get('https://example.com/acme/authz/1234');

        $this->assertInstanceOf(\stdClass::class, $ret);
        $this->assertObjectHasAttribute('status', $ret);
        $this->assertObjectHasAttribute('identifier', $ret);
        $this->assertInstanceOf('stdClass', $ret->identifier);
        $this->assertObjectHasAttribute('type', $ret->identifier);
        $this->assertObjectHasAttribute('value', $ret->identifier);
        $this->assertObjectHasAttribute('challenges', $ret);
        $this->assertEquals('pending', $ret->status);
        $this->assertEquals('dns', $ret->identifier->type);
        $this->assertEquals('example.org', $ret->identifier->value);
        $this->assertCount(2, $ret->challenges);
    }

    public function testUpdate()
    {
        $response = (new GenericResponse())->withBody(new Body(<<<JSON
    {
    "status": "deactivated"
}
JSON
        ));

        $this->acmeMock->method('send')->willReturn($response);

        $ret = $this->authorization->update('https://example.com/acme/authz/1234', ['status' => 'deactivated']);

        $this->assertInstanceOf(\stdClass::class, $ret);
        $this->assertObjectHasAttribute('status', $ret);
        $this->assertEquals('deactivated', $ret->status);
    }

    public function testGetChallenge()
    {
        $auth = json_decode($this->getSample());

        $challenge = $this->authorization->getChallenge($auth);

        $this->assertInstanceOf('stdClass', $challenge);
        $this->assertEquals('http-01', $challenge->type);
        $this->assertEquals('https://example.com/acme/authz/1234/0', $challenge->url);
        $this->assertEquals('DGyRejmCefe7v4NfDGDKfA', $challenge->token);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetChallengeNotFound()
    {
        $auth = json_decode($this->getSample());

        $this->authorization->getChallenge($auth, 'xxx');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetChallengeInvalidAuth1()
    {
        $this->authorization->getChallenge([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetChallengeInvalidAuth2()
    {
        $auth = new \stdClass();
        $this->authorization->getChallenge($auth);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetChallengeInvalidAuth3()
    {
        $auth = new \stdClass();
        $auth->challenges = 'xx';
        $this->authorization->getChallenge($auth);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetChallengeInvalidAuth4()
    {
        $auth = new \stdClass();
        $chall = new \stdClass();
        $auth->challenges = [
            $chall
        ];
        $this->authorization->getChallenge($auth);
    }

    protected function getSample()
    {
        return <<<JSON
    {
  "status": "pending",
  "expires": "2018-03-03T14:09:00Z",

  "identifier": {
    "type": "dns",
    "value": "example.org"
  },

  "challenges": [
    {
      "type": "http-01",
      "url": "https://example.com/acme/authz/1234/0",
      "token": "DGyRejmCefe7v4NfDGDKfA"
    },
    {
      "type": "dns-01",
      "url": "https://example.com/acme/authz/1234/2",
      "token": "DGyRejmCefe7v4NfDGDKfA"
    }
  ],

  "wildcard": false
}
JSON;
    }
}
