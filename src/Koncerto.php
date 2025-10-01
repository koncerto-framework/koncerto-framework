<?php

/**
 * Koncerto Framework
 * This is Koncerto Framework main class
 */
class Koncerto
{
    static $config = array();

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
}
