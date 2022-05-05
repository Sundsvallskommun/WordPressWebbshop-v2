<?php
/**
 * POB for SK Internal Order System
 *
 * @link     http://www.consid.se/
 * @package  SK_pob
 *
 * @wordpress-plugin
 * Plugin Name:   POB for SK Internal Order System
 * Plugin URI:    http://www.consid.se/
 * Description:   Extends the SK Internal Order System. Provides an integration with the POB system.
 * Version:       0.9
 * Author:        Consid
 * Author URI:    http://www.consid.se/
 * Developer:     Consid
 * Developer URI: http://www.consid.se/
 * Text Domain:   sk-pob
 * Domain Path:   /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Make sure all dependencies are active.
if ( ! in_array( 'sk-webshop/sk-webshop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-pob.php';
$sk_pob = SK_POB::get_instance();
