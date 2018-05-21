<?php

namespace WSUWP\Admin\GravityForms;

add_action( 'admin_menu', 'WSUWP\Admin\GravityForms\remove_system_status', 999 );

/**
 * Remove the Gravity Forms "System Status" page from the admin.
 *
 * @since 1.6.0
 */
function remove_system_status() {
	remove_submenu_page( 'gf_edit_forms', 'gf_system_status' );
}
