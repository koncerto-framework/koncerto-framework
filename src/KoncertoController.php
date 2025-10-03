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

        return $response->setContent($tbs->Show(false));
    }

    /**
     * @param  array<array-key, mixed> $data
     * @return KoncertoResponse
     */
    public function json($data)
    {
        return (new KoncertoResponse())
            ->setHeader('Content-type', 'application/json')
            ->setContent((string)json_encode($data));
    }
}
