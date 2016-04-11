<?php

/**
 * A PSR-3 inspired logger class
 * This class logs + formats logs for display in the Simple History GUI/Viewer
 *
 * Extend this class to make your own logger
 *
 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md PSR-3 specification
 */
class SimpleLogger {

	/**
	 * Unique slug for this logger
	 * Will be saved in DB and used to associate each log row with its logger
	 */
	public $slug = __CLASS__;

	/**
	 * Will contain the untranslated messages from getInfo()
	 *
	 * By adding your messages here they will be stored both translated and non-translated
	 * You then log something like this:
	 * <code>
	 *   $this->info( $this->messages["POST_UPDATED"] );
	 * </code>
	 * or with the shortcut
	 * <code>
	 *   $this->infoMessage("POST_UPDATED");
	 * </code>
	 * which results in the original, untranslated, string being added to the log and database
	 * the translated string are then only used when showing the log in the GUI
	 */
	public $messages;

	/**
	 * ID of last inserted row. Used when chaining methods.
	 */
	private $lastInsertID;

	/**
	 * Constructor. Remember to call this as parent constructor if making a childlogger
	 * @param $simpleHistory history class  objectinstance
	 */
	public function __construct($simpleHistory) {

		$this->simpleHistory = $simpleHistory;

	}

	/**
	 * Method that is called automagically when logger is loaded by Simple History
	 * Add your init stuff here
	 */
	public function loaded() {

	}

	/**
	 * Get array with information about this logger
	 *
	 * @return array
	 */
	function getInfo() {

		$arr_info = array(

			// The logger slug. Defaulting to the class name is nice and logical I think
			"slug" => __CLASS__,

			// Shown on the info-tab in settings, use these fields to tell
			// an admin what your logger is used for
			"name" => "SimpleLogger",
			"description" => "The built in logger for Simple History",

			// Capability required to view log entries from this logger
			"capability" => "edit_pages",
			"messages" => array(
				// No pre-defined variants
				// when adding messages __() or _x() must be used
			),

		);

		return $arr_info;

	}

	/**
	 * Returns the capability required to read log rows from this logger
	 *
	 * @return $string capability
	 */
	public function getCapability() {

		$arr_info = $this->getInfo();

		return $arr_info["capability"];

	}

	/**
	 * Interpolates context values into the message placeholders.
	 */
	function interpolate($message, $context = array()) {

		if (!is_array($context)) {
			return $message;
		}

		// build a replacement array with braces around the context keys
		$replace = array();
		foreach ($context as $key => $val) {
			$replace['{' . $key . '}'] = $val;
		}

		// interpolate replacement values into the message and return
		return strtr($message, $replace);

	}

	/**
	 * System is unusable.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public static function emergency($message, array $context = array()) {

		return $this->log(SimpleLoggerLogLevels::EMERGENCY, $message, $context);

	}

	/**
	 * System is unusable.
	 *
	 * @param string $message key from getInfo messages array
	 * @param array $context
	 * @return null
	 */
	public function emergencyMessage($message, array $context = array()) {

		return $this->logByMessageKey(SimpleLoggerLogLevels::EMERGENCY, $message, $context);

	}

	/**
	 * Log with message
	 * Called from infoMessage(), errorMessage(), and so on
	 *
	 * Call like this:
	 *
	 *   return $this->logByMessageKey(SimpleLoggerLogLevels::EMERGENCY, $message, $context);
	 */
	private function logByMessageKey($SimpleLoggerLogLevelsLevel, $messageKey, $context) {
	
		// When logging by message then the key must exist	
		if ( ! isset( $this->messages[ $messageKey ]["untranslated_text"] ) ) {
			return;
		}

		/**
		 * Filter so plugins etc. can shortut logging
		 *
		 * @since 2.0.20
		 *
		 * @param true yes, we default to do the logging
		 * @param string logger slug
		 * @param string messageKey
		 * @param string log level
		 * @param array context
		 * @return bool false to abort logging
		 */
		$doLog = apply_filters("simple_history/simple_logger/log_message_key", true, $this->slug, $messageKey, $SimpleLoggerLogLevelsLevel, $context);
		
		if ( ! $doLog ) {
			return;
		}

		$context["_message_key"] = $messageKey;
		$message = $this->messages[ $messageKey ]["untranslated_text"];

		$this->log( $SimpleLoggerLogLevelsLevel, $message, $context );

	}


	/**
	 * Action must be taken immediately.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public static function alert($message, array $context = array()) {
		return $this->log(SimpleLoggerLogLevels::ALERT, $message, $context);

	}

	/**
	 * Action must be taken immediately.
	 *
	 * @param string $message key from getInfo messages array
	 * @param array $context
	 * @return null
	 */
	public function alertMessage($message, array $context = array()) {

		return $this->logByMessageKey(SimpleLoggerLogLevels::ALERT, $message, $context);

	}

