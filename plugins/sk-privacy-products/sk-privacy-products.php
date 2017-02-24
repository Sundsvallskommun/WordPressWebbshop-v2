<?php
/**
 * @link     http://www.fmca.se/
 * @package  SK_SMEX
 *
 * @wordpress-plugin
 * Plugin Name:		SK Sekretessprodukter
 * Plugin URI:		http://www.fmca.se/
 * Description:		Makes it possible to set certain products as privacy products. Orders with these will get customer information stripped after sending email notices.
 * Version:			0.9
 * Author:			FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	http://www.fmca.se/
 * Text Domain:		sk-privacy-products
 * Domain Path:		/languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Make sure all dependencies are active.
if ( ! in_array( 'sk-webshop/sk-webshop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-privacy-products.php';
$sk_smex = new SK_Privacy_Products();
