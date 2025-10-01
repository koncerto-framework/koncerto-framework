<?php

/**
 * Koncerto Framework
 * This is Koncerto Framework main class
 */
class Koncerto
{
    static $config = array();

    /**
     * Use Koncerto from source with autoload
     * @return void
     */
    public static function autoload() {
        spl_autoload_register('Koncerto::loadClass');
    }

    /**
     * Load class from source when using Koncerto with autoload
     * @param string $className
     * @return void
     */
    public static function loadClass($className) {
        if ('clsTinyButStrong' === $className && !class_exists('clsTinyButStrong')) {
            self::loadTBS();
            return;
        }

        if (!class_exists($className)) {
            require_once(dirname(__FILE__) . '/' . $className . '.php');
        }
    }

    /**
     * @param array<string, mixed> $config
     * @return void
     */
    public static function setConfig($config) {
        Koncerto::$config = $config;
    }

    /**
     * @param string $entry
     * @return ?string
     */
    public static function getConfig($entry) {
        $config = Koncerto::$config;

        $path = explode('.', $entry);

        while (false !== ($subentry = array_shift($path))) {
            if (!is_array($config) || !array_key_exists($subentry, $config)) {
                $config = null;

                break;
            }

            $config = $config[$subentry];
            if (0 === count($path)) {
                break;
            }
        }

        return $config;
    }

    /**
     * Static function to return response from Koncerto Framework
     * @return string
     */
    public static function response() {
        $request = new KoncertoRequest();
        $router = new KoncertoRouter();
        $match = $router->match($request->getPathInfo());
        if (null === $match) {
            throw new Exception(sprintf('No match for route %s', $request->getPathInfo()));
        }
        list($controller, $action) = explode('::', $match);
        $response = (new $controller())->$action();
        $headers = $response->getHeaders();
        foreach ($headers as $headerName => $headerValue) {
            header(sprintf('%s: %s', $headerName, $headerValue));
        }

        return $response->getContent();
    }

    /**
     * @return void
     */
    private static function loadTBS() {
        $tbsLocations = array(
            dirname(__FILE__) . '/tbs_class.php',
            dirname(__FILE__) . '/tinybutstrong/tbs_class.php',
            dirname(__FILE__) . '/../tinybutstrong/tbs_class.php'
        );

        foreach ($tbsLocations as $tbsLocation) {
            if (is_file($tbsLocation)) {
                require_once($tbsLocation);

                return;
            }
        }
    }
}
