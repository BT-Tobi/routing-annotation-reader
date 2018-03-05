<?php
declare(strict_types=1);

namespace tyesty\RoutingAnnotationReader;

/**
 * Routing annotation reader
 *
 * This small class indexes all controller classes in a given set of directories
 * and creates a list of routes out of the action methods.
 *
 * To ensure that your controller action is indexed, simply add a bunch of docblock to your
 * Controller classes.
 *
 * @package tyesty/RoutingAnnotationReader
 * @author  Tobias Stursberg <tobias@stursberg.de>
 * @license MIT
 * @see     https://github.com/tyesty/routing-annotation-reader
 */
class Reader {

	/**
	 * the directories we're walking through
	 *
	 * @var array
	 */
	private $directories = [];

	/**
	 * the logfile for writing down the current routes
	 *
	 * @var ?string
	 */
	private $routeLog = null;

	/**
	 * the classlist we're walking through
	 *
	 * @var array
	 */
	private $classlist = [];

	/**
	 * the routes found in the class annotations
	 *
	 * @var array
	 */
	private $routes = [];

	/**
	 * The class postfix. Only classes that have this postfix will get indexed
	 *
	 * @var string
	 */
	private $classPostfix = "Controller";

	/**
	 * The method postfix. Only methods that have this postfix will get indexed
	 *
	 * @var string
	 * @todo use it ;-)
	 */
	private $methodPostfix = "Action";


	/**
	 * RoutingAnnotationReader constructor.
	 *
	 * Sets the directories and an optional log file
	 *
	 * @param array       $directories Directories to search in for Controller classes
	 * @param null|string $route_log   Complete path to the log file (incl. filename)
	 *
	 * @throws \ReflectionException
	 */
	public function __construct(array $directories, ?string $route_log = null) {
		$this->directories = $directories;
		$this->routeLog = $route_log;
	}

	/**
	 * Sets the class postfix for the annotation reader
	 *
	 * @param string $class_postfix The class postfix
	 */
	public function setClassPostfix(string $class_postfix): void {
		$this->classPostfix = $class_postfix;
	}

	/**
	 * Runs the annotation reader itself
	 *
	 * @throws \ReflectionException
	 */
	public function run(): void {
		$this->calculateClassList();
		$this->buildRoutes();
		if ($this->routeLog !== null) {
			$this->writeRouteLog();
		}
	}

	/**
	 * Calculates the class list the annotation reader shall read.
	 */
	private function calculateClassList(): void {

		// walk through all the directories
		foreach ($this->directories as $s_directory) {

			// and look for files ending with "{$this->classPostfix}.php"
			$_o_rec = new \RecursiveDirectoryIterator(realpath($s_directory));
			$_o_it = new \RecursiveIteratorIterator($_o_rec);
			$a_regex = new \RegexIterator($_o_it, '/^.+' . $this->classPostfix . '\.php$/i', \RecursiveRegexIterator::GET_MATCH);

			// walk through all the controller files and build up a list of classes
			foreach ($a_regex as $s_filename => $foo) {

				// fetch the namespace and the class name
				$s_classcontent = file_get_contents($s_filename);
				preg_match("/\s+namespace\s+(.*?);/i", $s_classcontent, $a_namespace);
				preg_match("/\s+class\s+(.*?)[\s\{]/i", $s_classcontent, $a_classname);

				// and add it to the class list
				$this->classlist[] = trim($a_namespace[1]) . "\\" . trim($a_classname[1]);
			}
		}
	}


	/**
	 * builds the routes out of the class annotations
	 *
	 * @throws \ReflectionException
	 */
	private function buildRoutes(): void {

		// walk through the class list
		foreach ($this->classlist as $s_classname) {

			// and build a new reflection class object
			$o_reflection = new \ReflectionClass($s_classname);

			// now preg through the class comment and get the base route
			preg_match("/@BaseRoute\s+(.*?)\s/i", $o_reflection->getDocComment(), $a_base_route);
			$s_base_route = $a_base_route[1];

			// walk through all the methods
			foreach ($o_reflection->getMethods() as $_o_method) {

				$o_method = new \ReflectionMethod($_o_method->class, $_o_method->name);
				$s_action = $_o_method->class . ":" . $_o_method->name;

				// get the method comment and fetch the route
				preg_match_all("/@Route\s+\[(.*?)\]\s+(.*?)\s+(\((.*?)\))?/i", $o_method->getDocComment(), $a_m_comment, PREG_SET_ORDER);

				foreach ((array)$a_m_comment as $a_comment) {

					// clean route part, method and name values (cast name to string, because it can be NULL)
					if ($a_comment[2] == "/") {
						$a_comment[2] = "";
					}
					$s_method = trim(strtolower($a_comment[1]));
					$s_name = trim(strtolower((string)$a_comment[4]));

					// build the complete route
					$s_route = preg_replace("/\/{2,}/is", "/", $s_base_route . $a_comment[2]);

					// check for route or name collisions
					array_walk($this->routes, function ($route, $key) use ($s_method, $s_name, $s_route, $s_action) {

						// check for route collision
						if ($route["method"] === $s_method && $route["route"] === $s_route) {
							throw new \Exception("Route collision detected: " . $s_method . " " . $s_route . " in " . $s_action);
						}

						// check for name collision
						if ($route["name"] === $s_name && $s_name !== "") {
							throw new \Exception("Route name collision detected: " . $s_method . " " . $s_route . " in " . $s_action);
						}

					});

					// no collision, then add the route to the routes list
					$this->routes[] = ["method" => $s_method, "route" => $s_route, "action" => $s_action, "name" => $s_name];
				}
			}
		}
	}

	/**
	 * returns the routes detected by the annotation reader
	 *
	 * @return array
	 */
	public function getRoutes(): array {
		return $this->routes;
	}

	/**
	 * writes the route log HTML dump file
	 */
	private function writeRouteLog(): void {

		// write the routelog headder
		$s_log = "<html><head>Current routes for " . $_SERVER["HTTP_HOST"] . "</head>";
		$s_log .= "<body>\n";
		$s_log = "<h2>Current routes for " . $_SERVER["HTTP_HOST"] . "</h2>\n";
		$s_log .= "<h4>autogenerated on " . date("Y-m-d H:i:s") . "</h4>\n";
		$s_log .= "<hr/>\n";

		$s_log .= "<table>\n\t<tr>\n\t\t<td><b>Method</b></td>\n\t\t<td><b>Route</b></td>\n\t\t<td><b>Name</b></td>\n\t\t<td><b>Action</b></td>\n\t</tr>\n";

		foreach ($this->routes as $a_route) {
			$s_log .= "\t<tr>\n\t\t<td>" . strtoupper($a_route["method"]) . "</td>\n\t\t<td>" . $a_route["route"] . "</td>\n\t\t<td>" . $a_route["name"] . "</td>\n\t\t<td>" . $a_route["action"] . "</td>\n\t</tr>";
		}

		$s_log .= "</table>\n</body>\n</html>";
		file_put_contents($this->routeLog, $s_log);
	}


}