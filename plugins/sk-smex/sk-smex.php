<?php
/**
 * Sundsvall Kommun SMEX.
 *
 * @link     http://www.fmca.se/
 * @package  SK_SMEX
 *
 * @wordpress-plugin
 * Plugin Name:		SK SMEX
 * Plugin URI:		http://www.fmca.se/
 * Description:		Integrates with SMEX to create a custom experience for the customer.
 * Version:			0.9
 * Author:			FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	http://www.fmca.se/
 * Text Domain:		sk-smex
 * Domain Path:		/languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

// Make sure all dependencies are active.
if ( ! in_array( 'sk-webshop/sk-webshop.php', $active_plugins ) || ! in_array( 'fmca-base/fmca-base.php', $active_plugins ) ) {
	return;
}

// Define SK_SMEX_FILE.
if ( ! defined( 'SK_SMEX_FILE' ) ) {
	define( 'SK_SMEX_FILE', __FILE__ );
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-smex.php';
$sk_smex = new SK_SMEX();

require_once __DIR__ . '/includes/class-sk-smex-settings.php';
$sk_smex_settings = new SK_SMEX_Settings();
