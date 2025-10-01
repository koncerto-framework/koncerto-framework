<?php

/**
 * Helper class to generate form fields based on
 * Template engine and _form.tbs.html template
 */
class KoncertoField
{
    /** @var ?KoncertoForm */
    private $form = null;
    /** @var ?string */
    private $name = null;
    /** @var string */
    private $type = 'text';
    /** @var ?string */
    private $label = null;
    /** @var array<array-key, string> */
    private $options = array();

    /**
     * @param KoncertoForm $form
     * @return KoncertoField
     */
    public function setForm($form) {
        $this->form = $form;

        return $this;
    }

    /**
     * @return KoncertoForm
     */
    public function getForm() {
        return $this->form;
    }

    /**
     * @param ?string $key
     * @return mixed
     */
    public function getData($key = null) {
        $data = $this->form->getData();
        if (null === $data) {
            return null;
        }

        if (is_a($data, 'KoncertoEntity')) {
            $data = $data->serialize();
        }

        if (!array_key_exists($this->name, $data)) {
            return null;
        }

        return $data[$this->name];
    }

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

    public function getFormName() {
        return 'form';
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
