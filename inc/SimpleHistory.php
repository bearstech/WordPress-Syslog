<?php

/**
 * Main class for Simple History
 */
class SimpleHistory {

	const NAME = "Simple History";

	// Dont use this any more! Will be removed in future versions. Use global SIMPLE_HISTORY_VERSION instead.
	const VERSION = "2.0.22";

	/**
	 * For singleton
	 */
	private static $instance;

	/**
	 * Capability required to view the history log
	 */
	private $view_history_capability;

	/**
	 * Capability required to view and edit the settings page
	 */
	private $view_settings_capability;

	/**
	 * Array with all instantiated loggers
	 */
	private $instantiatedLoggers;

	public $pluginBasename;

	/**
	 * Bool if gettext filter function should be active
	 * Should only be active during the load of a logger
	 */
	private $doFilterGettext = false;

	/**
	 * Used by gettext filter to temporarily store current logger
	 */
	private $doFilterGettext_currentLogger = null;

	/**
	 * Used to store latest translations used by __()
	 * Required to automagically determine orginal text and text domain
	 * for calls like this `SimpleLogger()->log( __("My translated message") );`
	 */
	public $gettextLatestTranslations = array();

	/**
	 * All registered settings tabs
	 */
	private $arr_settings_tabs = array();

	function __construct() {

		/**
		 * Fires before Simple History does it's init stuff
		 *
		 * @since 2.0
		 *
		 * @param SimpleHistory $SimpleHistory This class.
		 */
		do_action("simple_history/before_init", $this);

		$this->setupVariables();

		// Actions and filters, ordered by order specified in codex: http://codex.wordpress.org/Plugin_API/Action_Reference
		add_action('plugins_loaded', array($this, 'load_plugin_textdomain'));
		add_action('plugins_loaded', array($this, 'loadLoggers'));

		// Run before loading of loggers and before menu items are added
		add_action('plugins_loaded', array($this, 'check_for_upgrade'), 5);

		// Filters and actions not called during regular boot
		add_filter("gettext", array($this, 'filter_gettext'), 20, 3);
		add_filter("gettext_with_context", array($this, 'filter_gettext_with_context'), 20, 4);

		add_filter('gettext', array( $this, "filter_gettext_storeLatestTranslations" ), 10, 3 );

		/**
		 * Fires after Simple History has done it's init stuff
		 *
		 * @since 2.0
		 *
		 * @param SimpleHistory $SimpleHistory This class.
		 */
		do_action("simple_history/after_init", $this);

		// Add some extra info to each logged context when SIMPLE_HISTORY_LOG_DEBUG is set and true
		if ( defined("SIMPLE_HISTORY_LOG_DEBUG") && SIMPLE_HISTORY_LOG_DEBUG ) {

			add_filter("simple_history/log_argument/context", function($context, $level, $message, $logger) {

				$sh = SimpleHistory::get_instance();
				$context["_debug_get"] = $sh->json_encode( $_GET );
				$context["_debug_post"] = $sh->json_encode( $_POST );
				$context["_debug_server"] = $sh->json_encode( $_SERVER );
				$context["_debug_php_sapi_name"] = php_sapi_name();

				global $argv;
				$context["_debug_argv"] = $sh->json_encode( $argv );

				$consts = get_defined_constants(true);
				$consts = $consts["user"];
				$context["_debug_user_constants"] = $sh->json_encode( $consts );

				$postdata = file_get_contents("php://input");
				$context["_debug_http_raw_post_data"] = $sh->json_encode( $postdata );

				return $context;

			}, 10, 4);

		}

	}

