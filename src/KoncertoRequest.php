<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

/**
 * Helper class to parse request
 */
class KoncertoRequest
{
    /**
     * @return string
     */
    public function getPathInfo()
    {
        if (array_key_exists('PATH_INFO', $_SERVER)) {
            return $_SERVER['PATH_INFO'];
        }

        if (array_key_exists('REQUEST_URI', $_SERVER)) {
            return $_SERVER['REQUEST_URI'];
        }

        return '/';
    }

    /**
     * @param  string $argName
     * @return bool|float|int|string|null|array<array-key, bool|float|int|string|null>
     */
    public function get($argName)
    {
        if (!array_key_exists($argName, $_REQUEST)) {
            return null;
        }

        $value = $_REQUEST[$argName];
        if (is_array($value)) {
            foreach ($value as $k => $v) {
                if (!is_string($v) && !is_numeric($v) && !is_bool($v)) {
                    $v = null;
                }
                $value[$k] = (string)$v;
            }
        } else {
            if (!is_string($value) && !is_numeric($value) && !is_bool($value)) {
                $value = null;
            }
        }

        return $value;
    }

    /**
     * @param  string $argName
     * @param  mixed  $argValue
     * @return void
     */
    public function set($argName, $argValue)
    {
        $_REQUEST[$argName] = $argValue;
    }
}
