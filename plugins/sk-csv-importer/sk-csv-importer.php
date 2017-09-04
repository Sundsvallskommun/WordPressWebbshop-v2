<?php
/**
 * SK CSV Importer
 *
 * @link     http://www.fmca.se/
 * @package  SK_SMEX
 *
 * @wordpress-plugin
 * Plugin Name:		SK CSV Importer
 * Plugin URI:		http://www.fmca.se/
 * Description:		Imports products from a CSV document.
 * Version:			0.9
 * Author:			FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	http://www.fmca.se/
 * Text Domain:		sk-csvimporter
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

// Include plugin definitions.
require __DIR__ . '/includes/define.php';

/**
 * @return void
 */
function register_sk_importer() {
	register_importer( 'sk_product_import_csv', __( 'SK produktimport (CSV)', 'sk-csvimporter' ), __( 'Importera <strong>produkter</strong> till din e-butik via en csv fil.', 'sk-csvimporter' ), 'sk_csv_importer' );
}
add_action( 'admin_init', 'register_sk_importer' );

/**
 * Registers our CSV importer.
 * @return void
 */
function sk_csv_importer() {
	// Load Importer API
	require_once ABSPATH . 'wp-admin/includes/import.php';

	if ( ! class_exists( 'WP_Importer' ) ) {
		$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';

		if ( file_exists( $class_wp_importer ) ) {
			require $class_wp_importer;
		}
	}

	// includes
	require __DIR__ . '/includes/class-sk-csv-importer.php';

	// Dispatch
	$importer = new SK_CSV_Importer();
	$importer->dispatch();
}