	/**
	 * Get singleton intance
	 * @return SimpleHistory instance
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			
			self::$instance = new SimpleHistory();

		}

		return self::$instance;

	}

	function filter_gettext_storeLatestTranslations($translation, $text, $domain) {

		$array_max_size = 5;

		// Keep a listing of the n latest translation
		// when SimpleLogger->log() is called from anywhere we can then search for the
		// translated string among our n latest things and find it there, if it's translated
		//global $sh_latest_translations;
		$sh_latest_translations = $this->gettextLatestTranslations;

		$sh_latest_translations[$translation] = array(
			"translation" => $translation,
			"text" => $text,
			"domain" => $domain,
		);

		$arr_length = sizeof($sh_latest_translations);
		if ($arr_length > $array_max_size) {
			$sh_latest_translations = array_slice($sh_latest_translations, $arr_length - $array_max_size);
		}

		$this->gettextLatestTranslations = $sh_latest_translations;

		return $translation;

	}

	public function testlog_old() {

		# Log that an email has been sent
		simple_history_add(array(
			"object_type" => "Email",
			"object_name" => "Hi there",
			"action" => "was sent",
		));

		# Will show “Plugin your_plugin_name Edited” in the history log
		simple_history_add("action=edited&object_type=plugin&object_name=your_plugin_name");

		# Will show the history item "Starship USS Enterprise repaired"
		simple_history_add("action=repaired&object_type=Starship&object_name=USS Enterprise");

		# Log with some extra details about the email
		simple_history_add(array(
			"object_type" => "Email",
			"object_name" => "Hi there",
			"action" => "was sent",
			"description" => "The database query to generate the email took .3 seconds. This is email number 4 that is sent to this user",
		));

	}

	/**
	 * During the load of info for a logger we want to get a reference
	 * to the untranslated text too, because that's the version we want to store
	 * in the database.
	 */
	public function filter_gettext($translated_text, $untranslated_text, $domain) {

		if (isset($this->doFilterGettext) && $this->doFilterGettext) {

			$this->doFilterGettext_currentLogger->messages[] = array(
				"untranslated_text" => $untranslated_text,
				"translated_text" => $translated_text,
				"domain" => $domain,
				"context" => null,
			);

		}

		return $translated_text;

	}

	/**
	 * Store messages with context
	 */
	public function filter_gettext_with_context($translated_text, $untranslated_text, $context, $domain) {

		if (isset($this->doFilterGettext) && $this->doFilterGettext) {

			$this->doFilterGettext_currentLogger->messages[] = array(
				"untranslated_text" => $untranslated_text,
				"translated_text" => $translated_text,
				"domain" => $domain,
				"context" => $context,
			);

		}

		return $translated_text;

	}

	/**
	 * Load language files.
	 * Uses the method described here:
	 * http://geertdedeckere.be/article/loading-wordpress-language-files-the-right-way
	 *
	 * @since 2.0
	 */
	public function load_plugin_textdomain() {

		$domain = 'simple-history';

		// The "plugin_locale" filter is also used in load_plugin_textdomain()
		$locale = apply_filters('plugin_locale', get_locale(), $domain);

		load_textdomain($domain, WP_LANG_DIR . '/simple-history/' . $domain . '-' . $locale . '.mo');
		load_plugin_textdomain($domain, FALSE, dirname($this->plugin_basename) . '/languages/');

	}

	/**
	 * Setup variables and things
	 */
	public function setupVariables() {

		// Capability required to view history = for who will the History page be added
		$this->view_history_capability = "edit_pages";
		$this->view_history_capability = apply_filters("simple_history_view_history_capability", $this->view_history_capability);
		$this->view_history_capability = apply_filters("simple_history/view_history_capability", $this->view_history_capability);

		// Capability required to view settings
		$this->view_settings_capability = "manage_options";
		$this->view_settings_capability = apply_filters("simple_history_view_settings_capability", $this->view_settings_capability);
		$this->view_settings_capability = apply_filters("simple_history/view_settings_capability", $this->view_settings_capability);

		$this->plugin_basename = SIMPLE_HISTORY_BASENAME;

	}

