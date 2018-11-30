<?php
/**
 * Sundsvall Kommun Products CSV Export
 *
 * @link     http://www.fmca.se/
 * @package
 *
 * @wordpress-plugin
 * Plugin Name:		SK Products CSV Export
 * Plugin URI:		utveckling.sundsvall.se
 * Description:		Provides functionality to export products from WooCommerce to csv in a format intended to be used by humans. NOTE: This plugin is not indended to be used together with SK CSV Importer.
 * Version:			20181128
 * Author:			SK/FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	utveckling.sundsvall.se
 * Text Domain:		sk-csvexport
 * Domain Path:		/languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-csv-export.php';
$skios = new SK_CSV_Export();