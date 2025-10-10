<?php

/**
 * KoncertoLive class to implement a JavaScript bridge
 * based on Impulsus actions and frames
 */
class KoncertoLive extends KoncertoController
{
    /**
     * Return data from controller's live properties to Impulsus
     *
     * @internal {"route": {"name": "/_live"}}
     * @return KoncertoResponse
     */
    public function getData()
    {
        $name = (string)(new KoncertoRequest())->get('name');
        $props = $this->getLiveProps();

        if (!in_array($name, $props)) {
            return $this->json(array('error' => sprintf(
                '%s is not a live property',
                $name
            )));
        }

        $value = $this->$name;

        return $this->json(array($name => $value));
    }

    /**
     * @inheritdoc
     */
    public function render($template, $context = array(), $headers = array())
    {
        $response = parent::render($template, $context, $headers);

        $content = $response->getContent();

        $props = json_encode($this->getLiveProps());

        $controller = <<<JS
                    KoncertoImpulsus.controllers['live'] = function(controller) {
                        controller.on('$' + 'render',  function(element) {
                            var props = {$props};
                            props.forEach(function(prop) {
                                KoncertoImpulsus.fetch('_live?name=' + prop, false, function(response) {
                                    var json = JSON.parse(response.responseText);
                                    if (prop in json) {
                                        element.targets['$' + prop].innerText = json[prop];
                                    }
                                });
                            })
                        });
                    }

                    window.addEventListener('load', function() {
                        setTimeout(function() {
                            document.querySelector(':root').setAttribute('data-controller', 'live');
                            document.querySelectorAll('[data-model]').forEach(function(model) {
                                model.setAttribute('data-target', '$' + model.getAttribute('data-model'));
                            });
                        }, 100);
                    });
JS;

        $impulsus = '';
        if (is_file('impulsus.js')) {
            $impulsus = 'impulsus.js';
        }
        if (is_file('src/KoncertoImpulsus.js')) {
            $impulsus = 'src/KoncertoImpulsus.js';
        }
        if (is_file('../koncerto-impulsus/src/KoncertoImpulsus.js')) {
            $impulsus = '../koncerto-impulsus/src/KoncertoImpulsus.js';
        }
        if ('' === $impulsus) {
            throw new Exception('Impulsus framework not found');
        }

        $content = str_replace('</head>', <<<HTML
                <script src="{$impulsus}"></script>
                <script type="text/javascript">
                    {$controller}
                </script>
            </head>
HTML, $content);

        return $response->setContent($content);
    }

    /**
     * Get live props from class internal comments
     */
    private function getLiveProps()
    {
        $props = array();

        $class = new ReflectionClass(get_called_class());
        $properties = $class->getProperties(ReflectionProperty::IS_PUBLIC);
        foreach ($properties as $property) {
            $comment = $property->getDocComment();
            if (false === $comment) {
                continue;
            }

            $lines = explode("\n", $comment);
            foreach ($lines as $line) {
                // @phpstan-ignore argument.sscanf
                if (1 === sscanf($line, "%*[^@]@internal %[^\n]s", $json)) {
                    $internal = (array)json_decode((string)$json, true);
                    if (array_key_exists('live', $internal) && is_array($internal['live'])) {
                        if (array_key_exists('prop', $internal['live'])) {
                            array_push($props, $property->getName());
                        }
                    }
                }
            }
        }

        return array_unique($props);
    }
}
