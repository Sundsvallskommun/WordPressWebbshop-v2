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
 * Text Domain:		sk-smex
 * Domain Path:		/languages
 * Copyright:       © 2009-2015 WooThemes.
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Make sure WooCommerce is active.
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-webshop.php';


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