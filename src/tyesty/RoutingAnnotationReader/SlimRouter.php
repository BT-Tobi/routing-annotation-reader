<?php
declare(strict_types=1);

namespace tyesty\RoutingAnnotationReader;

use Slim\App;

class SlimRouter implements RouterInterface {

    /**
     * Injects routes found by the annotation reader into a Slim application.
     * 
     * @param Slim\App $app
     * @param ReaderInterface $reader
     * @throws SlimRouterInjectionException
     */
    public static function inject(App $app, ReaderInterface $reader): void {

        // run the reader
        $reader->run();

            // now walk through all the route definitions
            foreach ($reader->getRoutes() as $route) {
                try {
                    if ($route->name != "") {
                        $name = "->setName(\"" . $route->name . "\")";
                    }

                    foreach ($route->middlewares as $mw) {
                        $middleware .= "->add(new $mw)";
                    }

                    eval("\$app->{$route->method}(\"" . $route->route . "\", $route->action){$name}{$middleware};");
                } catch (\Throwable $t) {
                    throw new SlimRouterInjectionException($t->getMessage()."\n".print_r($route, true));
                }
            }
    }

}