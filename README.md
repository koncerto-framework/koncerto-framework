# koncerto-framework

Koncerto is a simple PHP framework.

The main concept is to have a light PHP framework in a single PHP script.

Koncerto uses TinyButStrong for its template engine.

## Project structure

* koncerto.php : Koncerto classes
* tinybutstrong/tbs_class.php : TBS template engine
* _cache : Koncerto cache folder (e.g. routes.json)
* _controller : folder containing controllers
* _templates : folder containing TBS templates (*.tbs.html)

## Classes

* Koncerto
* KoncertoRouter
* KoncertoRequest
* KoncertoResponse
* KoncertoController
* KoncertoForm
* KoncertoField

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

### KoncertoForm

Helper class to define form to use with `_form.tbs.html` template.

### KoncertoField

Helper class to define form fields.

## Project status

Koncerto is in early stage of developement. Nothing is ready for production. Most features are missing.

## To-do list

* Framework classes : ongoing
* Forms : ongoing
* Templates : ongoing
* CLI : to-do (koncerto generate, koncerto serve, etc)
* Entities & ORM : to-do
* JavaScript bridge : to-do (~ stimulus)
* Playground : to-do (koncerto using php-wasm)

## Recommended frameworks and libraries

* https://bulma.io/
* https://icons8.com/line-awesome/
