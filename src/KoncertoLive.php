<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

/**
 * KoncertoLive class to implement a JavaScript bridge
 * based on Impulsus actions and frames
 */
class KoncertoLive extends KoncertoController
{
    public function __construct()
    {
        if (!array_key_exists('csrf', $_COOKIE) || empty($_COOKIE['csrf'])) {
            $_COOKIE['csrf'] = sha1(uniqid((string)mt_rand(), true) . microtime(true) . getmypid());
            setcookie('csrf', $_COOKIE['csrf']);
        }
        $request = new KoncertoRequest();
        $request->set('_csrf', $_COOKIE['csrf']);
        $this->live();
    }

    /**
     * Get/set data from controller's live properties to Impulsus and back
     *
     * @internal {"route": {"name": "/_live"}}
     * @return KoncertoResponse
     */
    public function live()
    {
        if (null === $this->getRoute()) {
            throw new Exception(sprintf("Live controller [%s] requires a main route", get_class($this)));
        }

        $request = new KoncertoRequest();
        $csrf = $request->get('_csrf');
        if (null === $csrf || !is_string($csrf)) {
            throw new Exception('Missing required argument _csrf');
        }
        if (!array_key_exists('csrf', $_COOKIE) || !$this->validateCsrf($_COOKIE['csrf'], $csrf)) {
            throw new Exception('Invalid csrf token');
        }

        $props = $this->getLiveProps();

        $obj = array();
        foreach ($props as $propName => $prop) {
            if (is_array($prop) && array_key_exists('writable', $prop) && true === $prop['writable']) {
                $update = $request->get($propName);
                if (null !== $update) {
                    $this->$propName = $update;
                }
            }
            $obj[$propName] = $this->$propName;
        }

        return $this->json($obj, array('pretty' => true));
    }

    /**
     * @inheritdoc
     */
    public function render($template, $context = array(), $headers = array())
    {
        $response = parent::render($template, $context, $headers);

        $content = $response->getContent();

        $props = json_encode($this->getLiveProps());

        if (!array_key_exists('csrf', $_COOKIE) || empty($_COOKIE['csrf'])) {
            $_COOKIE['csrf'] = sha1(uniqid((string)mt_rand(), true) . microtime(true) . getmypid());
            setcookie('csrf', $_COOKIE['csrf']);
        }
        $csrf = $_COOKIE['csrf'];

        $controller = <<<JS
                    KoncertoImpulsus.controllers['live'] = function(controller) {
                        function liveProps() {
                            return {$props};
                        }
                        function liveUpdate(element) {
                            var update = '';
                            var props = liveProps();
                            for (var propName in props) {
                                var prop = props[propName];
                                if (prop.writable) {
                                    var target = element.targets['$' + propName];
                                    var value = target.innerText;
                                    var tagName = new String(target.tagName).toLowerCase();
                                    if ('input' === tagName) {
                                        value = target.value;
                                        if ('checkbox' === target.type) {
                                            value = target.checked ? target.value : '';
                                        }
                                    }
                                    update += '&' + encodeURIComponent(propName) + '=' + encodeURIComponent(value);
                                }
                            }

                            return update;
                        }
                        controller.on('$' + 'render',  function(controller) {
                            var csrf = 'csrf=' + controller.element.dataset.csrf;
                            KoncertoImpulsus.fetch('_live?' + csrf + liveUpdate(controller), false, function(response) {
                                var json = JSON.parse(response.responseText);
                                var props = liveProps();
                                for (var propName in props) {
                                    var prop = props[propName];
                                    if (propName in json) {
                                        var target = controller.targets['$' + propName];
                                        var tagName = new String(target.tagName).toLowerCase();
                                        if ('input' === tagName) {
                                            if ('checkbox' === target.type) {
                                                target.checked = target.value === json[propName];
                                                return;
                                            }
                                            target.value = json[propName];
                                            return;
                                        }
                                        if ('select' === tagName) {
                                            for (var i = 0; i < target.options.length; i++) {
                                                if (json[prop] === target.options[i].value) {
                                                    target.options.selectedIndex = i;
                                                    break;
                                                }
                                            }
                                            return;
                                        }
                                        target.innerText = json[propName];
                                    }
                                }
                            });
                        });
                    }

                    window.addEventListener('load', function() {
                        setTimeout(function() {
                            document.querySelector(':root').setAttribute('data-controller', 'live');
                            document.querySelector(':root').setAttribute('data-csrf', '{$csrf}');
                            document.querySelectorAll('[data-model]').forEach(function(model) {
                                model.setAttribute('data-target', '$' + model.getAttribute('data-model'));
                            });
                            var props = {$props};
                            for (var propName in props) {
                                var prop = props[propName];
                                console.debug(propName, prop);
                            }
                        }, 100);
                    });
JS;

        $impulsusLocations = array(
            'impulsus.js',
            'src/KoncertoImpulsus.js',
            'koncerto-impulsus/src/KoncertoImpulsus.js'
        );

        $impulsusValidLocations = array_filter($impulsusLocations, function ($impulsusLocation) {
            return is_file(dirname(__FILE__) . '/' . $impulsusLocation);
        });

        $impulsus = array_shift($impulsusValidLocations);

        if (null === $impulsus) {
            $impulsus = 'https://koncerto-framework.github.io/koncerto-playground/impulsus.js';
        }

        $content = str_replace('</head>', <<<HTML
                <script data-reload src="{$impulsus}"
                onload="if (window.reloadScript) reloadScript('#live-controller-script')"></script>
                <script id="live-controller-script" type="text/javascript">
                    {$controller}
                </script>
            </head>
HTML, $content);

        return $response->setContent($content);
    }

    /**
     * Get live props from class internal comments
     *
     * @return array<string, mixed>
     */
    private function getLiveProps()
    {
        $className = get_called_class();
        $props = Koncerto::cache('live', $className);
        if (is_array($props)) {
            return $props;
        }

        $props = array();

        $class = new ReflectionClass(get_called_class());
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $internal = Koncerto::getInternal($property->getDocComment());
            if (array_key_exists('live', $internal) && is_array($internal['live'])) {
                if (array_key_exists('prop', $internal['live'])) {
                    $props[$property->getName()] = $internal['live']['prop'];
                }
            }
        }

        Koncerto::cache('live', null, array($className => $props));

        return $props;
    }

    /**
     * Validate csrf token
     *
     * @param string $csrf
     * @param string $csrfToValidate
     * @return bool
     */
    private function validateCsrf($csrf, $csrfToValidate)
    {
        $length = strlen($csrf);
        if ($length !== strlen($csrfToValidate)) {
            return false;
        }

        $result = 0;
        for ($i = 0; $i < $length; $i++) {
            $result |= (ord($csrf[$i]) ^ ord($csrfToValidate[$i]));
        }

        return 0 === $result;
    }
}
