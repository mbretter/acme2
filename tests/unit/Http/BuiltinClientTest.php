<?php


// overwrite some internal functions in the namespace to get reproducable results
namespace Karl\Acme2\Http {

    function file_get_contents($url, $use_include_path = false, $context = null)
    {
        global $test_response_testbody, $test_stream_options;

        $test_stream_options = stream_context_get_options($context);

        return $test_response_testbody;
    }

    function get_headers($url, $format = 0, $context = null)
    {
        global $test_response_headers, $test_stream_options;

        $test_stream_options = stream_context_get_options($context);

        return $test_response_headers;
    }


}

namespace Acme2\Tests\Unit {

    use Karl\Acme2\Http\Body;
    use Karl\Acme2\Http\BuiltinClient;
    use Karl\Acme2\Http\Request;
    use Karl\Acme2\Http\Response;
    use Karl\Acme2\Http\Uri;
    use PHPUnit\Framework\MockObject\MockObject;
    use PHPUnit\Framework\TestCase;

    class BuiltinClientTest extends TestCase
    {
        /**
         * @var MockObject|BuiltinClient
         */
        protected $httpClient;

        public function setUp()
        {
            $this->httpClient = $this->createPartialMock(BuiltinClient::class, ['getResponseHeaders']);
        }

        public function testSendGet()
        {
            global $test_response_testbody;

            $test_response_testbody = '{foo:"bar"}';

            $this->httpClient->method('getResponseHeaders')->willReturn([
                'HTTP/1.0 201 OK',
                'Server: nginx',
                'Content-Length: 0',
                'Location: https://acme-staging-v02.api.letsencrypt.org/acme/acct/5994507',
                'Replay-Nonce: zU99KoSb3m1yRkqtbS7B0cU8SQbtG3789mADLBgCUpw',
                'Date: Mon, 04 Jun 2018 14:17:51 GMT',
            ]);
            $req      = new Request('GET', new Body(''), Uri::createFromString('http://www.example.com'));
            $response = $this->httpClient->send($req);

            $this->assertEquals(201, $response->getStatusCode());
            $this->assertEquals('zU99KoSb3m1yRkqtbS7B0cU8SQbtG3789mADLBgCUpw', $response->getHeaderLine('Replay-Nonce'));
            $this->assertEquals('https://acme-staging-v02.api.letsencrypt.org/acme/acct/5994507', $response->getHeaderLine('Location'));

            $this->assertEquals($test_response_testbody, (string)$response->getBody());
        }

        public function testSendGetWithHeaders()
        {
            global $test_stream_options;

            $this->httpClient->method('getResponseHeaders')->willReturn([
                'HTTP/1.0 204 OK',
                'Content-Length: 0'
            ]);
            $req = new Request('GET', new Body(''), Uri::createFromString('http://www.example.com'));
            $req = $req->withHeader('Content-Type', 'application/json');

            $response = $this->httpClient->send($req);

            $this->assertEquals(204, $response->getStatusCode());
            $this->assertEquals('Content-Type: application/json', $test_stream_options['http']['header']);
        }

        public function testSendPost()
        {
            global $test_response_testbody;

            $test_response_testbody = '{foo:"bar"}';

            $this->httpClient->method('getResponseHeaders')->willReturn([
                'HTTP/1.0 200 OKIDOK',
                'Server: nginx',
                'Content-Length: 11',
                'Location: https://acme-staging-v02.api.letsencrypt.org/acme/acct/5994507',
                'Replay-Nonce: zU99KoSb3m1yRkqtbS7B0cU8SQbtG3789mADLBgCUpw',
                'Date: Mon, 04 Jun 2018 14:17:51 GMT',
                'Connection: close'
            ]);
            $req      = new Request('POST', new Body($test_response_testbody), Uri::createFromString('http://www.example.com'));
            $response = $this->httpClient->send($req);

            $this->assertEquals(200, $response->getStatusCode());
            $this->assertEquals('OKIDOK', $response->getReasonPhrase());
            $this->assertEquals('zU99KoSb3m1yRkqtbS7B0cU8SQbtG3789mADLBgCUpw', $response->getHeaderLine('Replay-Nonce'));

            $this->assertEquals($test_response_testbody, (string)$response->getBody());
        }

