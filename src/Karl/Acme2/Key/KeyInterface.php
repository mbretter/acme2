<?php

namespace Karl\Acme2\Key;

interface KeyInterface
{
    public function generate($params = []);

    public function sign($jwsProtected, $data);

    public function getPem();

    public function setPem($keydata);

    public function buildJWKThumbprint();

    public function getKid();

    public function setKid($keyId);
}