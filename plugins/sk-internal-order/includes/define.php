<?php
/**
 * Defines
 * =======
 *
 * Sets up some constants used through out plugin.
 *
 * @since   0.1
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