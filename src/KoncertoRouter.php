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
        $this->getRoutes($url);

        if (array_key_exists($url, $this->routes)) {
            return $this->routes[$url];
        }

        return null;
    }

    /**
     * @param string $url
     * @return void
     */
    private function getRoutes($url)
    {
        if (0 === count($this->routes)) {
            $routes = Koncerto::cache('routes');
            $this->routes = is_array($routes) ? array_filter(array_map(function ($route) {
                return is_string($route) ? $route : null;
            }, $routes)) : array();
        }

        $routeUpdate = stat('_controller');
        if (count($this->routes) > 0 && false !== $routeUpdate && $routeUpdate[9] > filemtime('_cache/routes.json')) {
            $this->routes = array();
        }

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
                if (count($this->routes) > 0 && filemtime($d . $f) > filemtime('_cache/routes.json')) {
                    $this->routes = array();
                }
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
            if (empty($routeName)) {
                $routeName = '/';
            }
            if (null !== $mainRoute && '/' === substr($mainRoute, 0, 1)) {
                $routeName = $mainRoute . $routeName;
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
