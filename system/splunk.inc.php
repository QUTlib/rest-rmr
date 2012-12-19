<?php

require_once('esoe.inc.php');

class Splunk {

	const LOG_DELIMITER = ":";
	const LOG_SPACER = " ";

	private $log_location = NULL;
	private $log_data = NULL;

	public function __construct($logfilename='/tmp/splunk.log') {
		$this->log_location = $logfilename;
	}

	private function __deferred_init() {
		if (!is_null($this->log_data))
			return;

		$browser = get_browser();
		$this->log_data = array(
			'DATE' => date('Y-m-d\TH:i:sO'),
			// user and network information
			'IP'   => Request::client_ip(),
			'USER' => Request::server_var('REMOTE_USER', '-'),
			// browser and os information
			'BROWSER'       => $browser->browser,
			'BROWSERVER'    => $browser->version,
			'OS'            => $browser->platform,
			'ROLE'          => ESOE::roles('-'),
			'SERVICES'      => ESOE::services('-'),
			'CLIENTID'      => ESOE::clientid('-'),
			'STAFFNUMBER'   => ESOE::staffNumber('-'),
			'STUDENTNUMBER' => ESOE::studentNumber('-'),
		);
	}

	/**
	 * Sanitises a key.
	 * Converts to all uppcase, and removes any non-word characters.
	 */
	public function sanitise($key) {
		$key = strtoupper($key);
		$key = preg_replace('/[.:_-]+/', '_', $key);
		$key = preg_replace('/[^A-Z0-9_]+/', '', $key);
		return $key;
	}

	/**
	 * Gets a currently-defined value.
	 * @param string $key
	 * @param mixed $default what to return if key is undefined
	 */
	public function get($key, $default=NULL) {
		$this->__deferred_init();
		$key = $this->sanitise($key);
		if (array_key_exists($key, $this->log_data)) {
			return $this->log_data[$key];
		} else {
			return $default;
		}
	}

	/**
	 * Sets a value.
	 * @param string $key
	 * @param mixed $value
	 */
	public function set($key, $value) {
		$this->__deferred_init();
		$key = $this->sanitise($key);
		$this->log_data[$key] = $value;
	}

	/**
	 * Removes a value.
	 * @param string $key
	 */
	public function delete($key) {
		$this->__deferred_init();
		$key = $this->sanitise($key);
		if (array_key_exists($key, $this->log_data)) {
			unset($this->log_data[$key]);
		}
	}

	/**
	 * Gets the full concatenated message for this logging object.
	 * @return string
	 */
	public function message() {
		$this->__deferred_init();
		$elements = array();
		foreach ($this->log_data as $key=>$val) {
			// flatten arrays
			if (is_array($val))
				$val = implode(',', $val);
			// normalise whitespace
			$val = trim($val);
			$val = preg_replace('/\s+/', ' ', $val);
			// maybe quote stuff
			$pattern = '/('.preg_quote(Splunk::LOG_DELIMITER,'/').'|'.preg_quote(Splunk::LOG_SPACER,'/').'|")/';
			if (preg_match($pattern, $val)) {
				$val = preg_replace('/\\\\[\\"]/', '\\\$1', $val);
				$val = '"' . $val . '"';
			}
			// add to list
			$elements[] = sprintf('%s%s%s', $key, Splunk::LOG_DELIMITER, $val);
		}
		return implode(Splunk::LOG_SPACER, $elements);
	}

	/**
	 * Saves the log message to the file specified by $this->log_location
	 * @return boolean TRUE if it saved, FALSE otherwise
	 */
	public function save() {
		if (!$this->log_location) {
			error_log("Splunk logger: attempting to save, but no logfilename specified");
			return FALSE;
		}

		$log_message = $this->message();
		$log_message .= "\n";

		// Open file handle; log+abort on error
		$fh = @fopen($this->log_location, 'a');
		if ($fh === FALSE) {
			error_log("Splunk logger: unable to open ".$this->log_location." for appending");
			return FALSE;
		}

		// Write to file handle; log on error
		$result = fwrite($fh, $log_message);
		if ($result === FALSE) {
			error_log("Splunk logger: unable to write to ".$this->log_location);
		}
		fclose($fh);
		return !!$result;
	}

	/**
	 * Sends the log message to the syslog.
	 * @param int $priority
	 */
	public function syslog($priority=LOG_INFO) {
		$ident = sprintf('%s_%s', Application::TITLE, Application::VERSION);
		$message = $this->message();

		openlog($ident, LOG_ODELAY|LOG_PID, LOG_USER); // FIXME: LOG_DAEMON instead of LOG_USER ?
		syslog($priority, $message);
		closelog();
	}

	/**
	 * Sends the log message to the errorlog
	 */
	public function errorlog() {
		$ident = sprintf('%s_%s', Application::TITLE, Application::VERSION);
		$message = $this->message();

		error_log("[$ident] $message", 0);
	}

	/**
	 * Records the log message.
	 * First attempts #save(), and if that fails, falls back to one of the system log methods.
	 * @param bool $use_syslog if given and TRUE, uses #syslog() as a fallback, otherwuse uses #errorlog()
	 */
	public function log($use_syslog=FALSE) {
		// 1. maybe attempt to save()
		if ($this->log_location) {
			$success = $this->save();
		} else {
			$success = FALSE;
		}
		// 2. if it failed, use syslog or errorlog
		if (!$success) {
			if ($use_syslog) {
				$this->syslog();
			} else {
				$this->errorlog();
			}
		}
	}

}

