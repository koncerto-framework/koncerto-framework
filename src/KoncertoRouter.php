<?php

/**
 * Routing class
 * Koncerto matches KoncertoController classes from _controller folder
 * using @internal {"route":{name:"/"}} annotation
 */
class KoncertoRouter
{
    /** @var array<string, string> */
    private $routes = array();

    /**
     * Returns KoncertoController::action for the specified url (pathInfo)
     * @param string url
     * @return ?string
     */
    public function match($url) {
        $this->getRoutes($url);

        if (array_key_exists($url, $this->routes)) {
            return $this->routes[$url];
        }

        return null;
    }

    /**
     * @param $url
     * @return void
     */
    private function getRoutes($url) {
        if (0 === count($this->routes) && is_file('_cache/routes.json')) {
            $this->routes = (array)json_decode('_cache/routes.json', true);
        }

        if (array_key_exists($url, $this->routes)) {
            return;
        }

        $d = '_controller/';
        $dir = opendir($d);
        while ($f = readdir($dir)) {
            if (is_file($d . $f) && '.php' === strrchr($f, '.')) {
                include_once($d . $f);
                $className = str_replace('.php', '', $f);
                if (class_exists($className)) {
                    if (is_subclass_of($className, 'KoncertoController')) {
                        $this->routes = array_merge(
                            $this->routes,
                            $this->getControllerRoutes($className)
                        );
                    }
                }
            }
        }

        file_put_contents('_cache/routes.json', json_encode($this->routes));
    }

    /**
     * @param string $className
     * @return array<string, string>
     */
    private function getControllerRoutes($className) {
        /** @var array<string, string> */
        $routes = array();
        $methods = (new ReflectionClass($className))->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $comment = $method->getDocComment();
            if (false === $comment) {
                continue;
            }

            $routeName = $this->getControllerRoute($comment);
            if (null === $routeName) {
                continue;
            }

            $routes[$routeName] = sprintf(
                '%s::%s',
                $className,
                $method->getName()
            );
        }

        return $routes;
    }

    /**
     * @param string $comment
     * @return ?string
     */
    public function getControllerRoute($comment) {
        $lines = explode("\n", $comment);
        foreach ($lines as $line) {
            $line = trim($line);
            $line = trim(preg_replace('/^\*[ ]*/', '', $line));
            if (1 === sscanf($line, '@internal %s', $json)) {
                $internal = (array)json_decode($json, true);
                if (!array_key_exists('route', $internal)) {
                    return null;
                }
                if (!array_key_exists('name', $internal['route'])) {
                    return null;
                }

                return $internal['route']['name'];
            }
        }

        return null;
    }
}
