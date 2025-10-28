<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

/**
 * Routing class
 * Koncerto matches KoncertoController classes from _controller folder
 * using internal {"route":{name:"/"}} annotation
 */
class KoncertoRouter
{
    /**
     * @var array<string, string>
     */
    private $routes = array();

    /**
     * Returns KoncertoController::action for the specified url (pathInfo)
     *
     * @param  string $url
     * @return ?string
     */
    public function match($url)
    {
        // @todo - remove arguments from url
        $this->getRoutes($url);

        if (array_key_exists($url, $this->routes)) {
            return $this->routes[$url];
        }

        return null;
    }

    /**
     * Returns url for matching route name
     *
     * @param string $routeName
     * @return string
     */
    public function generate($routeName)
    {
        $args = func_get_args();
        array_shift($args);
        if (1 === count($args) && is_array($args[0])) {
            $args = $args[0];
        } else {
            $args = array_filter(array_map(function ($arg) {
                return is_string($arg) ? $arg : null;
            }, $args));
            $args = $this->parseArgs($args);
        }

        $url = $routeName;

        if (!empty($args)) {
            $url .= '?' . http_build_query($args);
        }

        if ('true' === Koncerto::getConfig('routing.useHash')) {
            $url = '#' . $url;
        }

        return $url;
    }

    /**
     * Parse arguments from TBS (format name:value)
     *
     * @param string[] $args
     * @return array<string, string>
     */
    private function parseArgs($args = array())
    {
        foreach ($args as $index => $arg) {
            $nameValue = explode(':', strval($arg));
            $name = array_shift($nameValue);
            $value = implode(':', $nameValue);

            if ('' !== $name) {
                $args[$name] = $value;
            }

            unset($args[$index]);
        }

        return $args;
    }

    /**
     * @param string $url
     * @return void
     */
    private function getRoutes($url)
    {
        $routes = Koncerto::cache('routes', null, $this->routes, '_controller');
        $this->routes = is_array($routes) ? array_filter(array_map(function ($route) {
            return is_string($route) ? $route : null;
        }, $routes)) : array();

        if (array_key_exists($url, $this->routes)) {
            return;
        }

        $d = '_controller/';
        if (!is_dir($d)) {
            mkdir($d);
        }

        $dir = opendir($d);
        if (false === $dir) {
            return;
        }

        while ($f = readdir($dir)) {
            if (is_file($d . $f) && '.php' === strrchr($f, '.')) {
                include_once $d . $f;
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

        Koncerto::cache('routes', null, $this->routes);
    }

    /**
     * @param  class-string $className
     * @return array<string, string>
     */
    private function getControllerRoutes($className)
    {
        $ref = new ReflectionClass($className);
        $mainRoute = (new KoncertoController())->getRoute($className);
        /**
          * @var array<string, string>
          */
        $routes = array();
        $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $routeName = $this->getControllerRoute($method->getDocComment());
            if (null === $routeName) {
                continue;
            }
            if (null !== $mainRoute && '/' === substr($mainRoute, 0, 1)) {
                $routeName = $mainRoute . $routeName;
            }
            if ('//' === $routeName) {
                $routeName = '/';
            }
            if (empty($routeName)) {
                $routeName = '/';
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
     * @param  string|false $comment
     * @return ?string
     */
    public function getControllerRoute($comment)
    {
        $internal = Koncerto::getInternal($comment);

        if (!array_key_exists('route', $internal)) {
            return null;
        }

        if (!array_key_exists('name', $internal['route'])) {
            return null;
        }

        return $internal['route']['name'];
    }
}
