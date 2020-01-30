<?php
/**
 * FMCA
 * ====
 *
 * Main plugin file.
 *
 * Register hooks and filters.
 *
 * @since   1.0.0
 * @package FMCA
 */

namespace FMCA;

class FMCA {

	/**
	 * Sets up the plugin with settings page, hooks and filters.
	 */
	public function __construct() {
		$this->includes();
		$this->init_classes();
	}

	/**
	 * Includes files used both in front-end
	 * and admin.
	 * @since  1.0.0
	 * @return void
	 */
	private function includes() {
		require_once __DIR__ . '/admin/class-options.php';
		require_once __DIR__ . '/class-listener.php';
		require_once __DIR__ . '/fmca-site-hooks.php';
		require_once __DIR__ . '/fmca-site-functions.php';
	}

	/**
	 * Inits all necessary classes.
	 * @since  1.0.0
	 * @return void
	 */
	private function init_classes() {
		$this->options  = Options::get_instance();
		$this->listener = new Listener();
	}

	/**
	 * Logs an error using WC_Logger.
	 * @param  string $level
	 * @param  string $message
	 * @return void
	 */
	public static function log( $level, $message ) {
		$logger = wc_get_logger();
		$logger->log( $level, $message, [ 'source' => 'fmca' ] );
	}

}
