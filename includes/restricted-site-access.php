<?php

namespace WSUWP\Admin\Restricted_Site_Access;

add_action( 'admin_init', 'WSUWP\Admin\Restricted_Site_Access\remove_admin_notices', 2 );

/**
 * Remove the admin notices registered by Restricted Site Access
 * after 6.0.0.
 *
 * @since 1.1.5
 */
function remove_admin_notices() {
	remove_action( 'network_admin_notices', array( 'Restricted_Site_Access', 'page_cache_notice' ) );
	remove_action( 'admin_notices', array( 'Restricted_Site_Access', 'page_cache_notice' ) );
}