        public function testSendHead()
        {
            global $test_response_headers;

            $test_response_headers = [
                'HTTP/1.0 204 No Content',
                'Server: nginx',
                'Replay-Nonce: sm15rg49ZBhgySTEQ3xJUnONdTDY2EoEatKxq-LyZd8',
                'X-Frame-Options: DENY',
                'Strict-Transport-Security: max-age=604800',
                'Expires: Mon, 04 Jun 2018 14:35:19 GMT',
                'Cache-Control: max-age=0, no-cache, no-store',
                'Pragma: no-cache',
                'Date: Mon, 04 Jun 2018 14:35:19 GMT',
                'Connection: close',
            ];

            $req      = new Request('HEAD', new Body(''), Uri::createFromString('http://www.example.com'));
            $response = $this->httpClient->send($req);

            $this->assertEquals(204, $response->getStatusCode());
            $this->assertEquals('No Content', $response->getReasonPhrase());
            $this->assertEquals('sm15rg49ZBhgySTEQ3xJUnONdTDY2EoEatKxq-LyZd8', $response->getHeaderLine('Replay-Nonce'));
        }

        public function testSendHeadFailed()
        {
            global $test_response_headers;

            $test_response_headers = false;

            $req      = new Request('HEAD', new Body(''), Uri::createFromString('http://www.example.com'));
            $response = $this->httpClient->send($req);

            $this->assertFalse($response);
        }

        /**
         * @expectedException \InvalidArgumentException
         */
        public function testSendMethodNotSupported()
        {
            $req = new Request('PUT', new Body(''), Uri::createFromString('http://www.example.com'));
            $this->httpClient->send($req);
        }

        public function testIsSuccessful()
        {
            $response = new Response([]);
            $this->assertTrue($this->httpClient->isSuccessful($response->withStatus(200)));
            $this->assertTrue($this->httpClient->isSuccessful($response->withStatus(299)));
            $this->assertFalse($this->httpClient->isSuccessful($response->withStatus(300)));
        }

        public function testIsClientError()
        {
            $response = new Response([]);
            $this->assertFalse($this->httpClient->isClientError($response->withStatus(200)));
            $this->assertTrue($this->httpClient->isClientError($response->withStatus(400)));
            $this->assertTrue($this->httpClient->isClientError($response->withStatus(499)));
            $this->assertFalse($this->httpClient->isClientError($response->withStatus(500)));
        }

        public function testIsOk()
        {
            $response = new Response([]);
            $this->assertFalse($this->httpClient->isOk($response->withStatus(201)));
            $this->assertTrue($this->httpClient->isOk($response->withStatus(200)));
            $this->assertFalse($this->httpClient->isOk($response->withStatus(500)));
        }

        public function testIsNotFound()
        {
            $response = new Response([]);
            $this->assertFalse($this->httpClient->isNotFound($response->withStatus(201)));
            $this->assertTrue($this->httpClient->isNotFound($response->withStatus(404)));
            $this->assertFalse($this->httpClient->isNotFound($response->withStatus(400)));
            $this->assertFalse($this->httpClient->isNotFound($response->withStatus(500)));
        }

        /*
         * test some protected method code paths which are never reached
         */

        public function testParseHeaders()
        {
            $client = new BuiltinClient2();

            $expected = [
                'Accept'        =>
                    array(
                        0 => 'application/json',
                        1 => 'text/xml',
                    ),
                'Server'        =>
                    array(
                        0 => 'nginx',
                    ),
                'Cache-Control' =>
                    array(
                        0 => 'max-age=0, no-cache, no-store',
                    ),
            ];

            $this->assertEquals($expected, $client->parseHeaders([
                'HTTP/1.0 204 No Content',
                'Accept: application/json',
                'Accept: text/xml',
                'Server: nginx',
                'Cache-Control: max-age=0, no-cache, no-store',
            ]));
        }

        public function testParseStatusLine()
        {
            $client = new BuiltinClient2();

            $expected = (object)[
                'statusCode'   => 200,
                'reasonPhrase' => ''
            ];

            $this->assertEquals($expected, $client->parseStatusLine(null));
        }
    }

    class BuiltinClient2 extends BuiltinClient
    {
        public function parseHeaders($headers)
        {
            return parent::parseHeaders($headers);
        }

        public function parseStatusLine($headers)
        {
            return parent::parseStatusLine($headers);
        }

    }
}