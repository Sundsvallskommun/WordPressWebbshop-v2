<?php
/**
 * Define
 * ======
 *
 * Sets up some constants used through out plugin.
 *
 * @since   1.0.0
 * @package FMCA
 */

// Set up plugin version.
if ( ! defined( 'FMCA_VERSION' ) ) {
	define( 'FMCA_VERSION', '1.3.2' );
}

// Set up plugin URL.
if ( ! defined( 'FMCA_PLUGIN_URL' ) ) {
	define( 'FMCA_PLUGIN_URL', untrailingslashit( plugins_url( '/', __DIR__ . '../' ) ) );
}
