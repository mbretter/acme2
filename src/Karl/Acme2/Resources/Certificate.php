<?php

namespace Karl\Acme2\Resources;

use Karl\Acme2;
use Karl\Acme2\Key\KeyInterface;
use RuntimeException;
use InvalidArgumentException;

class Certificate
{
    use Acme2\Dependency\Acme;

    protected $idents = [];

    /**
     * we need our own openssl config to support
     * subject alernative names
     *
     * @var string
     */
    protected $opensslConfigTemplate = <<<EOT
[req]
distinguished_name = req_distinguished_name
req_extensions     = v3_req
x509_extensions    = v3_req

[req_distinguished_name]

[v3_req]
# The extentions to add to a self-signed cert
subjectKeyIdentifier = hash
basicConstraints     = critical,CA:false
keyUsage             = critical,digitalSignature,keyEncipherment
subjectAltName       = %s
EOT;

    public function __construct(Acme2\Acme $acme = null)
    {
        $this->acme = $acme;
    }

    /**
     * download the certificate
     *
     * @param $orderData
     * @param string $format application/pem-certificate-chain application/pkix-cert
     *
     * @return null|string
     * @throws Acme2\Exception\RequestException
     */
    public function download($orderData, $format = 'application/pem-certificate-chain')
    {
        if ($this->acme === null)
            throw new RuntimeException('Need acme for sending requests.');

        if (!is_object($orderData) || !isset($orderData->certificate))
            throw new InvalidArgumentException('invalid order data given.');

        if ($orderData->status == 'valid' && isset($orderData->certificate))
        {
            $response = $this->acme->get($orderData->certificate, ['Accept' => $format]);

            return (string)$response->getBody();
        }

        return null;
    }

    /**
     * revoke certificate
     *
     * reasons: https://tools.ietf.org/html/rfc5280#section-5.3.1
     * unspecified             (0)
     * keyCompromise           (1)
     * cACompromise            (2)
     * affiliationChanged      (3)
     * superseded              (4)
     * cessationOfOperation    (5)
     * certificateHold         (6)
     * -- value 7 is not used
     * removeFromCRL           (8)
     * privilegeWithdrawn      (9)
     * aACompromise           (10)
     *
     * @param $cert
     * @param int $reason
     *
     * @throws Acme2\Exception\RequestException
     */
    public function revoke($cert, $reason = 0)
    {
        // check if cert is PEM formatted, base64decode to binary and recode base64urlsafe
        if (preg_match('/^-+BEGIN CERTIFICATE-+(.+)-+END CERTIFICATE-+$/s', $cert, $matches))
            $cert = base64_decode($matches[1]);

        $cert = Acme2\Helper::base64urlEncode($cert);

        $this->acme->send('revokeCert', 'post', ['certificate' => $cert, 'reason' => (int)$reason]);
    }

    /**
     * build new csr, supports subject alternative names
     * returns a standardclass containing csr and key properties (PEM vprmated)
     *
     * @param array $domains
     * @param KeyInterface $key
     * @param array $dnParams "countryName","stateOrProvinceName","localityName","organizationName","organizationalUnitName"
     * @param array $options digestAlg (sha256), privateKeyBits (2048), privateKeyType (OPENSSL_KEYTYPE_RSA)
     *
     * @return \stdClass
     */
    public function newCsr(array $domains, KeyInterface $key, array $dnParams, array $options = [])
    {
        if (!isset($dnParams['countryName']))
            throw new InvalidArgumentException('dnParams must contain at least the countryName field.');

        $digestAlg = isset($options['digestAlg']) ? $options['digestAlg'] : 'sha256';

        $dn = ["commonName" => $domains[0]];

        // merge in passed dn params
        foreach ($dnParams as $p => $v)
        {
            if (strlen($v))
                $dn[$p] = $v;
        }

        // subject alternative name
        // put all domains including the main domain into the SAN field
        $sans          = array_map(function ($d) { return 'DNS:' . $d; }, $domains);
        $opensslConfig = sprintf($this->opensslConfigTemplate, implode(',', $sans));

        $privateKey = $key->generate();

        $tmpFh = tmpfile();
        fwrite($tmpFh, $opensslConfig);
        $csr = openssl_csr_new($dn, $privateKey, [
            'config'     => stream_get_meta_data($tmpFh)['uri'],
            'digest_alg' => $digestAlg
        ]);

        fclose($tmpFh);
        openssl_csr_export($csr, $csrout);

        $ret      = new \stdClass();
        $ret->csr = $csrout;
        $ret->key = $key->getPem();

        return $ret;
    }

    /**
     * reads certificate x509 information
     * converts validFrom/To into PHP DateTime objects
     *
     * @param $cert
     *
     * @return object
     */
    public function readMetadata($cert)
    {
        $info = openssl_x509_parse($cert);
        if ($info === false)
        {
            $msgs = [];
            while ($msg = openssl_error_string())
            {
                $msgs[] = $msg;
            }
            throw new RuntimeException(implode(",", $msgs));
        }

        $ret            = (object)$info;
        $ret->validFrom = new \DateTime('@' . $info['validFrom_time_t']);
        $ret->validTo   = new \DateTime('@' . $info['validTo_time_t']);

        return $ret;
    }

    /**
     * split PEM formatted certificate into an array of PEM strings
     *
     * @param string $cert PEM formatted certificate chain
     *
     * @return array
     */
    public function splitChain($cert)
    {
        $data = null;
        $chain = [];
        foreach (explode("\n", $cert) as $line)
        {
            if (preg_match('/^-+BEGIN CERTIFICATE-+/', $line))
            {
                $data = '';
            } else if (preg_match('/^-+END CERTIFICATE-+/', $line))
            {
                $data .= sprintf("%s\n", $line);
                $chain[] = $data;
                $data = null;
            }

            if ($data !== null)
                $data .= sprintf("%s\n", $line);
        }

        return $chain;
    }
}