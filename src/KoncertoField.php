<?php

// phpcs:disable PSR1.Classes.ClassDeclaration

/**
 * Field type enumeration (hidden, text, etc)
 * @method string Hidden string "hidden"
 * @method string Text string "text"
 * @method string Email string "email"
 * @method string Textarea string "textarea"
 * @method string Select string "select"
 */
class KoncertoFieldType extends KoncertoEnum
{
}

/**
 * Helper class to generate form fields based on
 * Template engine and _form.tbs.html template
 */
class KoncertoField
{
    /**
     * @var ?KoncertoForm
     */
    private $form = null;
    /**
     * @var ?string
     */
    private $name = null;
    /**
     * @var string
     */
    private $type = 'text';
    /**
     * @var ?string
     */
    private $label = null;
    /**
     * @var array<array-key, string>
     */
    private $options = array();

    /**
     * @param  KoncertoForm $form
     * @return KoncertoField
     */
    public function setForm($form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @return ?KoncertoForm
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param  ?string $key
     * @return mixed
     */
    public function getData($key = null)
    {
        if (null === $this->form || null === $this->name) {
            return null;
        }

        $data = $this->form->getData();
        if (null === $data) {
            return null;
        }

        if (!is_array($data)) {
            $data = $data->serialize();
        }

        if (!array_key_exists($this->name, $data)) {
            return null;
        }

        return $data[$this->name];
    }

    /**
     * @param  string $name
     * @return KoncertoField
     */
    public function setName($name)
    {
        if ('name' === $name) {
            throw new Exception(sprintf('KoncertoField::setName(%s) - "%s" is a reserved keyword', $name, $name));
        }

        $this->name = $name;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  string $type
     * @return KoncertoField
     */
    public function setType($type)
    {
        if (!array_key_exists($type, KoncertoFieldType::cases())) {
            throw new Exception(sprintf('Unknown field type %s, expected KoncertoFieldType', $type));
        }

        $this->type = $type;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param  string $label
     * @return KoncertoField
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return ?string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param  array<array-key, string> $options
     * @return KoncertoField
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * @return array<array-key, string>
     */
    public function getOptions()
    {
        return $this->options;
    }
}
