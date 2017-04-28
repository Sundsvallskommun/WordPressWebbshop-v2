<?php
/**
 * WooCommerce > FDT Avance integration
 *
 * @link     http://www.fmca.se/
 * @package  SK_DeDU
 *
 * @wordpress-plugin
 * Plugin Name:		DeDU for SK Internal Order System
 * Plugin URI:		http://www.fmca.se/
 * Description:		Extends the SK Internal Order System. Provides an integration with the DeDU system.
 * Version:			0.9
 * Author:			FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	http://www.fmca.se/
 * Text Domain:		sk-dedu
 * Domain Path:		/languages
 * Copyright:       © 2009-2015 WooThemes.
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Make sure all dependencies are active.
if ( ! in_array( 'sk-webshop/sk-webshop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-dedu.php';
$sk_dedu = SK_DeDU::get_instance();