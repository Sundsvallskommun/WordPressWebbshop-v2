<?php
/**
 * Defines
 * =======
 *
 * Sets up some constants used through out plugin.
 *
 * @since   20170105
 * @package SKIOS
 */

// Set up plugin version.
if ( ! defined( 'SKIOS_VERSION' ) ) {
	define( 'SKIOS_VERSION', '20170105' );
}

// Set up plugin URL.
if ( ! defined( 'SKIOS_PLUGIN_URL' ) ) {
	define( 'SKIOS_PLUGIN_URL', untrailingslashit( plugins_url( '/', __DIR__ . '../' ) ) );
}

// Set up plugin path.
if ( ! defined( 'SKIOS_PLUGIN_PATH' ) ) {
	define( 'SKIOS_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ . '../' ) ) );
}