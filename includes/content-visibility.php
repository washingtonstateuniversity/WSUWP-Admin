<?php

namespace WSUWP\Admin\ContentVisibility;

add_filter( 'content_visibility_default_groups', 'WSUWP\Admin\ContentVisibility\modify_default_groups', 50 );
add_filter( 'user_in_content_visibility_groups', 'WSUWP\Admin\ContentVisibility\user_in_groups', 20, 3 );


/**
 * Extends the list of groups offered in the Content Visibility plugin.
 *
 * @since 1.5.0
 *
 * @param array $groups
 *
 * @return array
 */
function modify_default_groups( $groups ) {
	$site = get_site();

	if ( 'stage.web.wsu.edu' === $site->domain && 'aoisupport/' === $site->path ) {
		$aoi_groups = array(
			array(
				'id'   => 'aoi-employees-all',
				'name' => 'AOI.Employees.All',
			),
		);
		$groups = array_merge( $groups, $aoi_groups );
	}

	return $groups;
}

/**
 * Determines if a given user is a member of the passed groups.
 *
 * @since 1.5.0
 *
 * @param bool  $allowed Whether the user is associated with the passed groups.
 * @param int   $user_id ID of the user.
 * @param array $groups  List of groups to check the user against.
 *
 * @return bool False if the user is not a group member. True if the user is.
 */
function user_in_groups( $allowed, $user_id, $groups ) {
	$site = get_site();

	// This is the only site we provide custom groups for at this time. Once we
	// expand to other sites, this check will likely need to change.
	if ( 'stage.web.wsu.edu' !== $site->domain || 'aoisupport/' !== $site->path ) {
		return $allowed;
	}

	// Don't override a previous success.
	if ( true === $allowed ) {
		return $allowed;
	}
	// The WSUWP SSO Authentication plugin is required for this check to work.
	if ( ! class_exists( 'WSUWP_SSO_Authentication' ) ) {
		return $allowed;
	}
	if ( 'nid' !== WSUWP_SSO_Authentication()->get_user_type( $user_id ) ) {
		return $allowed;
	}
	// Skip any further checking if AD is disabled locally.
	if ( defined( 'WSU_LOCAL_CONFIG' ) && WSU_LOCAL_CONFIG && false === apply_filters( 'wsuwp_sso_force_local_ad', false ) ) {
		return $allowed;
	}

	$user = new WP_User( $user_id );
	$user_ad_data = WSUWP_SSO_Authentication()->refresh_user_data( $user );

	if ( in_array( 'aoi-employees-all', $groups, true ) && in_array( 'AOI.Employees.All', $user_ad_data['memberof'], true ) ) {
		return true;
	}

	return $allowed;
}
