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
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Make sure all dependencies are active.
if ( ! in_array( 'sk-webshop/sk-webshop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-skios.php';
$skios = new SKIOS();