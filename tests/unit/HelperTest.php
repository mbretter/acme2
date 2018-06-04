<?php

namespace Acme2\Tests\Unit;

use Karl\Acme2\Helper;
use PHPUnit\Framework\TestCase;

class HelperTest  extends TestCase
{
    protected $bdata = '7eff0bfa801f27fc5258346db4ee5091fe072dd740032303ffe66edb14d3b158b4c84f79d3c51cdfcae2c929370896963c4e5d273c64012df45c56ef164c3da1';
    protected $bdata64 = 'fv8L-oAfJ_xSWDRttO5Qkf4HLddAAyMD_-Zu2xTTsVi0yE9508Uc38riySk3CJaWPE5dJzxkAS30XFbvFkw9oQ';

    /**
     * @testdox Helper::base64urlencode
     */
    public function testBase64urlEncode()
    {
        $res = Helper::base64urlEncode(hex2bin($this->bdata));
        $this->assertEquals($this->bdata64, $res);
    }

    /**
     * @testdox Helper::base64urldecode
     */
    public function testBase64urlDecode()
    {
        $res = Helper::base64urlDecode($this->bdata64);
        $this->assertEquals(hex2bin($this->bdata), $res);
    }

}
