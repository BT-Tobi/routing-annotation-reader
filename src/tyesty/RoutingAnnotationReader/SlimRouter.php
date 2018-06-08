<?php
declare(strict_types=1);

namespace tyesty\RoutingAnnotationReader;

/**
 * Slim Route creation class
 *
 * This class creates slim routes definition from a Reader object.
 *
 * @package tyesty\RoutingAnnotationReader
 */
class SlimRouter implements RouterInterface
{
    // the cache file name
    const cacheFile = "__CACHE__slimrouter_inject.php";

    /**
     * Injects routes found by the annotation reader into a Slim application.
     *
     * @param ReaderInterface $reader
     * @param string $app_name
     * @param null|string $cache_folder
     * @throws SlimRouterInjectionException
     */
    public static function inject(ReaderInterface $reader, string $app_name = "app", ?string $cache_folder = null, int $cache_ttl = 60): void {

        // it's evil, but that's the only way to be able to get the app both cached and non-cached
        global $$app_name;

        // check if parsing is needed
        $parsingNeeded = true;
        if ($cache_folder !== null && file_exists($cache_folder . DIRECTORY_SEPARATOR . self::cacheFile)) {
            if ($cache_ttl == 0 || (time() - filemtime($cache_folder . DIRECTORY_SEPARATOR . self::cacheFile) < $cache_ttl)) {
                $parsingNeeded = false;
            }
        }

        // if parsing is needed (no cache present or invalid)
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

                    $s_call .= "\${$app_name}->{$route["method"]}(\"" . $route["route"] . "\", " . $route["action"] . "){$name}{$middleware};\n";

                } catch (\Throwable $t) {
                    throw new SlimRouterInjectionException($t->getMessage() . "\n" . print_r($route, true));
                }
            }

            // eval (is evil) the call
            eval($s_call);

            // and cache (if requested)
            if ($cache_folder !== null and realpath($cache_folder) !== false) {
                $cache_path = realpath($cache_folder) . DIRECTORY_SEPARATOR . self::cacheFile;
                file_put_contents($cache_path,
                    "<?php\n/**\n * Slim routes definition cache, generated on " . date("Y-m-d H:i:s") . "\n * This file is valid for $cache_ttl seconds.\n */\n$s_call\n?>");
            }

        }

        // otherwise just include the cache file
        else {
            require_once(realpath($cache_folder) . DIRECTORY_SEPARATOR . self::cacheFile);
        }

    }

}