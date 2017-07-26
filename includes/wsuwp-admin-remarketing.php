<?php

namespace WSUWP\Admin\Remarketing;

add_action( 'wp_enqueue_scripts', 'WSUWP\Admin\Remarketing\enqueue_conversion_js', 999 );
add_action( 'wp_footer', 'WSUWP\Admin\Remarketing\output_etm_tag', 999 );

/**
 * Retrieve a remarketing conversion ID if it exists for this page.
 *
 * @since 1.1.0
 *
 * @return int|bool The conversion ID. False if no ID is configured.
 */
function get_remarketing_id() {
	$ids = array(
	);

	$site = get_site();

	if ( isset( $ids[ $site->domain . $site->path ] ) && is_front_page() ) {
		return $ids[ $site->domain . $site->path ];
	}

	return false;
}

/**
 * Enqueue the remarketing conversion script provided by Google Ad Services.
 *
 * @since 1.1.0
 */
function enqueue_conversion_js() {
	if ( false === get_remarketing_id() ) {
		return;
	}

	wp_enqueue_script( 'wsuwp-admin-remarketing', 'https://www.googleadservices.com/pagead/conversion.js', array(), false, true );
}

/**
 * Output a configured remarketing tag in the footer of a page.
 *
 * @since 1.1.0
 */
function output_etm_tag() {
	$conversion_id = get_remarketing_id();

	if ( false === $conversion_id ) {
		return;
	}

	?>
	<!-- Google Code for Remarketing Tag -->
	<!--------------------------------------------------
	Remarketing tags may not be associated with personally identifiable information or placed on pages related to sensitive categories. See more information and instructions on how to setup the tag on: http://google.com/ads/remarketingsetup
	--------------------------------------------------->
	<script type="text/javascript">
		/* <![CDATA[ */
		var google_conversion_id = <?php echo esc_js( $conversion_id ); ?>;
		var google_custom_params = window.google_tag_params;
		var google_remarketing_only = true;
		/* ]]> */
	</script>
	<noscript>
		<div style="display:inline;">
			<img height="1" width="1" style="border-style:none;" alt="" src="https://googleads.g.doubleclick.net/pagead/viewthroughconversion/848095247/?guid=ON&amp;script=0"/>
		</div>
	</noscript>
	<?php
}

