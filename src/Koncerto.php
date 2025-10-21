<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

/**
 * Koncerto Framework
 * This is Koncerto Framework main class
 */
class Koncerto
{
    /**
     * @var array<string, string|array>
    */
    // @phpstan-ignore missingType.iterableValue
    private static $config = array();

    /**
     * Use Koncerto from source with autoload
     *
     * @return void
     */
    public static function autoload()
    {
        spl_autoload_register('Koncerto::loadClass');
    }

    /**
     * Load class from source when using Koncerto with autoload
     *
     * @param  string $className
     * @return void
     */
    public static function loadClass($className)
    {
        if (class_exists($className)) {
            return;
        }

        if ('clsTinyButStrong' === $className && !class_exists('clsTinyButStrong')) {
            self::loadTBS();
            return;
        }

        $classFile = sprintf('%s/%s.php', dirname(__FILE__), $className);
        $root = is_string($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '.';

        if (!is_file($classFile)) {
            $classFile = sprintf('%s/%s.php', $root, $className);
        }

        if (!is_file($classFile) && 'Controller' === substr($className, -10)) {
            $classFile = sprintf('%s/_controller/%s.php', $root, $className);
        }

        if (!is_file($classFile)) {
            throw new Exception(sprintf('Class file [%s] not found', $classFile));
        }

        include_once $classFile;
    }

    /**
     * @param  array<string, string|array<string, string>> $config
     * @return void
     */
    public static function setConfig($config)
    {
        Koncerto::$config = $config;
        if (array_key_exists('documentRoot', $config)) {
            $_SERVER['DOCUMENT_ROOT'] = $config['documentRoot'];
        }
    }

    /**
     * @param  string $entry
     * @return ?string
     */
    public static function getConfig($entry)
    {
        $config = Koncerto::$config;

        $path = explode('.', $entry);

        // @phpstan-ignore notIdentical.alwaysTrue
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

        return is_string($config) ? $config : null;
    }

    /**
     * Static function to return response from Koncerto Framework
     *
     * @return string
     */
    public static function response()
    {
        $request = new KoncertoRequest();
        $router = new KoncertoRouter();
        $pathInfo = $request->getPathInfo();
        $match = $router->match($pathInfo);
        if (null === $match && '.php' !== strrchr($pathInfo, '.') && is_file('.' . $pathInfo)) {
            return (string)file_get_contents('.' . $pathInfo);
        }
        if (null === $match) {
            throw new Exception(sprintf('No match for route %s', $pathInfo));
        }
        list($controller, $action) = explode('::', $match);
        $classFile = sprintf('%s/_controller/%s.php', dirname(__FILE__), $controller);
        if (!class_exists($controller) && is_file($classFile)) {
            include_once $classFile;
        }
        $root = is_string($_SERVER['DOCUMENT_ROOT']) ? $_SERVER['DOCUMENT_ROOT'] : '.';
        $classFile = sprintf('%s/_controller/%s.php', $root, $controller);
        if (!class_exists($controller) && is_file($classFile)) {
            include_once $classFile;
        }
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
    private static function loadTBS()
    {
        $tbsLocations = array(
            dirname(__FILE__) . '/tbs_class.php',
            dirname(__FILE__) . '/../tbs_class.php',
            dirname(__FILE__) . '/tinybutstrong/tbs_class.php',
            dirname(__FILE__) . '/../tinybutstrong/tbs_class.php'
        );

        foreach ($tbsLocations as $tbsLocation) {
            if (is_file($tbsLocation)) {
                include_once $tbsLocation;

                return;
            }
        }
    }

    /**
     * Parse internal comment
     *
     * @param string|false $comment
     * @return array<array-key, mixed>
     */
    public static function getInternal($comment)
    {
        if (false === $comment) {
            return array();
        }

        $lines = explode("\n", $comment);
        foreach ($lines as $line) {
            $line = trim($line);
            // @phpstan-ignore argument.sscanf
            if (2 === sscanf($line, "%*[^@]@internal %[^\n]s", $json)) {
                $internal = (array)json_decode((string)$json, true);
                return $internal;
            }
        }

        return array();
    }

    /**
     * Set/update cache and optionnaly returns a specific key from this cache
     *
     * @param string $name The cache filenale
     * @param ?string $return The key from cache to return
     * @param array<array-key, mixed> $data The data to put in cache, reads from cache if empty
     * @param ?string $source Source directory or file to invalidate cache
     * @return string|array<array-key, mixed>|null
     */
    public static function cache($name, $return = null, $data = array(), $source = null)
    {
        $result = null;

        $cacheDir = '_cache/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir);
        }
        $cacheFile = $cacheDir . $name . '.json';
        if (!is_file($cacheFile)) {
            file_put_contents($cacheFile, '[]');
        }

        $cache = (array)json_decode((string)file_get_contents($cacheFile), true);

        if (null !== $source && (is_dir($source) || is_file($source))) {
            $stat = stat($source);
            if (false !== $stat && $stat[9] >= filemtime($cacheFile)) {
                $cache = array();
            }
        }

        $cache = array_merge($cache, $data);

        if (file_put_contents($cacheFile, json_encode($cache, JSON_PRETTY_PRINT))) {
            $result = $cache;
        }

        if (null === $return) {
            return $result;
        }

        if (!array_key_exists($return, $data)) {
            return null;
        }

        $result = $data[$return];

        if (is_string($result) || is_array($result)) {
            return $result;
        }

        return null;
    }
}
