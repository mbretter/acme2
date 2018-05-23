<?php

namespace Karl\Acme2\Resources;

use Karl\Acme2;

use InvalidArgumentException;

class Authorization
{
    use Acme2\Dependency\Acme;

    public function __construct(Acme2\Acme $acme)
    {
        $this->acme = $acme;
    }

    /**
     * @param $url
     *
     * @return mixed
     */
    public function get($url)
    {
        $response = $this->acme->get($url);

        return json_decode($response->getBody());
    }

    /**
     * update authorization currently
     * possible keys: status => deactivated
     *
     * @param $url
     * @param array $params
     *
     * @return mixed
     * @throws Acme2\Exception\RequestException
     */
    public function update($url, $params = [])
    {
        $response = $this->acme->send($url, 'post', $params);

        return json_decode($response->getBody());
    }

    /**
     * return authorization by Type
     *
     * @param object $authorization the authorization object as returned by the get call
     * @param string $type (http-01,dns-01)
     *
     * @return object
     */
    public function getChallenge($authorization, $type = 'http-01')
    {
        if (!is_object($authorization) || !isset($authorization->challenges) || !is_array($authorization->challenges))
            throw new InvalidArgumentException('invalid authorization object.');

        $challenge = null;
        foreach ($authorization->challenges as $c)
        {
            if (!is_object($c) || !isset($c->type) || !isset($c->type))
                throw new InvalidArgumentException('invalid authorization object.');

            if ($c->type == $type)
            {
                $challenge = $c;
                break;
            }
        }

        if ($challenge === null)
            throw new InvalidArgumentException("No challenge found for type `$type`");

        return $challenge;
    }
}
