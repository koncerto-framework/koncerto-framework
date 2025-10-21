<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

/**
 *  Base (and Helper) class for Koncerto controllers
 */
class KoncertoController
{
    /**
     * @param  string                $template
     * @param  array<string, mixed>  $context
     * @param  array<string, string> $headers
     * @return KoncertoResponse
     */
    public function render($template, $context = array(), $headers = array())
    {
        Koncerto::loadClass('clsTinyButStrong');

        $tbs = new clsTinyButStrong();
        $tbs->MethodsAllowed = true;
        $tbs->ObjectRef = array();
        $tbs->ObjectRef['request'] = new KoncertoRequest();
        $tbs->ObjectRef['router'] = new KoncertoRouter();
        $tbs->SetOption('include_path', dirname(__FILE__) . '/_templates');
        $tbs->SetOption('include_path', dirname(__FILE__) . '/../_templates');
        $tbs->SetOption('include_path', dirname(__FILE__) . '/..');
        $tbs->SetOption('include_path', dirname(__FILE__));
        $tbs->LoadTemplate($template);

        foreach ($context as $key => $value) {
            if (is_array($value)) {
                $tbs->MergeBlock($key, 'array', $value);
                continue;
            }
            if (is_object($value) && is_a($value, 'KoncertoForm')) {
                /**
                 * @var KoncertoForm
                 */
                $form = $value;
                $form->setOption('name', $key);
                $tbs->TplVars[sprintf('forms[%s]', $key)] = $form;
                $dataKey = sprintf('data[%s]', $key);
                $tbs->TplVars[$dataKey] = array();
                foreach ((array)$form->getData() as $k => $v) {
                    $fieldKey = sprintf('field[%s]', $k);
                    $tbs->TplVars[$dataKey][$fieldKey] = $v;
                }
                foreach ($form->getOptions() as $optionName => $optionValue) {
                    $optionKey = sprintf(
                        '%s.%s.%s',
                        $key,
                        'option',
                        $optionName
                    );
                    $tbs->MergeField($optionKey, $optionValue);
                }
                $optionKey = sprintf(
                    '%s.%s',
                    $key,
                    'option'
                );
                $tbs->MergeField($optionKey, '');
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

        $response = new KoncertoResponse();
        foreach ($headers as $headerName => $headerValue) {
            $response->setHeader($headerName, $headerValue);
        }

        $tbs->Show(TBS_NOTHING);

        return $response->setContent($tbs->Source);
    }

    /**
     * @param array<array-key, mixed> $data
     * @param array<string, mixed> $options
     * @return KoncertoResponse
     */
    public function json($data, $options = array())
    {
        $json = (string)json_encode($data);
        if (array_key_exists('pretty', $options) && is_bool($options['pretty']) && true === $options['pretty']) {
            $json = (string)json_encode($data, JSON_PRETTY_PRINT);
        }

        $response = (new KoncertoResponse())->setHeader('Content-type', 'application/json');

        if (array_key_exists('headers', $options) && is_array($options['headers'])) {
            foreach ($options['headers'] as $headerName => $headerValue) {
                if (is_string($headerValue) || is_numeric($headerValue) || is_bool($headerValue)) {
                    $response->setHeader($headerName, (string)$headerValue);
                }
            }
        }

        return $response->setContent($json);
    }

    /**
     * Redirect to another url
     *
     * @param string $url
     * @return KoncertoResponse
     */
    public function redirect($url)
    {
        return (new KoncertoResponse())
            ->setHeader('Location', $url)
            ->setContent(null);
    }

    /**
     * Get the route associated with the controller from internal comment
     *
     * @param class-string $className
     * @return ?string
     */
    public function getRoute($className = null)
    {
        if (null === $className) {
            $className = get_called_class();
        }
        $ref = new ReflectionClass($className);
        $internal = Koncerto::getInternal($ref->getDocComment());

        if (!array_key_exists('route', $internal)) {
            return null;
        }

        if (!array_key_exists('name', $internal['route'])) {
            return null;
        }

        return $internal['route']['name'];
    }
}
