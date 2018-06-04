<?php

namespace Acme2\Tests\Unit\Resources;

use Acme2\Tests\Unit\Stubs\Http\ClientErrorResponse;
use Acme2\Tests\Unit\Stubs\Http\GenericResponse;
use Karl\Acme2\Acme;
use Karl\Acme2\Exception\RequestException;
use Karl\Acme2\Http\Body;
use Karl\Acme2\Key\RSA;
use Karl\Acme2\Resources\Certificate;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;


class CertificateTest extends TestCase
{
    /**
     * @var MockObject|Acme
     */
    protected $acmeMock;

    /**
     * @var Certificate
     */
    protected $certificate;

    public function setUp()
    {
        $this->acmeMock = $this->createMock(Acme::class);

        $this->certificate = new Certificate($this->acmeMock);
    }

    public function testDownload()
    {
        $orderData = json_decode($this->getOrderDataSample());
        $response  = (new GenericResponse())->withBody(new Body($this->getSampleChain()));

        $this->acmeMock->method('get')->willReturn($response);

        $cert = $this->certificate->download($orderData);

        $this->assertEquals($this->getSampleChain(), $cert);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testDownloadNoAcme()
    {
        $this->certificate->setAcme(null);
        $orderData = json_decode($this->getOrderDataSample());
        $this->certificate->download($orderData);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDownloadInvalidOrderData1()
    {
        $this->certificate->download([]);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDownloadInvalidOrderData2()
    {
        $orderData              = new \stdClass();
        $orderData->certificate = '';
        $this->certificate->download($orderData);
    }

    public function testDownloadEmptyCertificate()
    {
        $orderData              = new \stdClass();
        $orderData->certificate = '';
        $orderData->status      = 'valid';
        $this->assertNull($this->certificate->download($orderData));
    }

    public function testDownloadOrderStatusInvalid()
    {
        $orderData              = new \stdClass();
        $orderData->certificate = 'xxx';
        $orderData->status      = 'foo';
        $this->assertNull($this->certificate->download($orderData));
    }

    public function testRevoke()
    {
        $response = new GenericResponse();

        $this->acmeMock->method('send')->willReturn($response);

        $this->assertTrue($this->certificate->revoke($this->getSample()));
    }

    /**
     * @expectedException \Karl\Acme2\Exception\RequestException
     */
    public function testRevokeFailed()
    {
        $response = (new ClientErrorResponse())->withStatus(403);

        $this->acmeMock->method('send')->willThrowException(new RequestException($response));

        $this->assertFalse($this->certificate->revoke($this->getSample()));
    }

    public function testNewCsr()
    {
        $key = new RSA();
        $csr = $this->certificate->newCsr(['example.com'], $key, ['countryName' => 'AT']);
        $this->assertInstanceOf('stdClass', $csr);
        $this->assertObjectHasAttribute('csr', $csr);
        $this->assertObjectHasAttribute('key', $csr);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testNewCsrNoCountryName()
    {
        $key = new RSA();
        $this->certificate->newCsr(['example.com'], $key, ['xxx' => 'AT']);
    }

    public function testReadMetadata()
    {
        $data = $this->certificate->readMetadata($this->getSample());

        $expected = (object)[
            'name'             => '/CN=acmetest6.bretterklieber.com',
            'subject'          => ['CN' => 'acmetest6.bretterklieber.com'],
            'hash'             => '00ff4cda',
            'issuer'           => ['CN' => 'Fake LE Intermediate X1'],
            'version'          => 2,
            'serialNumber'     => '0xFA0D8A9B2C318A12E996577478CE00778578',
            'serialNumberHex'  => 'FA0D8A9B2C318A12E996577478CE00778578',
            'validFrom'        =>
                \DateTime::__set_state(array(
                    'date'          => '2018-05-16 13:40:20.000000',
                    'timezone_type' => 1,
                    'timezone'      => '+00:00',
                )),
            'validTo'          =>
                \DateTime::__set_state(array(
                    'date'          => '2018-08-14 13:40:20.000000',
                    'timezone_type' => 1,
                    'timezone'      => '+00:00',
                )),
            'validFrom_time_t' => 1526478020,
            'validTo_time_t'   => 1534254020,
            'signatureTypeSN'  => 'RSA-SHA256',
            'signatureTypeLN'  => 'sha256WithRSAEncryption',
            'signatureTypeNID' => 668,
            'purposes'         => [
                1 => [true, false, 'sslclient'],
                2 => [true, false, 'sslserver'],
                3 => [true, false, 'nssslserver'],
                4 => [false, false, 'smimesign'],
                5 => [false, false, 'smimeencrypt'],
                6 => [false, false, 'crlsign'],
                7 => [true, true, 'any'],
                8 => [true, false, 'ocsphelper'],
                9 => [false, false, 'timestampsign',],
            ],
            'extensions'       =>
                array(
                    'keyUsage'               => 'Digital Signature, Key Encipherment',
                    'extendedKeyUsage'       => 'TLS Web Server Authentication, TLS Web Client Authentication',
                    'basicConstraints'       => 'CA:FALSE',
                    'subjectKeyIdentifier'   => '1A:99:AB:CC:7A:F4:E9:DB:E5:16:61:E1:FE:69:28:94:61:4F:55:20',
                    'authorityKeyIdentifier' => 'keyid:C0:CC:03:46:B9:58:20:CC:5C:72:70:F3:E1:2E:CB:20:A6:F5:68:3A
',
                    'authorityInfoAccess'    => 'OCSP - URI:http://ocsp.stg-int-x1.letsencrypt.org
CA Issuers - URI:http://cert.stg-int-x1.letsencrypt.org/
',
                    'subjectAltName'         => 'DNS:acmetest6.bretterklieber.com',
                ),
            'subjects'         => ['acmetest6.bretterklieber.com']
        ];


        $this->assertEquals($expected->name, $data->name);
        $this->assertEquals($expected->subject, $data->subject);
        $this->assertEquals($expected->subjects, $data->subjects);
        $this->assertEquals($expected->hash, $data->hash);
        $this->assertEquals($expected->issuer, $data->issuer);
        $this->assertEquals($expected->extensions['subjectAltName'], $data->extensions['subjectAltName']);
        $this->assertEquals($expected->validFrom, $data->validFrom);
        $this->assertEquals($expected->validTo, $data->validTo);

    }

    public function testReadMetadataWithAltNames()
    {
        $data = $this->certificate->readMetadata($this->getSampleWithAltNames());

        $this->assertInstanceOf('stdClass', $data);
        $this->assertObjectHasAttribute('subjects', $data);
        $this->assertInternalType('array', $data->subjects);
        $this->assertCount(2, $data->subjects);
        $this->assertEquals('*.bretterklieber.com', $data->subjects[0]);
        $this->assertEquals('bretterklieber.com', $data->subjects[1]);
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testReadMetadataFailed()
    {
        $this->certificate->readMetadata('XXX');
    }

    public function testSplitChain()
    {
        $chain = $this->certificate->splitChain($this->getSampleChain());

        $this->assertInternalType('array', $chain);
        $this->assertCount(2, $chain);
        $this->assertEquals(<<<EOT
-----BEGIN CERTIFICATE-----
MIIGBTCCBO2gAwIBAgITAPoNipssMYoS6ZZXdHjOAHeFeDANBgkqhkiG9w0BAQsF
ADAiMSAwHgYDVQQDDBdGYWtlIExFIEludGVybWVkaWF0ZSBYMTAeFw0xODA1MTYx
MzQwMjBaFw0xODA4MTQxMzQwMjBaMCcxJTAjBgNVBAMTHGFjbWV0ZXN0Ni5icmV0
dGVya2xpZWJlci5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDS
fosy7MbWRV8U3N2KHvrCf7XmmhHvkaSmph1Z/yy9Vdx7HRBTGRBOpx42bqqzYhIS
HTtCcNuUF43TsfPGixZqndtg5zA4VdQtr3q6Kx8GXvIFVCZckWSwZh2JVtkUEUng
43DYp9cuSSOa0dKhe4R0V4goKA5LPFg367MFS9XpNin2sG1G23MnpMwUvzW6vurO
ZGHLpukDiW/8HmdA95fBVzpgXT6cWJCFcPQ064TgrUGyGg/NL/g9em5UVk320p0n
k/HJvAh4mX7RK70K29EehmiyC6UxLorvJWmLANbmn2SKNwg6Pgt1Lv7dKycB5zab
Uh/MSnp7/ZTOTADT+jJRAgMBAAGjggMtMIIDKTAOBgNVHQ8BAf8EBAMCBaAwHQYD
VR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUFBwMCMAwGA1UdEwEB/wQCMAAwHQYDVR0O
BBYEFBqZq8x69Onb5RZh4f5pKJRhT1UgMB8GA1UdIwQYMBaAFMDMA0a5WCDMXHJw
8+EuyyCm9Wg6MHcGCCsGAQUFBwEBBGswaTAyBggrBgEFBQcwAYYmaHR0cDovL29j
c3Auc3RnLWludC14MS5sZXRzZW5jcnlwdC5vcmcwMwYIKwYBBQUHMAKGJ2h0dHA6
Ly9jZXJ0LnN0Zy1pbnQteDEubGV0c2VuY3J5cHQub3JnLzAnBgNVHREEIDAeghxh
Y21ldGVzdDYuYnJldHRlcmtsaWViZXIuY29tMIH+BgNVHSAEgfYwgfMwCAYGZ4EM
AQIBMIHmBgsrBgEEAYLfEwEBATCB1jAmBggrBgEFBQcCARYaaHR0cDovL2Nwcy5s
ZXRzZW5jcnlwdC5vcmcwgasGCCsGAQUFBwICMIGeDIGbVGhpcyBDZXJ0aWZpY2F0
ZSBtYXkgb25seSBiZSByZWxpZWQgdXBvbiBieSBSZWx5aW5nIFBhcnRpZXMgYW5k
IG9ubHkgaW4gYWNjb3JkYW5jZSB3aXRoIHRoZSBDZXJ0aWZpY2F0ZSBQb2xpY3kg
Zm91bmQgYXQgaHR0cHM6Ly9sZXRzZW5jcnlwdC5vcmcvcmVwb3NpdG9yeS8wggEF
BgorBgEEAdZ5AgQCBIH2BIHzAPEAdwCwzIPlpfl9a698CcwoSQSHKsfoixMsY1C3
xv0m4WxsdwAAAWNpZQ9zAAAEAwBIMEYCIQCF0rS14S355NudGVWD1QpytYlirG+k
N7TQEqholWqOUgIhAP2u2v2H68kIJ7tZGvmmxhmFRlmGt6bYvOvccNaXicP4AHYA
3Zk0/KXnJIDJVmh9gTSZCEmySfe1adjHvKs/XMHzbmQAAAFjaWUW+gAABAMARzBF
AiAMkH0Pkj/Wd8PnyE+NdgW0Cj4WWmZE9jsKwrg+JdE2vgIhAPqWzF9MOhWpvzms
PjYjFoQLkSiyOg7nq8MVvq+1qJQsMA0GCSqGSIb3DQEBCwUAA4IBAQBgV2c0+ptp
LaTcsbrbFGY5BcFCQdoWBr6NVzSsL8DXu6uQScycAvzU/6K29J8wpsXdcTkJKt4E
q/7dGSZQ9tyVY8aPTiMS+DmUEOQ/UZ/LGOoVND1dB1PtvvcGxQzO7IGSt6AbzUcD
NJP2NpeSmy1K5W6FYw/TntrGc+yCnCDqYqg8+CsSnv0VnUsGpPT1bQ2i4c8Xi5FR
4VAl9mn4rTou+1w59A9SNeV5ODcyZMp25GoyReeTGq4EW8R1XoTLHPyiyuDjTXmc
MemCuGw9te4aWMfPlkaqRKfxaz9MZ8tF4pUp1d2BYBAxyWBVRWPn9AFC6uX7hLVC
AqH/omQQrCfi
-----END CERTIFICATE-----

EOT
            , $chain[0]);
        $this->assertEquals(<<<EOT
-----BEGIN CERTIFICATE-----
MIIEqzCCApOgAwIBAgIRAIvhKg5ZRO08VGQx8JdhT+UwDQYJKoZIhvcNAQELBQAw
GjEYMBYGA1UEAwwPRmFrZSBMRSBSb290IFgxMB4XDTE2MDUyMzIyMDc1OVoXDTM2
MDUyMzIyMDc1OVowIjEgMB4GA1UEAwwXRmFrZSBMRSBJbnRlcm1lZGlhdGUgWDEw
ggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDtWKySDn7rWZc5ggjz3ZB0
8jO4xti3uzINfD5sQ7Lj7hzetUT+wQob+iXSZkhnvx+IvdbXF5/yt8aWPpUKnPym
oLxsYiI5gQBLxNDzIec0OIaflWqAr29m7J8+NNtApEN8nZFnf3bhehZW7AxmS1m0
ZnSsdHw0Fw+bgixPg2MQ9k9oefFeqa+7Kqdlz5bbrUYV2volxhDFtnI4Mh8BiWCN
xDH1Hizq+GKCcHsinDZWurCqder/afJBnQs+SBSL6MVApHt+d35zjBD92fO2Je56
dhMfzCgOKXeJ340WhW3TjD1zqLZXeaCyUNRnfOmWZV8nEhtHOFbUCU7r/KkjMZO9
AgMBAAGjgeMwgeAwDgYDVR0PAQH/BAQDAgGGMBIGA1UdEwEB/wQIMAYBAf8CAQAw
HQYDVR0OBBYEFMDMA0a5WCDMXHJw8+EuyyCm9Wg6MHoGCCsGAQUFBwEBBG4wbDA0
BggrBgEFBQcwAYYoaHR0cDovL29jc3Auc3RnLXJvb3QteDEubGV0c2VuY3J5cHQu
b3JnLzA0BggrBgEFBQcwAoYoaHR0cDovL2NlcnQuc3RnLXJvb3QteDEubGV0c2Vu
Y3J5cHQub3JnLzAfBgNVHSMEGDAWgBTBJnSkikSg5vogKNhcI5pFiBh54DANBgkq
hkiG9w0BAQsFAAOCAgEABYSu4Il+fI0MYU42OTmEj+1HqQ5DvyAeyCA6sGuZdwjF
UGeVOv3NnLyfofuUOjEbY5irFCDtnv+0ckukUZN9lz4Q2YjWGUpW4TTu3ieTsaC9
AFvCSgNHJyWSVtWvB5XDxsqawl1KzHzzwr132bF2rtGtazSqVqK9E07sGHMCf+zp
DQVDVVGtqZPHwX3KqUtefE621b8RI6VCl4oD30Olf8pjuzG4JKBFRFclzLRjo/h7
IkkfjZ8wDa7faOjVXx6n+eUQ29cIMCzr8/rNWHS9pYGGQKJiY2xmVC9h12H99Xyf
zWE9vb5zKP3MVG6neX1hSdo7PEAb9fqRhHkqVsqUvJlIRmvXvVKTwNCP3eCjRCCI
PTAvjV+4ni786iXwwFYNz8l3PmPLCyQXWGohnJ8iBm+5nk7O2ynaPVW0U2W+pt2w
SVuvdDM5zGv2f9ltNWUiYZHJ1mmO97jSY/6YfdOUH66iRtQtDkHBRdkNBsMbD+Em
2TgBldtHNSJBfB3pm9FblgOcJ0FSWcUDWJ7vO0+NTXlgrRofRT6pVywzxVo6dND0
WzYlTWeUVsO40xJqhgUQRER9YLOLxJ0O6C8i0xFxAMKOtSdodMB3RIwt7RFQ0uyt
n5Z5MqkYhlMI3J1tPRTp1nEt9fyGspBOO05gi148Qasp+3N+svqKomoQglNoAxU=
-----END CERTIFICATE-----

EOT
            , $chain[1]);
    }

    public function testValidateSubject()
    {
        $metaData = $this->certificate->readMetadata($this->getSampleWithAltNames());

        $this->assertFalse($this->certificate->validateSubject('foo.exampl.com', $metaData));
        $this->assertTrue($this->certificate->validateSubject('bretterklieber.com', $metaData));
        $this->assertTrue($this->certificate->validateSubject('sss.bretterklieber.com', $metaData));
        $this->assertFalse($this->certificate->validateSubject('example.com', $metaData));
        $this->assertFalse($this->certificate->validateSubject('klieber.com', $metaData));
    }

    public function testValidateSubjectWithInvalidMetadata1()
    {
        $this->assertFalse($this->certificate->validateSubject('foo', 'xxx'));
    }

    public function testValidateSubjectWithInvalidMetadata2()
    {
        $this->assertFalse($this->certificate->validateSubject('foo', ['subjects' => []]));
    }

    public function testValidateSubjectWithInvalidMetadata3()
    {
        $this->assertFalse($this->certificate->validateSubject('foo', (object)['ssss' => []]));
    }

    protected function getOrderDataSample()
    {
        return <<<JSON
{
  "status": "valid",
  "expires": "2015-03-01T14:09:00Z",

  "identifiers": [
    { "type": "dns", "value": "example.com" },
    { "type": "dns", "value": "www.example.com" }
  ],

  "notBefore": "2016-01-01T00:00:00Z",
  "notAfter": "2016-01-08T00:00:00Z",

  "authorizations": [
    "https://example.com/acme/authz/1234",
    "https://example.com/acme/authz/2345"
  ],

  "finalize": "https://example.com/acme/acct/1/order/1/finalize",

  "certificate": "https://example.com/acme/cert/1234"
}
JSON;
    }

    protected function getSampleChain()
    {
        return <<<EOT

-----BEGIN CERTIFICATE-----
MIIGBTCCBO2gAwIBAgITAPoNipssMYoS6ZZXdHjOAHeFeDANBgkqhkiG9w0BAQsF
ADAiMSAwHgYDVQQDDBdGYWtlIExFIEludGVybWVkaWF0ZSBYMTAeFw0xODA1MTYx
MzQwMjBaFw0xODA4MTQxMzQwMjBaMCcxJTAjBgNVBAMTHGFjbWV0ZXN0Ni5icmV0
dGVya2xpZWJlci5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDS
fosy7MbWRV8U3N2KHvrCf7XmmhHvkaSmph1Z/yy9Vdx7HRBTGRBOpx42bqqzYhIS
HTtCcNuUF43TsfPGixZqndtg5zA4VdQtr3q6Kx8GXvIFVCZckWSwZh2JVtkUEUng
43DYp9cuSSOa0dKhe4R0V4goKA5LPFg367MFS9XpNin2sG1G23MnpMwUvzW6vurO
ZGHLpukDiW/8HmdA95fBVzpgXT6cWJCFcPQ064TgrUGyGg/NL/g9em5UVk320p0n
k/HJvAh4mX7RK70K29EehmiyC6UxLorvJWmLANbmn2SKNwg6Pgt1Lv7dKycB5zab
Uh/MSnp7/ZTOTADT+jJRAgMBAAGjggMtMIIDKTAOBgNVHQ8BAf8EBAMCBaAwHQYD
VR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUFBwMCMAwGA1UdEwEB/wQCMAAwHQYDVR0O
BBYEFBqZq8x69Onb5RZh4f5pKJRhT1UgMB8GA1UdIwQYMBaAFMDMA0a5WCDMXHJw
8+EuyyCm9Wg6MHcGCCsGAQUFBwEBBGswaTAyBggrBgEFBQcwAYYmaHR0cDovL29j
c3Auc3RnLWludC14MS5sZXRzZW5jcnlwdC5vcmcwMwYIKwYBBQUHMAKGJ2h0dHA6
Ly9jZXJ0LnN0Zy1pbnQteDEubGV0c2VuY3J5cHQub3JnLzAnBgNVHREEIDAeghxh
Y21ldGVzdDYuYnJldHRlcmtsaWViZXIuY29tMIH+BgNVHSAEgfYwgfMwCAYGZ4EM
AQIBMIHmBgsrBgEEAYLfEwEBATCB1jAmBggrBgEFBQcCARYaaHR0cDovL2Nwcy5s
ZXRzZW5jcnlwdC5vcmcwgasGCCsGAQUFBwICMIGeDIGbVGhpcyBDZXJ0aWZpY2F0
ZSBtYXkgb25seSBiZSByZWxpZWQgdXBvbiBieSBSZWx5aW5nIFBhcnRpZXMgYW5k
IG9ubHkgaW4gYWNjb3JkYW5jZSB3aXRoIHRoZSBDZXJ0aWZpY2F0ZSBQb2xpY3kg
Zm91bmQgYXQgaHR0cHM6Ly9sZXRzZW5jcnlwdC5vcmcvcmVwb3NpdG9yeS8wggEF
BgorBgEEAdZ5AgQCBIH2BIHzAPEAdwCwzIPlpfl9a698CcwoSQSHKsfoixMsY1C3
xv0m4WxsdwAAAWNpZQ9zAAAEAwBIMEYCIQCF0rS14S355NudGVWD1QpytYlirG+k
N7TQEqholWqOUgIhAP2u2v2H68kIJ7tZGvmmxhmFRlmGt6bYvOvccNaXicP4AHYA
3Zk0/KXnJIDJVmh9gTSZCEmySfe1adjHvKs/XMHzbmQAAAFjaWUW+gAABAMARzBF
AiAMkH0Pkj/Wd8PnyE+NdgW0Cj4WWmZE9jsKwrg+JdE2vgIhAPqWzF9MOhWpvzms
PjYjFoQLkSiyOg7nq8MVvq+1qJQsMA0GCSqGSIb3DQEBCwUAA4IBAQBgV2c0+ptp
LaTcsbrbFGY5BcFCQdoWBr6NVzSsL8DXu6uQScycAvzU/6K29J8wpsXdcTkJKt4E
q/7dGSZQ9tyVY8aPTiMS+DmUEOQ/UZ/LGOoVND1dB1PtvvcGxQzO7IGSt6AbzUcD
NJP2NpeSmy1K5W6FYw/TntrGc+yCnCDqYqg8+CsSnv0VnUsGpPT1bQ2i4c8Xi5FR
4VAl9mn4rTou+1w59A9SNeV5ODcyZMp25GoyReeTGq4EW8R1XoTLHPyiyuDjTXmc
MemCuGw9te4aWMfPlkaqRKfxaz9MZ8tF4pUp1d2BYBAxyWBVRWPn9AFC6uX7hLVC
AqH/omQQrCfi
-----END CERTIFICATE-----

-----BEGIN CERTIFICATE-----
MIIEqzCCApOgAwIBAgIRAIvhKg5ZRO08VGQx8JdhT+UwDQYJKoZIhvcNAQELBQAw
GjEYMBYGA1UEAwwPRmFrZSBMRSBSb290IFgxMB4XDTE2MDUyMzIyMDc1OVoXDTM2
MDUyMzIyMDc1OVowIjEgMB4GA1UEAwwXRmFrZSBMRSBJbnRlcm1lZGlhdGUgWDEw
ggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDtWKySDn7rWZc5ggjz3ZB0
8jO4xti3uzINfD5sQ7Lj7hzetUT+wQob+iXSZkhnvx+IvdbXF5/yt8aWPpUKnPym
oLxsYiI5gQBLxNDzIec0OIaflWqAr29m7J8+NNtApEN8nZFnf3bhehZW7AxmS1m0
ZnSsdHw0Fw+bgixPg2MQ9k9oefFeqa+7Kqdlz5bbrUYV2volxhDFtnI4Mh8BiWCN
xDH1Hizq+GKCcHsinDZWurCqder/afJBnQs+SBSL6MVApHt+d35zjBD92fO2Je56
dhMfzCgOKXeJ340WhW3TjD1zqLZXeaCyUNRnfOmWZV8nEhtHOFbUCU7r/KkjMZO9
AgMBAAGjgeMwgeAwDgYDVR0PAQH/BAQDAgGGMBIGA1UdEwEB/wQIMAYBAf8CAQAw
HQYDVR0OBBYEFMDMA0a5WCDMXHJw8+EuyyCm9Wg6MHoGCCsGAQUFBwEBBG4wbDA0
BggrBgEFBQcwAYYoaHR0cDovL29jc3Auc3RnLXJvb3QteDEubGV0c2VuY3J5cHQu
b3JnLzA0BggrBgEFBQcwAoYoaHR0cDovL2NlcnQuc3RnLXJvb3QteDEubGV0c2Vu
Y3J5cHQub3JnLzAfBgNVHSMEGDAWgBTBJnSkikSg5vogKNhcI5pFiBh54DANBgkq
hkiG9w0BAQsFAAOCAgEABYSu4Il+fI0MYU42OTmEj+1HqQ5DvyAeyCA6sGuZdwjF
UGeVOv3NnLyfofuUOjEbY5irFCDtnv+0ckukUZN9lz4Q2YjWGUpW4TTu3ieTsaC9
AFvCSgNHJyWSVtWvB5XDxsqawl1KzHzzwr132bF2rtGtazSqVqK9E07sGHMCf+zp
DQVDVVGtqZPHwX3KqUtefE621b8RI6VCl4oD30Olf8pjuzG4JKBFRFclzLRjo/h7
IkkfjZ8wDa7faOjVXx6n+eUQ29cIMCzr8/rNWHS9pYGGQKJiY2xmVC9h12H99Xyf
zWE9vb5zKP3MVG6neX1hSdo7PEAb9fqRhHkqVsqUvJlIRmvXvVKTwNCP3eCjRCCI
PTAvjV+4ni786iXwwFYNz8l3PmPLCyQXWGohnJ8iBm+5nk7O2ynaPVW0U2W+pt2w
SVuvdDM5zGv2f9ltNWUiYZHJ1mmO97jSY/6YfdOUH66iRtQtDkHBRdkNBsMbD+Em
2TgBldtHNSJBfB3pm9FblgOcJ0FSWcUDWJ7vO0+NTXlgrRofRT6pVywzxVo6dND0
WzYlTWeUVsO40xJqhgUQRER9YLOLxJ0O6C8i0xFxAMKOtSdodMB3RIwt7RFQ0uyt
n5Z5MqkYhlMI3J1tPRTp1nEt9fyGspBOO05gi148Qasp+3N+svqKomoQglNoAxU=
-----END CERTIFICATE-----
EOT;
    }


    protected function getSample()
    {
        return <<<EOT
-----BEGIN CERTIFICATE-----
MIIGBTCCBO2gAwIBAgITAPoNipssMYoS6ZZXdHjOAHeFeDANBgkqhkiG9w0BAQsF
ADAiMSAwHgYDVQQDDBdGYWtlIExFIEludGVybWVkaWF0ZSBYMTAeFw0xODA1MTYx
MzQwMjBaFw0xODA4MTQxMzQwMjBaMCcxJTAjBgNVBAMTHGFjbWV0ZXN0Ni5icmV0
dGVya2xpZWJlci5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIBAQDS
fosy7MbWRV8U3N2KHvrCf7XmmhHvkaSmph1Z/yy9Vdx7HRBTGRBOpx42bqqzYhIS
HTtCcNuUF43TsfPGixZqndtg5zA4VdQtr3q6Kx8GXvIFVCZckWSwZh2JVtkUEUng
43DYp9cuSSOa0dKhe4R0V4goKA5LPFg367MFS9XpNin2sG1G23MnpMwUvzW6vurO
ZGHLpukDiW/8HmdA95fBVzpgXT6cWJCFcPQ064TgrUGyGg/NL/g9em5UVk320p0n
k/HJvAh4mX7RK70K29EehmiyC6UxLorvJWmLANbmn2SKNwg6Pgt1Lv7dKycB5zab
Uh/MSnp7/ZTOTADT+jJRAgMBAAGjggMtMIIDKTAOBgNVHQ8BAf8EBAMCBaAwHQYD
VR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUFBwMCMAwGA1UdEwEB/wQCMAAwHQYDVR0O
BBYEFBqZq8x69Onb5RZh4f5pKJRhT1UgMB8GA1UdIwQYMBaAFMDMA0a5WCDMXHJw
8+EuyyCm9Wg6MHcGCCsGAQUFBwEBBGswaTAyBggrBgEFBQcwAYYmaHR0cDovL29j
c3Auc3RnLWludC14MS5sZXRzZW5jcnlwdC5vcmcwMwYIKwYBBQUHMAKGJ2h0dHA6
Ly9jZXJ0LnN0Zy1pbnQteDEubGV0c2VuY3J5cHQub3JnLzAnBgNVHREEIDAeghxh
Y21ldGVzdDYuYnJldHRlcmtsaWViZXIuY29tMIH+BgNVHSAEgfYwgfMwCAYGZ4EM
AQIBMIHmBgsrBgEEAYLfEwEBATCB1jAmBggrBgEFBQcCARYaaHR0cDovL2Nwcy5s
ZXRzZW5jcnlwdC5vcmcwgasGCCsGAQUFBwICMIGeDIGbVGhpcyBDZXJ0aWZpY2F0
ZSBtYXkgb25seSBiZSByZWxpZWQgdXBvbiBieSBSZWx5aW5nIFBhcnRpZXMgYW5k
IG9ubHkgaW4gYWNjb3JkYW5jZSB3aXRoIHRoZSBDZXJ0aWZpY2F0ZSBQb2xpY3kg
Zm91bmQgYXQgaHR0cHM6Ly9sZXRzZW5jcnlwdC5vcmcvcmVwb3NpdG9yeS8wggEF
BgorBgEEAdZ5AgQCBIH2BIHzAPEAdwCwzIPlpfl9a698CcwoSQSHKsfoixMsY1C3
xv0m4WxsdwAAAWNpZQ9zAAAEAwBIMEYCIQCF0rS14S355NudGVWD1QpytYlirG+k
N7TQEqholWqOUgIhAP2u2v2H68kIJ7tZGvmmxhmFRlmGt6bYvOvccNaXicP4AHYA
3Zk0/KXnJIDJVmh9gTSZCEmySfe1adjHvKs/XMHzbmQAAAFjaWUW+gAABAMARzBF
AiAMkH0Pkj/Wd8PnyE+NdgW0Cj4WWmZE9jsKwrg+JdE2vgIhAPqWzF9MOhWpvzms
PjYjFoQLkSiyOg7nq8MVvq+1qJQsMA0GCSqGSIb3DQEBCwUAA4IBAQBgV2c0+ptp
LaTcsbrbFGY5BcFCQdoWBr6NVzSsL8DXu6uQScycAvzU/6K29J8wpsXdcTkJKt4E
q/7dGSZQ9tyVY8aPTiMS+DmUEOQ/UZ/LGOoVND1dB1PtvvcGxQzO7IGSt6AbzUcD
NJP2NpeSmy1K5W6FYw/TntrGc+yCnCDqYqg8+CsSnv0VnUsGpPT1bQ2i4c8Xi5FR
4VAl9mn4rTou+1w59A9SNeV5ODcyZMp25GoyReeTGq4EW8R1XoTLHPyiyuDjTXmc
MemCuGw9te4aWMfPlkaqRKfxaz9MZ8tF4pUp1d2BYBAxyWBVRWPn9AFC6uX7hLVC
AqH/omQQrCfi
-----END CERTIFICATE-----

EOT;
    }


    protected function getSampleWithAltNames()
    {
        return <<<EOT
-----BEGIN CERTIFICATE-----
MIIFzDCCBLSgAwIBAgIQCz1rkmJ9DZD2VtQGPA0YJTANBgkqhkiG9w0BAQsFADBg
MQswCQYDVQQGEwJVUzEVMBMGA1UEChMMRGlnaUNlcnQgSW5jMRkwFwYDVQQLExB3
d3cuZGlnaWNlcnQuY29tMR8wHQYDVQQDExZSYXBpZFNTTCBUTFMgUlNBIENBIEcx
MB4XDTE4MDQxNzAwMDAwMFoXDTE5MDYxNjEyMDAwMFowHzEdMBsGA1UEAwwUKi5i
cmV0dGVya2xpZWJlci5jb20wggEiMA0GCSqGSIb3DQEBAQUAA4IBDwAwggEKAoIB
AQCe2/Or3Uof5KADwTpeiTbbS+iha2ZwQChxbSLBfgVJ41zL5qxH3xrjODWyrhbC
LwnZMmpBXMI4uYjJLItUx/mw/lyVblLV3H0Y2TwP5Vb47M37npg9q1rpfYyHLh84
HCSSWnih7Pcq8sgGmHOycdGavgrvlYL6eJoydFCImi3jsB3lW3CLGNNQaIWwUBpp
3tCBc9OIvV26mLWoxlvU6aC4Ijdc9oh8koicaQLuYVE4S/BQQf7fDecFCwlTE2uZ
3qCIhWYe6batsUHigCm+XbR/YUEtRXZN5A3SW+zdtRvfmzgj+b5HI2NaK11zPVTm
qdP64kn9Z6noQk5fUWMdTbsLAgMBAAGjggLBMIICvTAfBgNVHSMEGDAWgBQM22yC
SQ9KZwq4FO56xEhSiOtWODAdBgNVHQ4EFgQUy53qPEtB4Hr7O9Vi6h1GO4jHvsww
MwYDVR0RBCwwKoIUKi5icmV0dGVya2xpZWJlci5jb22CEmJyZXR0ZXJrbGllYmVy
LmNvbTAOBgNVHQ8BAf8EBAMCBaAwHQYDVR0lBBYwFAYIKwYBBQUHAwEGCCsGAQUF
BwMCMD8GA1UdHwQ4MDYwNKAyoDCGLmh0dHA6Ly9jZHAucmFwaWRzc2wuY29tL1Jh
cGlkU1NMVExTUlNBQ0FHMS5jcmwwTAYDVR0gBEUwQzA3BglghkgBhv1sAQIwKjAo
BggrBgEFBQcCARYcaHR0cHM6Ly93d3cuZGlnaWNlcnQuY29tL0NQUzAIBgZngQwB
AgEwdgYIKwYBBQUHAQEEajBoMCYGCCsGAQUFBzABhhpodHRwOi8vc3RhdHVzLnJh
cGlkc3NsLmNvbTA+BggrBgEFBQcwAoYyaHR0cDovL2NhY2VydHMucmFwaWRzc2wu
Y29tL1JhcGlkU1NMVExTUlNBQ0FHMS5jcnQwCQYDVR0TBAIwADCCAQMGCisGAQQB
1nkCBAIEgfQEgfEA7wB2AKS5CZC0GFgUh7sTosxncAo8NZgE+RvfuON3zQ7IDdwQ
AAABYtNdE3cAAAQDAEcwRQIgfxxQea4WkzkoBrOk10vXGjfUD/XJ9Byogcj7bAE7
MRsCIQDk+Qxeh2/YErJo7iOzJ52Ii7qRjcLBkMos4XNB6faYigB1AG9Tdqwx8DEZ
2JkApFEV/3cVHBHZAsEAKQaNsgiaN9kTAAABYtNdFKsAAAQDAEYwRAIgYfpFUrvj
dxp3y8GFqOLDZzy54Z3xTu/yrWS7nvv1AMoCID7NqaWpz9OhJFNZBJ4JDMlBX2S1
draI9fGgX4LaBYptMA0GCSqGSIb3DQEBCwUAA4IBAQBseY4ctyt4803qv5DqkN9e
MqrolDEdoVyuBfHQLWCHwvEqv7YmQe3Qj6ywJ7B8OUlcH/5raVWD3HjW1XrMCba/
mscftUBqqNj4CU/D0pRTZ4e5A3hXri0Bt17iM4GsCqOmkjCvMhDqOPNgV5rRNkIb
hojhNzfFCvPoYXffPqiozNgqqALo9tbSZZlAJTQ/mUZwXz2lPUmdHnRxyuPsnrnj
0DmhEEkUCGhXeHlsiLPpFHRdL+z+fHMonKEs9kZ0q04YLtMwZ3ps9JiVi9TNz+bU
lrvUoKFrDtdQCHC0U5kXblEg0GB56lb6iO1JMg3V0y6Z3YaMhtBNfoXE3qBUxVwL
-----END CERTIFICATE-----
EOT;
    }

}
