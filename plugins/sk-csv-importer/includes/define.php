<?php
/**
 * Defines
 * =======
 *
 * Sets up some constants used through out plugin.
 *
 * @since   20170206
 * @package SK_CVS_Importer
 */

// Set up plugin version.
if ( ! defined( 'SK_CVS_IMPORTER_VERSION' ) ) {
	define( 'SK_CVS_IMPORTER_VERSION', '20170206' );
}

// Set up plugin URL.
if ( ! defined( 'SK_CVS_IMPORTER_PLUGIN_URL' ) ) {
	define( 'SK_CVS_IMPORTER_PLUGIN_URL', untrailingslashit( plugins_url( '/', __DIR__ . '../' ) ) );
}

// Set up plugin path.
if ( ! defined( 'SK_CVS_IMPORTER_PLUGIN_PATH' ) ) {
	define( 'SK_CVS_IMPORTER_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ . '../' ) ) );
}