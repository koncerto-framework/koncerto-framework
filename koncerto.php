<?php

/**
 * Koncerto Framework
 * This is Koncerto Framework main class
 */
class Koncerto
{
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

        return $response->getContent();
    }
}

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
}

/**
 * Helper class to prepare response
 */
class KoncertoResponse
{
    /** @var ?string */
    private $content = null;

    /**
     * @param ?string $content
     * @return KoncertoResponse
     */
    public function setContent($content) {
        $this->content = $content;

        return $this;
    }

    /**
     * @retrun string
     */
    public function getContent() {
        return $this->content;
    }
}

/**
 *  Base (and Helper) class for Koncerto controllers
 */
class KoncertoController
{
    /**
     * @param string $template
     * @param array<string, mixed> $context
     * @param array<string, string> $headers
     */
    public function render($template, $context = array(), $headers = array()) {
        require_once('tinybutstrong/tbs_class.php');

        $tbs = new clsTinyButStrong();
        $tbs->MethodsAllowed = true;
        $tbs->ObjectRef = array();
        $tbs->ObjectRef['request'] = new KoncertoRequest();
        $tbs->LoadTemplate($template);

        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $tbs->MergeBlock($key, 'array', $value);
                continue;
            }
            if (is_a($value, 'KoncertoForm')) {
                /** @var KoncertoForm */
                $form = $value;
                $fieldKey = sprintf('%s.%s', $key, 'field');
                $tbs->MergeBlock($fieldKey, $form->getFields());
                foreach ($form->getFields() as $field) {
                    if ('select' === $field->getType()) {
                        $optionsKey = sprintf(
                            '%s.%s.%s.%s',
                            $key,
                            'field',
                            $field->getName(),
                            'options'
                        );
                        $tbs->MergeBlock($optionsKey, $field->getOptions());
                    }
                }
                continue;
            }
            $tbs->MergeField($key, $value);
        }

        return $tbs->Show(false);
    }
}

class KoncertoForm
{
    /** @var KoncertoField[] */
    private $fields = array();

    /**
     * @param KoncertoField $field
     * @return KoncertoForm
     */
    public function add($field) {
        array_push($this->fields, $field);

        return $this;
    }

    /**
     * @return KoncertoField[]
     */
    public function getFields() {
        return $this->fields;
    }
}

class KoncertoField
{
    /** @var ?string */
    private $name = null;
    /** @var string */
    private $type = 'text';
    /** @var ?string */
    private $label = null;
    /** @var array<array-key, string> */
    private $options = array();

    /**
     * @param string $type
     * @return KoncertoField
     */
    public function setName($name) {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * @param string $type
     * @return KoncertoField
     */
    public function setType($type) {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType() {
        return $this->type;
    }

    /**
     * @param string $label
     * @return KoncertoField
     */
    public function setLabel($label) {
        $this->label = $label;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getLabel() {
        return $this->label;
    }

    /**
     * @param array<array-key, string> $options
     * @return KoncertoField
     */
    public function setOptions($options) {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<array-key, string>
     */
    public function getOptions() {
        return $this->options;
    }
}
