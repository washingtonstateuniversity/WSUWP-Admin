<?php

namespace WSUWP\Admin\Akismet;

add_filter( 'pre_option_akismet_comment_form_privacy_notice', __NAMESPACE__ . '\\hide_privacy_notice' );

/**
 * Hide the Akismet comment form privacy notice.
 *
 * @since 1.7.0
 *
 * @return string
 */
function hide_privacy_notice( $option ) {
	return 'hide';
}
