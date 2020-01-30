<?php
/**
 * Plugin Name: SK Gravity Forms Field: Enduser
 * Description: Adds a gravity forms field for searching for Enduser from SMEX
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

// Make sure all dependencies are active.
if ( ! in_array( 'sk-webshop/sk-webshop.php', $active_plugins ) || ! in_array( 'fmca-base/fmca-base.php', $active_plugins ) ) {
	return;
}

if ( ! class_exists( 'GF_Fields' ) ) {
	return;
}

define( 'SK_GF_FIELD_ENDUSER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once __DIR__ . '/includes/class-gf-field-enduser.php';
require_once __DIR__ . '/includes/class-sk-enduser.php';
$sk_enduser = new SK_Enduser();
