<?php

namespace Acme2\Tests\Unit\KEY;

use Karl\Acme2\Key\RSA;
use PHPUnit\Framework\TestCase;


class RSATest extends TestCase
{

    /**
     * @var RSA
     */
    protected $key;

    public function setUp()
    {
        $this->key = new RSA();
    }

    public function testGenerate()
    {
        $keyResource = $this->key->generate();
        $this->assertInternalType('resource', $keyResource);
        $this->assertStringStartsWith('-----BEGIN PRIVATE KEY-----', $this->key->getPem());
        $this->assertStringEndsWith("-----END PRIVATE KEY-----\n", $this->key->getPem());
    }

    public function testSetPem()
    {
        $key = "-----BEGIN PRIVATE KEY-----\nDDD-----END PRIVATE KEY-----\n";
        $this->key->setPem($key);
        $this->assertEquals($key, $this->key->getPem());
    }

    public function testSetGetKid()
    {
        $kid = 'https://acme-staging-v02.api.letsencrypt.org/acme/acct/5994507';
        $this->key->setKid($kid);
        $this->assertEquals($kid, $this->key->getKid());
    }

    public function testBuildJWKThumbprint()
    {
        $probe = '2nrRnBzF7Wr3hiuYFQyniFJF+AioyDOQ1VFB36Kj96Q=';
        $key   = new \Acme2\Tests\Unit\Stubs\Key\RSA();
        $this->assertEquals(base64_decode($probe), $key->buildJWKThumbprint());
    }

    public function testSign()
    {
        $jwsProtected = [
            'nonce' => '12345678901234567890abcdef',
            'url'   => 'http://www.example.com'
        ];

        $payload  = [
            'foo' => 'bar',
            'num' => 1111
        ];
        $expected = array(
            'protected' => 'eyJub25jZSI6IjEyMzQ1Njc4OTAxMjM0NTY3ODkwYWJjZGVmIiwidXJsIjoiaHR0cDpcL1wvd3d3LmV4YW1wbGUuY29tIiwiYWxnIjoiUlMyNTYiLCJqd2siOnsia3R5IjoiUlNBIiwibiI6InNOYzl1bjZlRlhxbDh6V3EtVjdJWFJxWE9Ka2I0YjAxaXdpeVkyc2VtZHVEZTd5WC1YSXlWcldxYTFRTW1mRE8wdnpiV3JaSktIUjExXzRuMzB6QmNPOVg3QWh6TnQtdElWb3kxT0JVRUMwM291QVU5cmVoYTFQWkFvaWJfclZQY1p1U2paNWdEeHBQRXRNcm5LQkl5UzdBcjN3dnk3dkdRajVGODFXd2hwSDFQaDN4SUNKa1h4WG42U2dMZ050Y3VNQ1pnMWc4NTNsUWhIdVFXNzRnYWx1Mng0UDlOa19DQU5fOWxhQklqZkdWeGUzNlRhRlc4SmtDNDBRZjFMN25odDc1ZkMzNU5WenRjamtaREh3dk1IazNXTDBoZ2NlTXBPX0pDVGo1MzZPWmdvek5aQlFTakVjMU9PSXB5X3o5UFBVYmJlZlFtaHZCRHVNdHVmOE4wUSIsImUiOiJBUUFCIn19',
            'payload'   => 'eyJmb28iOiJiYXIiLCJudW0iOjExMTF9',
            'signature' => 'hMVWw1drV-OvTVubv6B2vUAwqyTOQQpTxG4m_dXgt-9n1N2EA4lkBc3GvTQNiWodNQi11sAz5Zd6Jkk3bJ4gsuWhKapGu6gwChzlJSaSF6mhDGZ3aQKX001v_-LNlclxYvslfIDtxTaRsnyarJSyzoWQM0cRX1_1O3SFgOP0UIl7m-fMybsDfEmWo0mZRg-kvjNHxcliVxcdD8OnvvuWkwj4jzabJGgKKPLrbXNoOhlP01OeY4YpTlCrUD977Z-NKv1gDCKwlPkNxDSF3XbBG7WETkQoHyCKAXgfpNH_0s8MQp0DjCXQUlJAWnAj_arQ3LUrW8r10Wg9-ffPqbVbPw',
        );
        $key      = new \Acme2\Tests\Unit\Stubs\Key\RSA();
        $this->assertEquals($expected, $key->sign($jwsProtected, $payload));
    }


    public function testSignWithKid()
    {
        $jwsProtected = [
            'nonce' => '12345678901234567890abcdef',
            'url'   => 'http://www.example.com'
        ];

        $payload  = [
            'foo' => 'bar',
            'num' => 1111
        ];
        $expected = array(
            'protected' => 'eyJub25jZSI6IjEyMzQ1Njc4OTAxMjM0NTY3ODkwYWJjZGVmIiwidXJsIjoiaHR0cDpcL1wvd3d3LmV4YW1wbGUuY29tIiwiYWxnIjoiUlMyNTYiLCJraWQiOiJodHRwczpcL1wvYWNtZS1zdGFnaW5nLXYwMi5hcGkubGV0c2VuY3J5cHQub3JnXC9hY21lXC9hY2N0XC81OTk0NTA3In0',
            'payload'   => 'eyJmb28iOiJiYXIiLCJudW0iOjExMTF9',
            'signature' => 'U8JOIBT2bAGcUxd8b1ACKvZAAsrCwf7pdVWMsXp4GDFLdvltOyj2OUNwiZ_7QhKMrdjZhl5y3glmipG7yV2kYyVy1grpQ6JcdxjUEFu6BEQrFiNX2VbjjkhvCkmPPszL7TWxWWWMKYOqp3fPN8XMhsNt8sUPnTmyjP5EXI5fQJNsXx1RZWot7g89HIKk_6vKlhr2SCvUx4UTvWX06GjAQoSGr6i4_FqjS-ZFEj9gmqYDT9KrKDEq3g2wkbsROlD08IhXAQwTVubtrqHfyRwfxl2F1aL5wW1VOYAgiV7iLe9CcceRIa21K7VKvBzvG4xZjHCdfF8HGfV8dj8P5psb4Q',
        );
        $key      = new \Acme2\Tests\Unit\Stubs\Key\RSA();
        $key->setKid('https://acme-staging-v02.api.letsencrypt.org/acme/acct/5994507');
        $this->assertEquals($expected, $key->sign($jwsProtected, $payload));
    }
}
