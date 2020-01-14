<?php
/**
 * SK_SMEX_Install
 * ===============
 *
 * Handles all things that should be run
 * on plugin activation.
 *
 * @since   20200113
 * @package SK_SMEX
 */

class SK_SMEX_Install {

	/**
	 * Installs SK_SMEX.
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
	 *      sk_smex_searchable_persons - Table for storing searchable persons.
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
CREATE TABLE `{$wpdb->prefix}sk_smex_searchable_persons` (
	`person_id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
	`person` varchar(100) NOT NULL,
	PRIMARY KEY (person_id),
	KEY `person` (`person`)
) $collate;

		";

		return $tables;
	}

	/**
	 * Uninstalls SK_SMEX.
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
