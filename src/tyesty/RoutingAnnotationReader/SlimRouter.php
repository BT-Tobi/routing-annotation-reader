<?php
declare(strict_types=1);

namespace tyesty\RoutingAnnotationReader;

use Slim\App;

class SlimRouter implements RouterInterface
{
    const cacheFile = "__CACHE__slimrouter_inject.php";

    /**
     * Injects routes found by the annotation reader into a Slim application.
     *
     * @param App $app
     * @param ReaderInterface $reader
     * @param null|string $cache_folder
     * @throws SlimRouterInjectionException
     */
    public static function inject(App $app, ReaderInterface $reader, ?string $cache_folder = null, int $cache_ttl = 60): void
    {
        // check if parsing is needed
        $parsingNeeded = true;
        if ($cache_folder !== null && file_exists($cache_folder.DIRECTORY_SEPARATOR.self::cacheFile)) {
            if ($cache_ttl == 0 || (time() - filemtime($cache_folder.DIRECTORY_SEPARATOR.self::cacheFile) < $cache_ttl)) {
                $parsingNeeded = false;
            }
        }

        if ($parsingNeeded === true) {

            // run the reader
            $reader->run();

            // initialize variables
            $s_call = '';

            // now walk through all the route definitions
            foreach ($reader->getRoutes() as $route) {
                try {
                    $middleware = '';
                    if ($route["name"] != "") {
                        $name = "->setName(\"" . $route["name"] . "\")";
                    }

                    foreach ((array)$route["middlewares"] as $mw) {
                        $middleware .= "->add(new $mw)";
                    }

                    $s_call .= "\$app->{$route["method"]}(\"" . $route["route"] . "\", ".$route["action"]."){$name}{$middleware};\n";
                } catch (\Throwable $t) {
                    throw new SlimRouterInjectionException($t->getMessage() . "\n" . print_r($route, true));
                }
            }

            eval($s_call);
            if ($cache_folder !== null) {
                file_put_contents($cache_folder.DIRECTORY_SEPARATOR.self::cacheFile, "<?php\n/**\n * Slim routes definition cache, generated on ".date("Y-m-d H:i:s")."\n * This file is valid for $cache_ttl seconds.\n */\n$s_call\n?>");
            }

        } else {
            require_once($cache_folder.DIRECTORY_SEPARATOR.self::cacheFile);
        }

    }

}