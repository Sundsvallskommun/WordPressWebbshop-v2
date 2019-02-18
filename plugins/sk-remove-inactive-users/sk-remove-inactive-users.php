<?php
/**
 * Sundsvall Kommun Remove Inactive Users
 *
 * @link     http://www.fmca.se/
 * @package
 *
 * @wordpress-plugin
 * Plugin Name:		SK Remove Inactive Users
 * Plugin URI:		utveckling.sundsvall.se
 * Description:		Provides functionality to remove users that is no longer available in SMEX.
 * Version:			20181128
 * Author:			SK/FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	utveckling.sundsvall.se
 * Text Domain:		sk-ria
 * Domain Path:		/languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-remove-inactive-users.php';
$sk_remove_inactive_users = new SK_Remove_Inactive_Users();