<?php
/**
 * WooCommerce > FDT Avance integration
 *
 * @link     http://www.fmca.se/
 * @package  SK_Webshop
 *
 * @wordpress-plugin
 * Plugin Name:		SK Webshop
 * Plugin URI:		http://www.fmca.se/
 * Description:		Base plugin for Sundsvall Kommun Webshop.
 * Version:			0.9
 * Author:			FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	http://www.fmca.se/
 * Text Domain:		sk-webshop
 * Domain Path:		/languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

// Make sure all dependencies are active.
if ( ! in_array( 'woocommerce/woocommerce.php', $active_plugins ) || ! in_array( 'fmca-base/fmca-base.php', $active_plugins ) ) {
	return;
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-webshop.php';

// Create an instance of the main class.
$skw = SK_Webshop::get_instance();

/**
 * Main instance of SK_Webshop.
 *
 * Returns the main instance of SK_Webshop to prevent the need to use globals.
 *
 * @since  2.1
 * @return SK_Webshop
 */
function SKW() {
	return SK_Webshop::get_instance();
}