<?php
/**
 * SK_RD_Install
 * =============
 *
 * Handles all things that should be run
 * on plugin activation.
 *
 * @since   20191111
 * @package SK_Raindance
 */

class SK_RD_Install {

	/**
	 * Installs SK_Raindance.
	 * @since  1.0.0
	 * @return void
	 */
	public static function install() {
		self::setup_environment();
		self::create_tables();
	}

	/**
	 * Sets up our post types etc.
	 * @since  1.0.0
	 * @return void
	 */
	private static function setup_environment() {
	}

	/**
	 * Set up the database tables which the plugin needs to function.
	 *
	 * Tables:
	 *      sk_rd_responsibility_numbers - Table for storing responsibility numbers.
	 *      sk_rd_occupation_numbers - Table for storing occupation numbers.
	 * @return void
	 */
	private static function create_tables() {
		global $wpdb;

		$wpdb->hide_errors();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( self::get_schema() );
	}

	/**
	 * Get table schema.
	 * @return string
	 */
	private static function get_schema() {
		global $wpdb;

		$collate = '';
		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$tables = "
CREATE TABLE {$wpdb->prefix}sk_rd_responsibility_numbers (
	responsibility_number_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	responsibility_number BIGINT UNSIGNED NOT NULL,
	PRIMARY KEY (responsibility_number_id)
) $collate;

CREATE TABLE {$wpdb->prefix}sk_rd_occupation_numbers (
	occupation_number_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	occupation_number BIGINT UNSIGNED NOT NULL,
	PRIMARY KEY (occupation_number_id)
) $collate;

		";

		return $tables;
	}

	/**
	 * Uninstalls MBW.
	 * @return void
	 */
	public static function uninstall() {
		self::reset_environment();
	}

	/**
	 * Resets the WP environment.
	 * @return void
	 */
	private static function reset_environment() {
	}

}
