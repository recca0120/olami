<?php

namespace Recca0120\Olami;

class Arr
{
    /**
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed
     */
    public static function get($array, $key, $default = null)
    {
        if (strpos($key, '.') === false) {
            return isset($array[$key]) === true ? $array[$key] : $default;
        }

        foreach (explode('.', $key) as $segment) {
            if (isset($array[$segment]) === true) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }
}
