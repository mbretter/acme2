<?php

namespace Karl\Acme2\Dependency;

trait Acme
{
    /** @var \Karl\Acme2\Acme */
    protected $acme;

    public function setAcme($acme)
    {
        $this->acme = $acme;

        return $this;
    }

    public function getAcme()
    {
        return $this->acme;
    }

}