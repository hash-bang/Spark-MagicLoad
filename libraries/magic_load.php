<?php
/**
 * Magic_load is a CodeIgniter library that attempts to remove all the tediousness out of loading libraries manually.
 * All you need to do is include Magic_load as the only item of your autoload config file:
 *
 *	$autoload['libraries'] = array('Magic_load');
 *
 * ... And from now on all models, libraries and helpers will be automatically loaded as needed.
 *
 * So:
 *
 * 	$this->load->library('Fancy_library');
 * 	$this->fancy_library->do_things('foo', 'bar');
 *
 * Becomes just:
 *
 * 	$this->fancy_library->do_things('foo', 'bar');
 *
 * @author Matt Carter <m@ttcarter.com>
 * @date 2012-01-28
 * @version 1.00
 */
class Magic_load {
	var $examine_paths = array();

	/**
	 * Load the library
	 * This really doesn't do anything other than kick-off the Inject() function
	 */
	function __construct() {
		$this->Inject();
	}

	/**
	 * Inject the Magic_load core functions into your application
	 * Ideally this should only really be called by the constructor in the config/autorun config but its included here in case you just want to invoke it for specific modules.
	 */
	function Inject() {
		$CI =& get_instance();
		if (file_exists($file = 'application/controllers/' . $CI->router->class . '.php')) { // Found the controller
			foreach ($this->Scan($file, $CI->router->method) as $alias => $attribs)
				$this->Load($alias, $attribs);
		}
	}

	/**
	 * Try and auto load a given alias by its type
	 * @param string $alias What the module SHOULD be known as after loading
	 * @param array $attribs An assoc array of extra properties. At minimum this should contain 'type'
	 */
	function Load($alias, $attribs) {
		$CI =& get_instance();
		switch ($attribs['type']) {
			case 'model':
				if (!class_exists('CI_Model')) // First load of a model
					load_class('Model', 'core'); // This is useless but does bring in the CI_Model correctly - we still need to load the class
				$CI->$alias =& load_class(strtolower($alias), 'models', '');
				break;
			case 'library':
				$CI->$alias =& load_class($alias, 'libraries');
				break;
			case 'helper':
				load_class($alias, 'helper');
				break;
			case 'spark':
				$CI->load->spark("$alias/{$attribs['version']}");
				if (!class_exists($alias)) { // Sparks didn't load the requested module up from config/autoload
					switch ($attribs['sub-type']) { // Special cases for Sparks which needs to load things using the standard loader
						case 'model': // A sparks model
							if (!class_exists('CI_Model')) // First load of a model
								load_class('Model', 'core');
							$CI->load->model(strtolower($alias));
							break;
						case 'library':
							$CI->load->library($alias);
							break;
						case 'helper':
							$CI->load->helper($alias);
							break;
					}
				}
				break;
		}
	}

	/**
	 * Scan a fresh file for references
	 * @param string $file The file name to load - must be a path relative to the root folder (e.g. application/controllers/users.php)
	 * @param string $method Optional method inside the file to optimize for. If unspecified the entire file is scanned
	 * @return array An associative array where the key is the alias and the value is the type of the module
	 */
	function Scan($file, $method = null) {
		$file = file_get_contents($file);
		if ($method && preg_match("/function\s+$method\s*\((.*?)\)\s*{(.*?)function\s+/is", $file, $matches)) // Try to constrain to just one function (MC - Yes, yes I know this is less than optimal but it's FAST)
			$file = $matches[2];
		$out = array();
		if (preg_match_all('/\$this->(.*?)->/', $file, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $match) {
				$match = $match[1];
				$class = ucfirst($match);
				$lcase = strtolower($match);
				if (isset($out[$match])) // Dont bother loading the same reference twice
					continue;
				if (file_exists($path = "application/models/$lcase.php")) {
					$out[$match] = array('type' => 'model');
				} elseif (file_exists($path = "application/libraries/$class.php")) {
					$out[$match] = array('type' => 'library');
				} elseif (file_exists($path = "application/helpers/$class.php")) {
					$out[$match] = array('type' => 'helper');
				} elseif (is_dir('sparks')) { // Hey! We're running inside a sparks compatible project - take a shot!
					if (is_dir($sparkdir = 'sparks/' . strtolower($class))) { // Found something that looks like the right spark
						$sparkdir = end(glob("$sparkdir/*")); // Grab the latest version
						$version = basename($sparkdir);
						if ($GLOBALS['CFG']->config['magic_load_complain_spark_info'] && !file_exists("$sparkdir/spark.info"))
							die("Magic_load: Spark found in $sparkdir but it doesn't appear to have a spark.info file. Either place a file there or disable magic_load_complain_spark_info.");

						if (file_exists($path = "$sparkdir/models/$lcase.php")) { // Damn you Sparks and you're clever hierarchical system! *shakes fist at sky*
							$spark_type = 'model';
						} elseif (file_exists($path = "$sparkdir/libraries/$class.php")) {
							$spark_type = 'library';
						} elseif (file_exists($path = "$sparkdir/helpers/$class.php")) {
							$spark_type = 'helper';
						} else {
							die("Magic_load: Spark found in $sparkdir but I can't tell what it is I'm supposted to be loading for the requested module - $class!");
						}

						$out[$match] = array(
							'type' => 'spark',
							'sub-type' => $spark_type,
							'version' => $version,
						);
					} else {
						die("Magic_load: I understand you want me to magically load the $class spark for you but I can't determine its version. Are you sure its installed correctly?");
					}
				} else {
					die("Unknown reference: $match");
				}
			}
		}
		return $out;
	}
}