	/**
	 * Load built in loggers from all files in /loggers
	 * and instantiates them
	 */
	public function loadLoggers() {

		$loggersDir = SIMPLE_HISTORY_PATH . "loggers/";

		/**
		 * Filter the directory to load loggers from
		 *
		 * @since 2.0
		 *
		 * @param string $loggersDir Full directory path
		 */
		$loggersDir = apply_filters("simple_history/loggers_dir", $loggersDir);

		$loggersFiles = glob($loggersDir . "*.php");

		// SimpleLogger.php must be loaded first since the other loggers extend it
		require_once $loggersDir . "SimpleLogger.php";

		/**
		 * Filter the array with absolute paths to files as returned by glob function.
		 * Each file will be loaded and will be assumed to be a logger with a classname
		 * the same as the filename.
		 *
		 * @since 2.0
		 *
		 * @param array $loggersFiles Array with filenames
		 */
		$loggersFiles = apply_filters("simple_history/loggers_files", $loggersFiles);

		$arrLoggersToInstantiate = array();

		foreach ( $loggersFiles as $oneLoggerFile ) {

			$load_logger = true;

			$basename_no_suffix = basename($oneLoggerFile, ".php");

			/**
			 * Filter to completely skip loading of a logger
			 *
			 * @since 2.0.22
			 *
			 * @param bool if to load the logger. return false to not load it.
			 * @param srting slug of logger
			 */
			$load_logger = apply_filters("simple_history/logger/load_logger", $load_logger, $basename_no_suffix );

			if ( ! $load_logger ) {
				continue;
			}

			if ( basename( $oneLoggerFile ) == "SimpleLogger.php") {

				// SimpleLogger is already loaded

			} else {

				include_once $oneLoggerFile;

			}

			$arrLoggersToInstantiate[] = $basename_no_suffix;

		}

		/**
		 * Filter the array with names of loggers to instantiate.
		 *
		 * @since 2.0
		 *
		 * @param array $arrLoggersToInstantiate Array with class names
		 */
		$arrLoggersToInstantiate = apply_filters("simple_history/loggers_to_instantiate", $arrLoggersToInstantiate);
		
		// Instantiate each logger
		foreach ($arrLoggersToInstantiate as $oneLoggerName) {

			if (!class_exists($oneLoggerName)) {
				continue;
			}

			$loggerInstance = new $oneLoggerName($this);

			if (!is_subclass_of($loggerInstance, "SimpleLogger") && !is_a($loggerInstance, "SimpleLogger")) {
				continue;
			}

			$loggerInstance->loaded();

			// Tell gettext-filter to add untranslated messages
			$this->doFilterGettext = true;
			$this->doFilterGettext_currentLogger = $loggerInstance;

			$loggerInfo = $loggerInstance->getInfo();

			// Un-tell gettext filter
			$this->doFilterGettext = false;
			$this->doFilterGettext_currentLogger = null;

			// LoggerInfo contains all messages, both translated an not, by key.
			// Add messages to the loggerInstance
			$loopNum = 0;
			foreach ($loggerInfo["messages"] as $message_key => $message) {

				$loggerInstance->messages[$message_key] = $loggerInstance->messages[$loopNum];
				$loopNum++;

			}

			// Remove index keys, only keeping slug keys
			if (is_array($loggerInstance->messages)) {
				foreach ($loggerInstance->messages as $key => $val) {
					if (is_int($key)) {
						unset($loggerInstance->messages[$key]);
					}
				}
			}

			// Add logger to array of loggers
			$this->instantiatedLoggers[$loggerInstance->slug] = array(
				"name" => $loggerInfo["name"],
				"instance" => $loggerInstance,
			);

		}

		do_action("simple_history/loggers_loaded");

	}

