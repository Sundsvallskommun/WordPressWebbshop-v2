<?php
/**
 * FMCA Base
 *
 * @link     http://www.fmca.se/
 * @package  FMCA
 *
 * @wordpress-plugin
 * Plugin Name:          FMCA Base Plugin
 * Plugin URI:           https://fmca.se/
 * Description:          Base plugin for sites powered by FMCA.
 * Version:              1.3.2
 * Author:               FMCA
 * Author URI:           https://fmca.se/
 * Text Domain:          fmca
 * Domain Path:          /languages
 * Copyright:            © 2018 Flatmate Creative Agency
 * License:              GNU General Public License v3.0
 * License URI:          http://www.gnu.org/licenses/gpl-3.0.html
 * WC requires at least: 3.0
 * WC tested up to:      3.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Include Composer.
require_once __DIR__ . '/vendor/autoload.php';

// Include all our defines.
require_once __DIR__ . '/includes/define.php';

// Include main plugin class.
require_once __DIR__ . '/includes/class-fmca.php';
$fmca = new FMCA\FMCA();

/**
 * Returns main instance of class.
 * @since  1.0.0
 * @return FMCA
 */
function FMCA() {
	global $fmca;
	return $fmca;
}
