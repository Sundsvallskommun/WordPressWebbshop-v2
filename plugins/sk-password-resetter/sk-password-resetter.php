<?php
/**
 * SK Sekretessprodukter
 * 
 * @link     http://www.fmca.se/
 * @package  SK_SMEX
 *
 * @wordpress-plugin
 * Plugin Name:		SK Password Resetter
 * Plugin URI:		http://www.fmca.se/
 * Description:		Used to reset the password for all users except the one called "wpadmin". This is needed because of a change done to the SSO-plugin which makes sure that all passwords are being checked by the username in lowercase. Because of this we need to make sure all passwords in the DB is store based on these conditions. Regenerate plugins by visiting the url /?reset_passwords=run while the plugin is active.
 * Version:			1.0
 * Author:			FMCA
 * Author URI:		http://www.fmca.se/
 * Developer:		FMCA
 * Developer URI:	http://www.fmca.se/
 * Text Domain:		sk-password-resetter
 * Domain Path:		/languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if acccessed directly.
}

// Include main plugin class.
require_once __DIR__ . '/includes/class-sk-password-resetter.php';
$sk_password_resetter = new SK_Password_Resetter();