	/**
	 * Check if plugin version have changed, i.e. has been upgraded
	 * If upgrade is detected then maybe modify database and so on for that version
	 */
	function check_for_upgrade() {

		global $wpdb;

		$db_version = get_option("simple_history_db_version");
		$first_install = false;

		// If no db_version is set then this
		// is a version of Simple History < 0.4
		// or it's a first install
		// Fix database not using UTF-8
		if (false === $db_version) {

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$db_version_prev = $db_version;
			$db_version = 1;

			update_option("simple_history_db_version", $db_version);

			// We are not 100% sure that this is a first install,
			// but it is at least a very old version that is being updated
			$first_install = true;

		}// done pre db ver 1 things

		// If db version is 1 then upgrade to 2
		// Version 2 added the action_description column
		if (1 == intval($db_version)) {

			$db_version_prev = $db_version;
			$db_version = 2;

			update_option("simple_history_db_version", $db_version);

		}

		/**
		 * If db_version is 2 then upgrade to 3:
		 * - Add some fields to existing table wp_simple_history_contexts
		 * - Add all new table wp_simple_history_contexts
		 *
		 * @since 2.0
		 */
		if (2 == intval($db_version)) {

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$db_version_prev = $db_version;
			$db_version = 3;
			update_option("simple_history_db_version", $db_version);

		}// db version 2 » 3

		/**
		 * If db version = 3
		 * then we need to update database to allow null values for some old columns
		 * that used to work in pre wp 4.1 beta, but since 4.1 wp uses STRICT_ALL_TABLES
		 * WordPress Commit: https://github.com/WordPress/WordPress/commit/f17d168a0f72211a9bfd9d3fa680713069871bb6
		 *
		 * @since 2.0
		 */
		if (3 == intval($db_version)) {

			require_once ABSPATH . 'wp-admin/includes/upgrade.php';

			$db_version_prev = $db_version;
			$db_version = 4;

			update_option("simple_history_db_version", $db_version);

		}// end db version 3 » 4

	}// end check_for_upgrade

	/**
	 * Works like json_encode, but adds JSON_PRETTY_PRINT if the current php version supports it
	 * i.e. PHP is 5.4.0 or greated
	 *
	 * @param $value array|object|string|whatever that is json_encode'able
	 */
	public static function json_encode($value) {

		return version_compare(PHP_VERSION, '5.4.0') >= 0 ? json_encode($value, JSON_PRETTY_PRINT) : json_encode($value);

	}

	/**
	 * Returns true if $haystack ends with $needle
	 * @param string $haystack
	 * @param string $needle
	 */
	public static function ends_with($haystack, $needle) {
		return $needle === substr($haystack, -strlen($needle));
	}

	/**
	 * Return translated loglevel
	 *
	 * @since 2.0.14
	 * @param string $loglevel
	 * @return string translated loglevel
	 */
	function getLogLevelTranslated($loglevel) {

		$str_translated = "";

		switch ($loglevel) {

			// Lowercase
			case "emergency":
				$str_translated = _x("emergency", "Log level in gui", "simple-history");
				break;

			case "alert":
				$str_translated = _x("alert", "Log level in gui", "simple-history");
				break;

			case "critical":
				$str_translated = _x("critical", "Log level in gui", "simple-history");
				break;

			case "error":
				$str_translated = _x("error", "Log level in gui", "simple-history");
				break;

			case "warning":
				$str_translated = _x("warning", "Log level in gui", "simple-history");
				break;

			case "notice":
				$str_translated = _x("notice", "Log level in gui", "simple-history");
				break;

			case "info":
				$str_translated = _x("info", "Log level in gui", "simple-history");
				break;

			case "debug":
				$str_translated = _x("debug", "Log level in gui", "simple-history");
				break;

			// Uppercase
			case "Emergency":
				$str_translated = _x("Emergency", "Log level in gui", "simple-history");
				break;

			case "Alert":
				$str_translated = _x("Alert", "Log level in gui", "simple-history");
				break;

			case "Critical":
				$str_translated = _x("Critical", "Log level in gui", "simple-history");
				break;

			case "Error":
				$str_translated = _x("Error", "Log level in gui", "simple-history");
				break;

			case "Warning":
				$str_translated = _x("Warning", "Log level in gui", "simple-history");
				break;

			case "Notice":
				$str_translated = _x("Notice", "Log level in gui", "simple-history");
				break;

			case "Info":
				$str_translated = _x("Info", "Log level in gui", "simple-history");
				break;

			case "Debug":
				$str_translated = _x("Debug", "Log level in gui", "simple-history");
				break;

			default:
				$str_translated = $loglevel;

		}

		return $str_translated;

	}

