<?php
/**
 * Sundsvalls Kommun Raindance
 *
 * @link     http://www.fmca.se/
 * @package  SK_Raindance
 *
 * @wordpress-plugin
 * Plugin Name:		SK Raindance
 * Plugin URI:		http://www.fmca.se/
 * Description:		Integrates with Raindance for API queries.
 * Version:			0.9
 * Author:			FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	http://www.fmca.se/
 * Text Domain:		sk-raindance
 * Domain Path:		/languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Make sure all dependencies are active.
if ( ! in_array( 'sk-webshop/sk-webshop.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	return;
}

// Define MBW_PLUGIN_FILE.
if ( ! defined( 'SK_RD_FILE' ) ) {
	define( 'SK_RD_FILE', __FILE__ );
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-raindance.php';
$sk_raindance = new SK_Raindance();

require_once __DIR__ . '/includes/class-sk-raindance-settings.php';
$sk_raindance_settings = new SK_Raindance_Settings();
