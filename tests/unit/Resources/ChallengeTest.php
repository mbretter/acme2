<?php

namespace Acme2\Tests\Unit\Resources;

use Acme2\Tests\Unit\Stubs\Http\GenericResponse;
use Karl\Acme2\Acme;
use Karl\Acme2\Http\Body;
use Karl\Acme2\Resources\Challenge;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class ChallengeTest extends TestCase
{
    /**
     * @var MockObject|Acme
     */
    protected $acmeMock;

    /**
     * @var Challenge
     */
    protected $challenge;

    public function setUp()
    {
        $this->acmeMock = $this->createMock(Acme::class);

        $this->challenge = new Challenge($this->acmeMock);
    }

    public function testGet()
    {
        $response = (new GenericResponse())->withBody(new Body(<<<JSON
{
  "type": "http-01",
  "status": "pending",
  "url": "https://acme-staging-v02.api.letsencrypt.org/acme/challenge/Y7_LW1p0km3hdl1DKAzlLAJff2aJpsmnMeBcPg-FkC8/127868480",
  "token": "UyuaOa5SGfr9IslZWP8e0ygu3X62an15Uz66s1YQVVM"
}
JSON
        ));

        $this->acmeMock->method('get')->willReturn($response);

        $ret = $this->challenge->get('https://acme-staging-v02.api.letsencrypt.org/acme/challenge/Y7_LW1p0km3hdl1DKAzlLAJff2aJpsmnMeBcPg-FkC8/127868480');

        $this->assertEquals((object)array(
            'type'   => 'http-01',
            'status' => 'pending',
            'url'    => 'https://acme-staging-v02.api.letsencrypt.org/acme/challenge/Y7_LW1p0km3hdl1DKAzlLAJff2aJpsmnMeBcPg-FkC8/127868480',
            'token'  => 'UyuaOa5SGfr9IslZWP8e0ygu3X62an15Uz66s1YQVVM'
        ), $ret);
    }

    public function testValidate()
    {
        $response = (new GenericResponse())->withBody(new Body(<<<JSON
{"type":"dns-01","status":"valid","url":"https:\/\/acme-staging-v02.api.letsencrypt.org\/acme\/challenge\/Y7_LW1p0km3hdl1DKAzlLAJff2aJpsmnMeBcPg-FkC8\/127868481","token":"n-Z6K4lqBzoM51C00PmGP20PUygfHsbOdF0h2TPBiG4","validationRecord":[{"hostname":"acmetest6.example.com"}]}
JSON
        ));

        $this->acmeMock->method('send')->willReturn($response);

        $ret = $this->challenge->validate('https://acme-staging-v02.api.letsencrypt.org/acme/challenge/Y7_LW1p0km3hdl1DKAzlLAJff2aJpsmnMeBcPg-FkC8/127868480');

        $this->assertEquals((object)array(
            'type'             => 'dns-01',
            'status'           => 'valid',
            'url'              => 'https://acme-staging-v02.api.letsencrypt.org/acme/challenge/Y7_LW1p0km3hdl1DKAzlLAJff2aJpsmnMeBcPg-FkC8/127868481',
            'token'            => 'n-Z6K4lqBzoM51C00PmGP20PUygfHsbOdF0h2TPBiG4',
            'validationRecord' => [
                (object)[
                    'hostname' => 'acmetest6.example.com'
                ]
            ]
        ), $ret);
    }

    public function testBuildKeyAuthorization()
    {
        $expected  = 'UyuaOa5SGfr9IslZWP8e0ygu3X62an15Uz66s1YQVVM.2123213abcdef';
        $challenge = (object)array(
            'type'   => 'http-01',
            'status' => 'pending',
            'url'    => 'https://acme-staging-v02.api.letsencrypt.org/acme/challenge/Y7_LW1p0km3hdl1DKAzlLAJff2aJpsmnMeBcPg-FkC8/127868480',
            'token'  => 'UyuaOa5SGfr9IslZWP8e0ygu3X62an15Uz66s1YQVVM'
        );
        $this->acmeMock->method('getJWKThumbprint')->willReturn('2123213abcdef');

        $this->assertEquals($expected, $this->challenge->buildKeyAuthorization($challenge));
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBuildKeyAuthorizationInvalidArgs1()
    {
        $this->challenge->buildKeyAuthorization('');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testBuildKeyAuthorizationInvalidArgs2()
    {
        $this->challenge->buildKeyAuthorization((object)[]);
    }
}
