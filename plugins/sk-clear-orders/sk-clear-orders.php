<?php
/**
 * Sundsvall Kommun Clear Orders Function
 *
 * @link     http://www.fmca.se/
 * @package
 *
 * @wordpress-plugin
 * Plugin Name:		SK Clear Orders
 * Plugin URI:		utveckling.sundsvall.se
 * Description:		Provides functionality to clear old orders from WooCommerce.
 * Version:			20181128
 * Author:			SK/FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	utveckling.sundsvall.se
 * Text Domain:		sk-clearorders
 * Domain Path:		/languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-clear-orders.php';
$skios = new SK_Clear_Orders();