	/**
	 * Critical conditions.
	 *
	 * Example: Application component unavailable, unexpected exception.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public static function critical($message, array $context = array()) {

		return $this->log(SimpleLoggerLogLevels::CRITICAL, $message, $context);

	}

	/**
	 * Critical conditions.
	 *
	 * @param string $message key from getInfo messages array
	 * @param array $context
	 * @return null
	 */
	public function criticalMessage($message, array $context = array()) {

		if (!isset($this->messages[$message]["untranslated_text"])) {
			return;
		}

		$context["_message_key"] = $message;
		$message = $this->messages[$message]["untranslated_text"];

		$this->log(SimpleLoggerLogLevels::CRITICAL, $message, $context);

	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function error($message, array $context = array()) {

		return $this->log(SimpleLoggerLogLevels::ERROR, $message, $context);

	}

	/**
	 * Runtime errors that do not require immediate action but should typically
	 * be logged and monitored.
	 *
	 * @param string $message key from getInfo messages array
	 * @param array $context
	 * @return null
	 */
	public function errorMessage($message, array $context = array()) {

		return $this->logByMessageKey(SimpleLoggerLogLevels::ERROR, $message, $context);

	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * Example: Use of deprecated APIs, poor use of an API, undesirable things
	 * that are not necessarily wrong.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function warning($message, array $context = array()) {

		return $this->log(SimpleLoggerLogLevels::WARNING, $message, $context);

	}

	/**
	 * Exceptional occurrences that are not errors.
	 *
	 * @param string $message key from getInfo messages array
	 * @param array $context
	 * @return null
	 */
	public function warningMessage($message, array $context = array()) {

		return $this->logByMessageKey(SimpleLoggerLogLevels::WARNING, $message, $context);

	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function notice($message, array $context = array()) {

		return $this->log(SimpleLoggerLogLevels::NOTICE, $message, $context);

	}

	/**
	 * Normal but significant events.
	 *
	 * @param string $message key from getInfo messages array
	 * @param array $context
	 * @return null
	 */
	public function noticeMessage($message, array $context = array()) {

		return $this->logByMessageKey(SimpleLoggerLogLevels::NOTICE, $message, $context);

	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function info($message, array $context = array()) {

		return $this->log(SimpleLoggerLogLevels::INFO, $message, $context);

	}

	/**
	 * Interesting events.
	 *
	 * Example: User logs in, SQL logs.
	 *
	 * @param string $message key from getInfo messages array
	 * @param array $context
	 * @return null
	 */
	public function infoMessage($message, array $context = array()) {

		return $this->logByMessageKey(SimpleLoggerLogLevels::INFO, $message, $context);

	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function debug($message, array $context = array()) {

		return $this->log(SimpleLoggerLogLevels::DEBUG, $message, $context);

	}

	/**
	 * Detailed debug information.
	 *
	 * @param string $message key from getInfo messages array
	 * @param array $context
	 * @return null
	 */
	public function debugMessage($message, array $context = array()) {

		return $this->logByMessageKey(SimpleLoggerLogLevels::DEBUG, $message, $context);

	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * @param mixed $level
	 * @param string $message
	 * @param array $context
	 * @return null
	 */
	public function log($level, $message, array $context = array()) {

		// Check criticity level in wp-config.php. If not defined, default to warning
		if(!defined('WP_SYSLOG_CRITICITY_LEVEL')) define('WP_SYSLOG_CRITICITY_LEVEL', 'warning');
		$numeric_criticity_level = array_search(WP_SYSLOG_CRITICITY_LEVEL, SimpleLoggerSyslog::NumericLogLevels());
		$numeric_level = array_search($level, SimpleLoggerSyslog::NumericLogLevels());
		// If criticity level of the message to log is over the value defined in config, just not log.
		if($numeric_level > $numeric_criticity_level) return true;


		// Check if $message is a translated message, and if so then fetch original
		$sh_latest_translations = $this->simpleHistory->gettextLatestTranslations;
		
		if ( ! empty( $sh_latest_translations ) ) {

			if ( isset( $sh_latest_translations[ $message ] ) ) {
				
				// Translation of this phrase was found, so use original phrase instead of translated one

				// Store textdomain since it's required to translate
				$context["_gettext_domain"] = $sh_latest_translations[$message]["domain"];
				
				// These are good to keep when debugging
				// $context["_gettext_org_message"] = $sh_latest_translations[$message]["text"];
				// $context["_gettext_translated_message"] = $sh_latest_translations[$message]["translation"];

				$message = $sh_latest_translations[ $message ]["text"];
			}

		}

		/**
		 * Filter arguments passed to log funtion
		 *
		 * @since 2.0
		 *
		 * @param string $level
		 * @param string $message
		 * @param array $context
		 * @param object SimpleLogger object
		 */
		apply_filters("simple_history/log_arguments", $level, $message, $context, $this);
		$context = apply_filters("simple_history/log_argument/context", $context, $level, $message, $this);
		$level = apply_filters("simple_history/log_argument/level", $level, $context, $message, $this);
		$message = apply_filters("simple_history/log_argument/message", $message, $level, $context, $this);

		// Allow date to be override
		// Date must be in format 'Y-m-d H:i:s'
		if (isset($context["_date"])) {
			unset($context["_date"]);
		}

		// Add occasions id
		$occasions_id = null;
		if (isset($context["_occasionsID"])) {

			// Minimize risk of similar loggers logging same messages and such and resulting in same occasions id
			// by always adding logger slug
			$occasions_data = array(
				"_occasionsID" => $context["_occasionsID"],
				"_loggerSlug" => $this->slug,
			);
			$occasions_id = md5(json_encode($occasions_data));
			unset($context["_occasionsID"]);

		} else {

			// No occasions id specified, create one bases on the data array
			$occasions_data = $context;

			// Don't include date in context data
			unset($occasions_data["date"]);

			//sf_d($occasions_data);exit;
			$occasions_id = md5(json_encode($occasions_data));

		}

		// Log initiator, defaults to current user if exists, or other if not user exist
		if ( isset( $context["_initiator"] ) ) {

			// Manually set in context
			unset( $context["_initiator"] );

		} else {

			// Check if user is responsible.
			if ( function_exists("wp_get_current_user") ) {

				$current_user = wp_get_current_user();

				if ( isset( $current_user->ID ) && $current_user->ID ) {
					$context["_user_id"] = $current_user->ID;
					$context["_user_login"] = $current_user->user_login;
					$context["_user_email"] = $current_user->user_email;

				}

			}

			// If cron then set WordPress as responsible
			if ( defined('DOING_CRON') && DOING_CRON ) {

				// Seems to be wp cron running and doing this
				$context["_wp_cron_running"] = true;

			}

		}

		// Detect XML-RPC calls and append to context, if not already there
		if ( defined("XMLRPC_REQUEST") && XMLRPC_REQUEST && ! isset( $context["_xmlrpc_request"] ) ) {

			$context["_xmlrpc_request"] = true;

		}

		if (!is_array($context)) {
			$context = array();
		}

		// Append user id to context, if not already added
		if (!isset($context["_user_id"])) {

			// wp_get_current_user is ont available early
			// http://codex.wordpress.org/Function_Reference/wp_get_current_user
			// https://core.trac.wordpress.org/ticket/14024
			if (function_exists("wp_get_current_user")) {

				$current_user = wp_get_current_user();

				if (isset($current_user->ID) && $current_user->ID) {
					$context["_user_id"] = $current_user->ID;
					$context["_user_login"] = $current_user->user_login;
					$context["_user_email"] = $current_user->user_email;
				}

			}

		}

		// Add remote addr to context
		// Good to always have
		if (!isset($context["_server_remote_addr"])) {

			$context["_server_remote_addr"] = $_SERVER["REMOTE_ADDR"];

			// If web server is behind a load balancer then the ip address will always be the same
			// See bug report: https://wordpress.org/support/topic/use-x-forwarded-for-http-header-when-logging-remote_addr?replies=1#post-6422981
			// Note that the x-forwarded-for header can contain multiple ips
			// Also note that the header can be faked
			// Ref: http://stackoverflow.com/questions/753645/how-do-i-get-the-correct-ip-from-http-x-forwarded-for-if-it-contains-multiple-ip
			// Ref: http://blackbe.lt/advanced-method-to-obtain-the-client-ip-in-php/

			// Check for IP in lots of headers
			// Based on code found here:
			// http://blackbe.lt/advanced-method-to-obtain-the-client-ip-in-php/
			$ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED');

			foreach ($ip_keys as $key) {

				if (array_key_exists($key, $_SERVER) === true) {

					// Loop through all IPs
					$ip_loop_num = 0;
					foreach (explode(',', $_SERVER[$key]) as $ip) {

						// trim for safety measures
						$ip = trim($ip);

						// attempt to validate IP
						if ($this->validate_ip($ip)) {

							// valid, add to context, with loop index appended so we can store many IPs
							$key_lower = strtolower($key);
							$context["_server_{$key_lower}_{$ip_loop_num}"] = $ip;

						}

						$ip_loop_num++;

					}

				}

			}

		}

		// Append http referer
		// Also good to always have!
		if (!isset($context["_server_http_referer"]) && isset($_SERVER["HTTP_REFERER"])) {
			$context["_server_http_referer"] = $_SERVER["HTTP_REFERER"];
		}

		// Syslog logging
		SimpleLoggerSyslog::SyslogLog( $level, $message, $context, $this->slug );

		// Return $this so we can chain methods
		return $this;

	} // log

	/**
	 * Ensures an ip address is both a valid IP and does not fall within
	 * a private network range.
	 */
	function validate_ip($ip) {

		if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4|FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE) === false) {
			return false;
		}

		return true;

	}

}

/**
 * Describes log initiator, i.e. who caused to log event to happend
 */
class SimpleLoggerLogInitiators {

	// A wordpress user that at the log event created did exist in the wp database
	// May have been deleted when the log is viewed
	const WP_USER = 'wp_user';

	// Cron job run = wordpress initiated
	// Email sent to customer on webshop = system/wordpress/anonymous web user
	// Javascript error occured on website = anonymous web user
	const WEB_USER = 'web_user';

	// WordPress core or plugins updated automatically via wp-cron
	const WORDPRESS = "wp";

	// WP CLI / terminal
	const WP_CLI = "wp_cli";

	// I dunno
	const OTHER = 'other';
}

/**
 * Describes log event type
 * Based on the CRUD-types
 * http://en.wikipedia.org/wiki/Create,_read,_update_and_delete
 * More may be added later on if needed
 * Note: not in use at the moment
 */
class SimpleLoggerLogTypes {
	const CREATE = 'create';
	const READ = 'read';
	const UPDATE = 'update';
	const DELETE = 'delete';
	const OTHER = 'other';
}

/**
 * Describes log levels
 */
class SimpleLoggerLogLevels {
	const EMERGENCY = 'emergency';
	const ALERT = 'alert';
	const CRITICAL = 'critical';
	const ERROR = 'error';
	const WARNING = 'warning';
	const NOTICE = 'notice';
	const INFO = 'info';
	const DEBUG = 'debug';
}

/**
 * List logger classes and assign a code to add before message to facilitate search in logs
 */
class SimpleLoggerLogDomains {
	public function getDomain($slug) {
		switch ($slug) {
			case 'SimpleUserLogger':
				return '[Auth]';
				break;

			case 'SimpleThemeLogger':
				return '[Theme]';
				break;

			case 'SimplePostLogger':
				return '[Post]';
				break;

			case 'SimplePluginLogger':
				return '[Plugin]';
				break;

			case 'SimpleOptionsLogger':
				return '[Options]';
				break;

			case 'SimpleMenuLogger':
				return '[Menu]';
				break;

			case 'SimpleMediaLogger':
				return '[Media]';
				break;

			case 'SimpleCoreUpdatesLogger':
				return '[Core]';
				break;

			case 'SimpleCommentsLogger':
				return '[Comments]';
				break;

			case 'SimpleExportLogger':
				return '[Export]';
				break;
			
			default:
				return '[Other]';
				break;
		}
	}
}

/**
 * Syslog log levels
 */
class SimpleLoggerSyslog {
	public static function SyslogLogLevels() {
		return array(
			SimpleLoggerLogLevels::EMERGENCY => LOG_EMERG,
			SimpleLoggerLogLevels::ALERT => LOG_ALERT,
			SimpleLoggerLogLevels::CRITICAL => LOG_CRIT,
			SimpleLoggerLogLevels::ERROR => LOG_ERR,
			SimpleLoggerLogLevels::WARNING => LOG_WARNING,
			SimpleLoggerLogLevels::NOTICE => LOG_NOTICE,
			SimpleLoggerLogLevels::INFO => LOG_INFO,
			SimpleLoggerLogLevels::DEBUG => LOG_DEBUG
		);
	}

	public static function NumericLogLevels() {
		return array(
			SimpleLoggerLogLevels::EMERGENCY,
			SimpleLoggerLogLevels::ALERT,
			SimpleLoggerLogLevels::CRITICAL,
			SimpleLoggerLogLevels::ERROR,
			SimpleLoggerLogLevels::WARNING,
			SimpleLoggerLogLevels::NOTICE,
			SimpleLoggerLogLevels::INFO,
			SimpleLoggerLogLevels::DEBUG,
		);
	}

	public static function SyslogLog($level, $message, $context, $slug) {
		// Syslog logging
		if(!defined('WP_SYSLOG_FACILITY')) define('WP_SYSLOG_FACILITY', LOG_LOCAL0);
		openlog(get_bloginfo('name'), LOG_PID | LOG_PERROR, WP_SYSLOG_FACILITY);
		$message = SimpleLogger::interpolate($message, $context);
		$success = syslog(SimpleLoggerSyslog::SyslogLogLevels()[$level], SimpleLoggerLogDomains::getDomain($slug)." ".$context['_user_login']." (".$context['_server_remote_addr'].") ".$message);
		if(!$success) {
			error_log('Log failure');
		}
		closelog();
	}
}