	public function getInstantiatedLoggers() {

		return $this->instantiatedLoggers;

	}

	public function getInstantiatedLoggerBySlug($slug = "") {

		if (empty($slug)) {
			return false;
		}

		foreach ($this->getInstantiatedLoggers() as $one_logger) {

			if ($slug == $one_logger["instance"]->slug) {
				return $one_logger["instance"];
			}

		}

		return false;

	}

	/**
	 * Check which loggers a user has the right to read and return an array
	 * with all loggers they are allowed to read
	 *
	 * @param int $user_id Id of user to get loggers for. Defaults to current user id.
	 * @param string $format format to return loggers in. Default is array.
	 * @return array
	 */
	public function getLoggersThatUserCanRead($user_id = "", $format = "array") {

		$arr_loggers_user_can_view = array();

		if (!is_numeric($user_id)) {
			$user_id = get_current_user_id();
		}

		$loggers = $this->getInstantiatedLoggers();
		foreach ($loggers as $one_logger) {

			$logger_capability = $one_logger["instance"]->getCapability();

			//$arr_loggers_user_can_view = apply_filters("simple_history/loggers_user_can_read", $user_id, $arr_loggers_user_can_view);
			$user_can_read_logger = user_can($user_id, $logger_capability);
			$user_can_read_logger = apply_filters("simple_history/loggers_user_can_read/can_read_single_logger", $user_can_read_logger, $one_logger["instance"], $user_id);

			if ($user_can_read_logger) {
				$arr_loggers_user_can_view[] = $one_logger;
			}

		}

		/**
		 * Fires before Simple History does it's init stuff
		 *
		 * @since 2.0
		 *
		 * @param array $arr_loggers_user_can_view Array with loggers that user $user_id can read
		 * @param int user_id ID of user to check read capability for
		 */
		$arr_loggers_user_can_view = apply_filters("simple_history/loggers_user_can_read", $arr_loggers_user_can_view, $user_id);

		// just return array with slugs in parenthesis suitable for sql-where
		if ("sql" == $format) {

			$str_return = "(";

			foreach ($arr_loggers_user_can_view as $one_logger) {

				$str_return .= sprintf(
					'"%1$s", ',
					$one_logger["instance"]->slug
				);

			}

			$str_return = rtrim($str_return, " ,");
			$str_return .= ")";

			return $str_return;

		}

		return $arr_loggers_user_can_view;

	}

	/**
	 * https://www.tollmanz.com/invalidation-schemes/
	 *
	 * @param $refresh bool
	 * @return string
	 */
	public static function get_cache_incrementor( $refresh = false ) {

		$incrementor_key = 'simple_history_incrementor';
		$incrementor_value = wp_cache_get( $incrementor_key );

		if ( false === $incrementor_value || true === $refresh ) {
			$incrementor_value = time();
			wp_cache_set( $incrementor_key, $incrementor_value );
		}

		//echo "<br>incrementor_value: $incrementor_value";
		return $incrementor_value;

	}

} // class


/**
 * Helper function with same name as the SimpleLogger-class
 *
 * Makes call like this possible:
 * SimpleLogger()->info("This is a message sent to the log");
 */
function SimpleLogger() {
	return new SimpleLogger( SimpleHistory::get_instance() );
}


/**
 * Add event to history table
 * This is here for backwards compatibility
 * If you use this please consider using
 * SimpleHistory()->info();
 * instead
 */
function simple_history_add($args) {

	$defaults = array(
		"action"         => null,
		"object_type"    => null,
		"object_subtype" => null,
		"object_id"      => null,
		"object_name"    => null,
		"user_id"        => null,
		"description"    => null
	);

	$context = wp_parse_args( $args, $defaults );

	$message = "{$context["object_type"]} {$context["object_name"]} {$context["action"]}";

	SimpleLogger()->info($message, $context);

} // simple_history_add
