# KoncertoForm

This document explains how to generate and use a form with the Koncerto Framework.

## Generating a form

You can generate a form using a `KoncertoController`,
[TinyButStrong](https://www.tinybutstrong.com/)  template engine
and the [Bulma](https://bulma.io/) CSS framework.

This requires:

* A valid [controller](controller.md)
* The `_form.tbs.html` template
* Defining form fields (using `KoncertoField` class)
* Do something with the form data
* Optional : link the form to an entity (using `KoncertoEntity` class)

## Creating the form

Creating a form is really straight forward. Just instantiate the class in your controller.


```php
<?php

class MyController extends KoncertoController {
    /**
     * @internal {"route":{"name":"/"}}
     */
    public function index() {
        $form = new KoncertoForm();
    }
}

```

## Adding fields

Adding fields to the form is really simple, just instantiate the `KoncertoField` class.

Then use the `add` method of the `KoncertoForm` class.

```php
<?php

class MyController extends KoncertoController {
    /**
     * @internal {"route":{"name":"/"}}
     */
    public function index() {
        $form = new KoncertoForm();
        $form->add((new KoncertoField())->setName('email')->setType('email'));
    }
}

```

## Rendering the form

To render the form, you will use the `render` method with a context (second argument).

```php
<?php

class MyController extends KoncertoController {
    /**
     * @internal {"route":{"name":"/"}}
     */
    public function index() {
        $form = new KoncertoForm();
        $form->add((new KoncertoField())->setName('email')->setType('email'));

        return $this->render('_templates/index.tbs.html', array(
            'form' => $form
        ));
    }
}

```

The template will use the _form.tbs.html template to generate the form.

```html
<!-- index.tbs.html -->
<form>
    [onload;file=_form.tbs.html;rename field=form.field,option=form.option;getpart=form]
</form>
```

TinyButStrong syntax explanation:

* `onload` : this will load the file when loading your template
* `file` : the template file loaded (included; here: `_form.tbs.html`)
* `rename`:
    - inside `_form.tbs.html`, a field is identified by a `field` keyword
    - this operation will rename the keyword with the fields defined in the `form` inside the controller
    - it will also rename the keyword `option` with the form options
* `getpart`:
    - this will only copy the content of the `<form>` tag from the `_forms.tbs.html` template
    - _fields will be displayed according to their type_
    - _other tags are ignored and reserved for internal use_

The `_form.tbs.html` template does not include submit buttons.

You need to include the HTML code for these buttons in your template.

## Using form data

Data from the form submission is collected using `getData` method.

It is required to first check that the form is submitted using `isSubmitted` method.

You can link the form to an entity using `setOption` method.

Doing so, will transform the form data to an entity that can be saved to a database.

### Full example

```php
<?php

class MyController extends KoncertoController {
    /**
     * @internal {"route":{"name":"/hello"}}
     */
    public function hello() {
        $form = new KoncertoForm();
        $form->add((new KoncertoField())->setName('yourName')->setLabel('Your name'));

        $name = '';
        if ($form->isSubmitted()) {
            $data = $form->getData();
            if (is_array($data) && array_key_exists('yourName', $data)) {
                $name = $data['yourName'];
            }
        }

        return $this->render('_templates/hello.tbs.html', array(
            'form' => $form,
            'name' => $name
        ));
    }
}

```

```html
<!-- hello.tbs.html -->
<form [onshow;block=form;when [name]='']>
    [onload;file=_form.tbs.html;rename field=form.field,option=form.option;getpart=form]
    <input type="submit">
</form>
<h1>Hello [name;magnet=h1]</h1>
```