<?php
/*
Plugin Name: WSU Admin
Plugin URI: https://github.com/washingtonstateuniversity/WSUWP-Admin
Description: Customized portions of the admin area of WordPress for Washington State University
Author: washingtonstateuniversity, jeremyfelt
Author URI: https://web.wsu.edu
Version: 1.1.10
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// This plugin uses namespaces and requires PHP 5.3 or greater.
if ( version_compare( PHP_VERSION, '5.3', '<' ) ) {
	add_action( 'admin_notices', create_function( '', // @codingStandardsIgnoreLine
	"echo '<div class=\"error\"><p>" . __( 'WSUWP Admin requires PHP 5.3 to function properly. Please upgrade PHP or deactivate the plugin.', 'wsuwp-admin' ) . "</p></div>';" ) );
	return;
} else {
	// The core plugin class.
	require dirname( __FILE__ ) . '/includes/class-wsuwp-admin.php';

	add_action( 'after_setup_theme', 'WSUWP_Admin' );
	/**
	 * Start things up.
	 *
	 * @since 1.0.0
	 *
	 * @return \WSUWP_Admin
	 */
	function WSUWP_Admin() {
		return WSUWP_Admin::get_instance();
	}

	include_once __DIR__ . '/includes/restricted-site-access.php';
	include_once __DIR__ . '/includes/wsuwp-admin-remarketing.php';
	include_once __DIR__ . '/includes/response-headers.php';
}
