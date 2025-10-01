<?php

/**
 * Helper class to parse request
 */
class KoncertoRequest
{
    /**
     * @return string
     */
    public function getPathInfo() {
        if (array_key_exists('PATH_INFO', $_SERVER)) {
            return $_SERVER['PATH_INFO'];
        }

        return '/';
    }

    /**
     * @param string $argName
     * @return mixed
     */
    public function get($argName) {
        if (!array_key_exists($argName, $_REQUEST)) {
            return null;
        }

        return $_REQUEST[$argName];
    }

    /**
     * @param string $argName
     * @param mixed $argValue
     * @return void
     */
    public function set($argName, $argValue) {
        $_REQUEST[$argName] = $argValue;
    }
}
