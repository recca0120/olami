<?php

namespace Recca0120\Olami;

class Hasher
{
    /**
     * @var string
     */
    private $appSecret = '';

    /**
     * Hasher constructor.
     *
     * @param string $appSecret
     */
    public function __construct($appSecret = '')
    {
        $this->setAppSecret($appSecret);
    }

    /**
     * @param $appSecret
     *
     * @return $this
     */
    public function setAppSecret($appSecret)
    {
        $this->appSecret = $appSecret;

        return $this;
    }

    /**
     * @param array $values
     *
     * @return string
     */
    public function make($values)
    {
        return md5(implode('', [
            $this->appSecret,
            'api='.$values['api'],
            'appkey='.$values['appkey'],
            'timestamp='.$values['timestamp'],
            $this->appSecret,
        ]));
    }
}
