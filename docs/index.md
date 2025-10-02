# koncerto-framework

Koncerto is a simple PHP framework.

The main concept is to have a light PHP framework in a single PHP script.

Koncerto uses TinyButStrong for its template engine.

## Project structure

* koncerto.php : Koncerto classes
* tinybutstrong/tbs_class.php : TBS template engine
* _cache : Koncerto cache folder (e.g. routes.json)
* _controller : folder containing controllers
* _entity: folder containing entities
* _templates : folder containing TBS templates (*.tbs.html)

## Usage

You can load koncerto from a single file or from source.

### Loading from single file

* Download or clone the source
* Build from source using `make` command or `php make.php`
* `koncerto.php` is found in the `dist` folder
* Copy this file to your project folder (you also need `tbs_class.php`)

```php
<?php

require_once('koncerto.php');

echo Koncerto::response();

```

### Loading from source
* Download or clone the source
* Copy src folder to your project folder (you also need `tbs_class.php`)
* Require `src/Koncerto.php` main class and call `autoload()`
```php
<?php

require_once('src/Koncerto.php');

Koncerto::autoload();

echo Koncerto::response();

```

## Classes

* Koncerto
* KoncertoRouter
* KoncertoRequest
* KoncertoResponse
* KoncertoController
* KoncertoForm
* KoncertoField
* KoncertoEntity

### Koncerto

Main Framework class that responds to a KoncertoRequest.

### KoncertoRouter

Maps a KoncertoController to the KoncertoRequest url using `@internal {"route":{"name":"/"}}` annotation.

### KoncertoRequest

Helper class to parse HTTP request.

### KoncertoResponse

Helper class to prepare HTTP response.

### KoncertoController

Helper class to render KoncertoResponse using TBS template engine.

[(read more)](controller.md)

### KoncertoForm

Helper class to define form to use with `_form.tbs.html` template.

[(read more)](form.md)

### KoncertoField

Helper class to define form fields.

### KoncertoEntity

Helper class to define entities and ORM.

## Project status

Koncerto is in early stage of developement. Nothing is ready for production. Most features are missing.

## From Symfony to Koncerto

If you used Symfony, you will find many similarities with Koncerto.

Koncerto stays compatible with PHP 5 to 8, this is why there is no attributes or enums.

Instead of new PHP attributes, this framework uses PHPdoc annotations.

No special annotations are added, Koncerto uses only standard annotations.

## From Twig to TinyButStrong

Koncerto uses TinyButStrong (TBS) for its template engine.

It is very light, PHP 5 to 8 compatible and HTML compliant.

There is no such thing as `set`, `if` or `for` statements. Instead, TBS uses `tplvars`, `fields` and `blocks`.

### Variables

| Twig | TBS |
|-|-|
|`{% set lang = 'fr' %}`|`[onload;tplvars;lang='fr']`|

### Conditions

| Twig | TBS |
|-|-|
|`{% if lang = 'fr' %}Bienvenue{% endif %}`|`[onshow;block=begin;when [var..tplvars.lang]='fr']`<br>`Bienvenue[onshow;block=end]`|
|`{{ lang == 'fr' ? 'Bienvenue' : '' }}`|`[onshow..tplvars.lang;if [val]='fr';then 'Bienvenue';else '']`|

### Loops
| Twig | TBS |
|-|-|
|`<ul>{% for lang in langs %}`<br>`<li>{{ lang }}</li>{% endfor %}</ul>`|`<ul><li>[langs.val;block=li]</li></ul>`|
|

## To-do list

* Framework classes : ongoing
* Forms : ongoing
* Templates : ongoing
* Entities & ORM : ongoing
* CLI : to-do (koncerto generate, koncerto serve, etc)
* JavaScript bridge : to-do (~ stimulus)
* Playground : to-do (koncerto using php-wasm)

## Recommended frameworks and libraries

* https://bulma.io/
* https://icons8.com/line-awesome/
