<?php
/*
Plugin Name: WSU Admin
Plugin URI: https://github.com/washingtonstateuniversity/WSUWP-Admin
Description: Customized portions of the admin area of WordPress for Washington State University
Author: washingtonstateuniversity, jeremyfelt
Author URI: https://web.wsu.edu
Version: 1.0.6
*/

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

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
