# KoncertoController

This document explains how to create and use a controller with the Koncerto Framework.

## Controller base

Any controller is saved in the `_controller` folder and extends `KoncertoController` class.

The class file must match the class name (e.g. `MyController` class is saved in a `MyController.php` file).

```php
<?php

class MyController extends KoncertoController {
}

```

## Routing

The main goal of a controller is to respond to a request. This is also called routing.

Routing is made by adding a public function to the controller with a special
[PHPdoc](https://docs.phpdoc.org/guide/references/phpdoc/tags/internal.html#internal) comment.

The comment is standard and uses `@internal` notation followed by a JSON encoded description.

The routing format is very simple: e.g. `@internal {"route":{"name":"/"}}` for the main route.

```php
<?php

class MyController extends KoncertoController {
    /**
     * @internal {"route":{"name":"/"}}
     */
    public function index() {
    }
}

```

## KoncertoResponse

Every routing function should return a KoncertoResponse.

This can be done manually or by using helper function `json` and `render`.

### Manually

Creating a `KoncertoResponse` is simple. The minimum requirement is to call the `setContent` method.

```php
<?php

class MyController extends KoncertoController {
    /**
     * @internal {"route":{"name":"/"}}
     */
    public function index() {
        return (new KoncertoResponse())->setContent('HELLO');
    }
}

```

### Using the `json` method

You can use the `json` method if your controller is used as an API endpoint.

```php
<?php

class MyController extends KoncertoController {
    /**
     * @internal {"route":{"name":"/hello.json"}}
     */
    public function hello() {
        return $this->json(array('result' => 'hello'));
    }
}

```

### Using the `render` method

This method requires [TinyButStrong](https://www.tinybutstrong.com/) template engine.
Be sure to include it in your project.

The `render` method need a template file as first argument.

Templates are stored (preferably) in a `_templates` folder and have a .tbs.html extension.

```html
<!-- hello.tbs.html -->
<h1>HELLO</h1>
```

```php
<?php

class MyController extends KoncertoController {
    /**
     * @internal {"route":{"name":"/"}}
     */
    public function index() {
        return $this->render('_templates/hello.tbs.html');
    }
}

```
