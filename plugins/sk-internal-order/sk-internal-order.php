<?php
/**
 * Sundsvall Kommun Internal Order System
 *
 * @link     http://www.fmca.se/
 * @package  SKIOS
 *
 * @wordpress-plugin
 * Plugin Name:		SK Internal Order System
 * Plugin URI:		utveckling.sundsvall.se
 * Description:		Provides an internal order system.
 * Version:			20170105
 * Author:			SK/FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	utveckling.sundsvall.se
 * Text Domain:		skio
 * Domain Path:		/languages
 * Copyright:       © 2009-2015 WooThemes.
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Make sure WooCommerce is active.
// TODO: Add SK Internal Order System as a dependency aswell.
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	exit;
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-skios.php';
$skios = new SKIOS();