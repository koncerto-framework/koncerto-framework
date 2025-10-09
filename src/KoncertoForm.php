<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

/**
 * Helper class to generate and submit forms
 * Requires template engine and _form.tbs.html template
 */
class KoncertoForm
{
    /**
     * @var KoncertoField[]
     */
    private $fields = array();

    /**
     * @var array<string, bool|float|int|string|null|array<array-key, bool|float|int|string|null>>
     */
    private $options = array();

    /**
     * @param  KoncertoField $field
     * @return KoncertoForm
     */
    public function add($field)
    {
        array_push($this->fields, $field);
        $field->setForm($this);

        return $this;
    }

    /**
     * @return KoncertoField[]
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param  string $optionName
     * @param  bool|float|int|string|null|array<array-key, bool|float|int|string|null> $optionValue
     * @return KoncertoForm
     */
    public function setOption($optionName, $optionValue = null)
    {
        $optionName = strtolower(($optionName));

        if (null === $optionValue && array_key_exists($optionName, $this->options)) {
            unset($this->options[$optionName]);

            return $this;
        }

        $this->options[$optionName] = $optionValue;

        if ('data' === $optionName && is_array($optionValue)) {
            $form = 'form';
            if (array_key_exists('class', $this->options)) {
                $form = strtolower($this->options['class']);
            }
            $request = new KoncertoRequest();
            $request->set($form, $optionValue);
        }

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @return string
     */
    public function getName()
    {
        $name = 'form';
        if (array_key_exists('name', $this->options) && !is_array($this->options['name'])) {
            $name = $this->options['name'];
        }

        return (string)$name;
    }

    /**
     * @return bool
     */
    public function isSubmitted()
    {
        $request = new KoncertoRequest();
        $class = 'form';
        if (array_key_exists('class', $this->options) && is_string($this->options['class'])) {
            $class = strtolower($this->options['class']);
        }
        $form = $request->get($class);

        return null !== $form && is_array($form);
    }

    /**
     * @return KoncertoEntity|array<string, bool|float|int|string|null>|null
     */
    public function getData()
    {
        $request = new KoncertoRequest();
        $class = 'form';
        if (array_key_exists('class', $this->options) && is_string($this->options['class'])) {
            $class = strtolower($this->options['class']);
        }

        $data = $request->get($class);

        if (null === $data || !is_array($data)) {
            return null;
        }

        $entity = $this->getEntity();
        if (null === $entity) {
            return $data;
        }

        return $entity->hydrate($data);
    }

    /**
     * @return ?KoncertoEntity
     */
    private function getEntity()
    {
        if (!array_key_exists('class', $this->options) || !is_string($this->options['class'])) {
            return null;
        }

        $class = $this->options['class'];
        $classFile = sprintf('_entity/%s.php', $class);
        if (!is_file($classFile)) {
            return null;
        }

        include_once $classFile;
        if (!class_exists($class)) {
            return null;
        }

        $entity = new $class();
        if (!is_a($entity, 'KoncertoEntity')) {
            return null;
        }

        return $entity;
    }
}
