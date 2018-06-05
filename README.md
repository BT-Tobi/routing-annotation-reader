## Routing annotation reader ##

This small class indexes all controller classes in a given set of directories and creates a list of routes out of the action methods.

To ensure that your controller action is indexed, simply add a bunch of docblock to your Controller classes and run the annotation reader.

### Usage ###

- In order to be indexed properly, your project must follow the psr-4 namespace convention and all controller classes must have the postfix `Controller`.
- All action methods must have the postfix "Action" in order to be indexed properly
- Then simply annotate the contoller/action routes in your class/method docblocks
 - For controller classes, add a `@BaseRoute <YOUR_BASE_ROUTE>` annotation to your class docblock.
 - For controller action methods, add a `@Route [<METHOD>] <YOUR_METHOD_ROUTE> (<YOUR_METHOD_NAME>)` annotation.
 - You can add middlewares for either all methods in a particular class or only for a specific method by adding a `@Middleware <YOUR_MIDDLEWARE_CLASS>` annotation.
- Run the annotation reader as described in the example below. It will then search for controller classes in the given folders and will walk through all the action methods, fetch the docblock comments and create the route array for you.

### Example ###
```php
<?php
namespace foobar\Controllers;

/**
 * Your class documentation here...
 *
 * @BaseRoute /foobar
 * @Middleware MyFirstMiddleware
 */
class FooController {

  /**
   * Your action documentation here
   *
   * @Route [GET] /test (testaction)
   * @Route [GET] /alternative/test (testaction-alt)
   */
  public function myTestAction(Request $request, Response $response): Response {
    // do something cool ;-)
    return $response;
  }

  /**
   * Your action documentation here
   *
   * @Route [POST] /test (test-post-action)
   * @Middleware MySecondMiddleware
   */
  public function myTestPostAction(Request $request, Response $response): Response {
    // do something cool ;-)
    return $response;
  }

}
```

```php
<?php

require_once("vendor/autoload.php");

// initialize the reader with the base path to your controller classes
$reader = new tyesty\RoutingAnnotationReader\Reader(["src"]);
$reader->run();
print_r($reader->getRoutes());
```
will then output
```php
Array
(
    [0] => Array
        (
            [method] => get
            [route] => /foobar/test
            [action] => foobar\Controllers\FooController:myTestAction
            [name] => testaction
            [middlewares] => [
              "MyFirstMiddleware"
            ]
        )

    [1] => Array
        (
            [method] => get
            [route] => /foobar/alternative/test
            [action] => foobar\Controllers\FooController:myTestAction
            [name] => testaction-alt
            [middlewares] => [
              "MyFirstMiddleware"
            ]
        )

    [2] => Array
        (
            [method] => post
            [route] => /foobar/test
            [action] => foobar\Controllers\FooController:myTestPostAction
            [name] => test-post-action
            [middlewares] => [
              "MyFirstMiddleware",
              "MySecondMiddleware"
            ]
        )

)
```

### Options ###

#### Set Controller class postfix ####
In order to overwrite the default controller class name postfix, simply call
```php
$reader->setClassPostfix("YourNewPostfix");
```
#### Set Action method postfix ####
In order to overwrite the default controller action method name postfix, simply call
```php
$reader->setMethodPostfix("YourNewPrefix");
```
#### Log routes to a file ####
For logging the determined routes to a HTML file, just add a second parameter to the constructor in which you set the complete path to the logfile (including the filename). The annotation reader will then automatically write the logfile. Be aware that this will slow down the reading process, so please use this option only in development environments.
```php
$reader = new Reader(["src", "somemore", "paths"], "/path/to/your/logfile.html");
```

### Route injection ###

#### Inject routes to Slim application ####
In order to inject routes into your slim application, simply add these lines to your slim bootstrap code:
```php

$app = new \Slim\App($container);

SlimRouter::inject(
  $app,
  new Reader(["src", "somemore", "paths"])
);

```
